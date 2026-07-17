# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

RT29 Minomartani — a CodeIgniter 4 + Shield web app for an Indonesian neighborhood association (RT). It's a CI3→CI4 rewrite: the original CI3 app (if present on this machine) lives outside this repo at `c:\laragon\www\rt29`; this repo is the CI4 target. Both point at the same live MySQL database (`rt29mino`), so schema and data are shared/production, not disposable — see "Working with the database" below before writing migrations or seeders.

Modules: Warga (residents), Alamat (addresses, with QR codes), Berita (news), Surat (letter/certificate requests), Inventaris (inventory), Pekerjaan (occupations), Layanan (public service request form), plus Shield-backed user/auth management.

## Commands

```bash
composer install                    # install dependencies
php spark serve --port 8082         # dev server (matches app.baseURL in .env)
php spark routes                    # list all registered routes + filters
php spark migrate                   # apply pending migrations
php spark migrate:status            # check which migrations have run
php spark key:generate              # generate encryption.key into .env

php spark test                      # run PHPUnit
vendor/bin/phpunit                  # same, direct
vendor/bin/phpunit --filter TestName            # run a single test class
vendor/bin/phpunit --filter TestName::testMethod # run a single test method
vendor/bin/phpunit tests/database/MigrationsTest.php  # run one file
```

Database tests need a real MySQL database (not the CI4 SQLite default) because migrations use MySQL-specific SQL (`ON UPDATE CURRENT_TIMESTAMP`, raw `ALTER TABLE`). One-time setup:

```bash
mysql -u root -e "CREATE DATABASE rt29mino_test CHARACTER SET utf8mb4"
```

`phpunit.xml.dist` already points `database.tests.*` at `rt29mino_test`.

## Architecture

### View rendering: no CI4 layout engine, manual concatenation

`BaseController` (`app/Controllers/BaseController.php`) does not use CI4's `extend`/`section` layout system. It provides two hand-rolled methods that concatenate view strings, carried over from the CI3 version:

- `loadViews($viewName, $headerInfo, $pageInfo, $footerInfo)` — admin pages: `layouts/header` + `$viewName` + `layouts/footer`
- `load_view($viewName, $data)` — public pages: `includes/header` + `$viewName` + `includes/footer`

New controllers should call one of these rather than CI4's native `view()` composition. `BaseController::initController()` also globally autoloads the `form`, `url`, and `kbw` helpers — don't re-`helper()` those in individual controllers.

### `kbw_helper.php` (`app/Helpers/kbw_helper.php`)

Grab-bag of utility functions carried over from CI3: `pre()` (dump+exit), `setFlashData()`/`loadFlashData()` (flash message + toast HTML), `convert_to_rupiah()`, `bulan_indo()`, `tanggal()`, `umur()`, `nicetime()`, `assets()` (prefixes `public/`), `back()`. Password-hashing helpers were deliberately dropped when Shield took over auth.

### Auth: CodeIgniter Shield, not the legacy `user` table

Shield owns authentication (`users`, `auth_identities`, `auth_groups_users`, etc.). The legacy `user` table still exists in the DB (and has a migration, `CreateLegacyUserTable`) purely for historical/audit reference — nothing in the app reads or writes it anymore. Groups are defined in `app/Config/AuthGroups.php` (`superadmin`, `admin`, `developer`, `user`, `rw`, `beta`); routes under `admin/*` require the Shield `session` filter (any logged-in user), and the higher-blast-radius `admin/users/*` additionally requires `group:superadmin` (`CodeIgniter\Shield\Filters\GroupFilter`, aliased as `'group'` in `app/Config/Filters.php`). When adding a new admin surface that manages sensitive data, wrap it in its own route group with an explicit `filter` rather than assuming the outer `admin` group's `session` filter is enough.

### Multi-tenancy: one DB, `id_rt` on every tenant table

The app is multi-tenant (hierarchy: `rw` → `rt`; RT 29 is tenant `id_rt = 1`). Tenant-owned tables (`warga`, `alamat`, `berita`, `surat`, `inventaris`, `dawis`, `ketua`) carry an `id_rt` column; lookup tables (`pekerjaan`, `status_*`, `layanan`) are shared. Isolation is enforced twice: the `tenant` filter (`App\Filters\TenantFilter`) establishes the session tenant context for `admin/*`, and **every model query adds `->where('<table>.id_rt', current_rt_id())`** — new model methods must do the same, and inserts must set `'id_rt' => current_rt_id()`. The `tenant` helper (autoloaded via `Config\Autoload::$helpers`) provides `current_rt_id()`/`current_rt()`/`current_rw_id()`/`tenant_set_rt()`. Public pages are slug-prefixed (`/rt29/...`, resolved via `BaseController::resolveTenant()`); alamat QR codes are generated with the owning RT's slug baked into the URL (`current_rt()->slug`), and the legacy unprefixed `detail/(:any)` route looks up the QR code's owning RT and 301-redirects to the slug-prefixed URL, so QR codes printed before multi-tenancy keep working regardless of which RT they belong to. Other legacy unprefixed public routes (`berita/(:any)`, `layanan`) fall back to RT 29 since they carry no tenant-identifying record. Account model: superadmin (no tenant, header dropdown switches active RT), group `admin` + `users.id_rt` (RT admin), group `rw` + `users.id_rw` (read-only recap at `admin/rekap`). `TenantFilter::hostMatchesUser()` lets `rw` accounts authenticate on *any* RT subdomain belonging to their own RW, not just a dedicated RW domain — since `rw` users have no `id_rt`, host access is matched via `id_rw` instead, with the read-only restriction enforced separately (not by host-matching). Tenant provisioning is superadmin-only (`admin/tenants`).

### CSRF is on globally

`'csrf'` is in `$globals['before']` in `app/Config/Filters.php`. CI4's `form_open()`/`form_open_multipart()` helpers auto-inject the token; hand-rolled `<form>` tags need `<?= csrf_field() ?>` explicitly. When testing route/filter wiring, remember `RouteCollection::getFiltersForRoute()` resolves by the *current* HTTP verb context, which in a CLI test run isn't `get`/`post` by default — call `$this->collection->setHTTPVerb('get')` (or `'post'`) before asserting, and use the literal registered route pattern for dynamic segments (e.g. `admin/users/delete/([0-9]+)`, not a resolved example like `admin/users/delete/1`).

### Working with the database

- **`DATABASE.md` (repo root) is the source of truth for current table structure** — built from a real production dump cross-checked against migrations and `allowedFields`, not just a migration replay. Read it instead of piecing schema together from migrations when you need to know what columns/indexes/FKs actually exist live. **Whenever a new migration is added (new table, new/dropped column, new index/FK), update `DATABASE.md` in the same change** so it doesn't drift from what's actually in `app/Database/Migrations/`. If a discrepancy between a migration and the live DB is found (as happened with `warga.sumber_air`, `alamat.kode_rumah`, and the removed `surat.no_surat`/`id_alamat`), note it in `DATABASE.md` too, not just in code comments.
- **The dev DB has real production data.** Migrations in `app/Database/Migrations/` are written idempotently (`$this->forge->createTable($table, true, [...])` — the `true` is `ifNotExists`) specifically so they're safe to run against a DB that already has the tables (no-op) as well as bootstrap a fresh one. Follow this pattern for new migrations; don't assume you're working against a disposable database.
- Several tables use `latin1`/`latin1_swedish_ci` (legacy CI3-era default) rather than `utf8mb4` — match the existing table's actual charset/collation when altering it (check with `SHOW CREATE TABLE`), don't silently convert.
- Data-only migrations (not just schema) are a valid pattern here — see `MigrateLegacyUsersToShield` for one that copies rows into Shield's auth tables idempotently (matches on email before inserting, safe to re-run).
- Schema changes go through `app/Database/Migrations/` only. There used to be an `Admin\Sync` controller that let a logged-in session upload JSON and run arbitrary `CREATE TABLE`/`ADD COLUMN` against the live DB as a stopgap before real migrations existed — it has been removed. Don't reintroduce that pattern.
- `DatabaseTestTrait`'s `$namespace` defaults to `'Tests\Support'` (the framework's own example migrations), not `App` — override `protected $namespace = null;` in a test class to run this app's real migrations before the test.

### Deployment

See `DEPLOYMENT.md` for the full checklist (TLS is required — `forcehttps` is a required filter in production; encryption key must be generated fresh per environment; pre-deploy DB backup, etc.) and `.env.production.example` for the production `.env` template.
