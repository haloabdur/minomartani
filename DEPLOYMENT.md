# Deployment Guide

Deployment notes for RT29 Minomartani (CI4 + Shield). Read this before pushing to a new environment.

## Fresh install

```bash
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
# Edit .env: real DB credentials, app.baseURL, CI_ENVIRONMENT = production
php spark key:generate
php spark migrate
```

`php spark migrate` creates every app table (warga, alamat, berita, surat, inventaris, pekerjaan, ketua, dawis, status lookups) plus Shield's auth tables from nothing — see `app/Database/Migrations/`. No manual SQL import needed.

If this is a genuinely fresh install (no legacy data), there's nothing to migrate into Shield and `MigrateLegacyUsersToShield` will no-op (it only acts on rows in the legacy `user` table). Register the first admin via `php spark shield:user create` instead.

## Migrating from the old data (this deployment's history)

This app's schema and the legacy `user` table's 6 accounts were ported from a live database rather than starting fresh — see the migration commit history for what each migration does. If you're standing up a new environment from that same live data dump, run migrations against it directly; they're idempotent (`forge->createTable($name, true, ...)`) so they're safe to run against a DB that already has the tables.

## TLS is required

`app/Config/Filters.php`'s `required.before` list includes `forcehttps` by default. Once `CI_ENVIRONMENT = production`, every request redirects to HTTPS. **Get a real TLS certificate before pointing a domain at this**, or requests will redirect-loop.

## Encryption key

Generate a fresh one **on the production server itself** — don't copy the development key:

```bash
php spark key:generate
```

Shield's remember-me tokens and encrypted session data depend on this. Keep `.env` out of version control (already gitignored) and keep a secure backup of the key outside the app server.

## Schema changes going forward

`Admin\Sync` (a stopgap tool that let any logged-in session upload JSON and run arbitrary `CREATE TABLE`/`ADD COLUMN` against the live DB) has been removed. **All schema changes now go through `app/Database/Migrations/`** — write a new migration class, test it, commit it, then `php spark migrate` on each environment. Never hand-edit the schema directly on production.

## User management

- `admin/users/*` requires the Shield `admin` group (`group:admin` filter), not just being logged in.
- All 6 accounts ported from the legacy `user` table landed in the `admin` group (the old `role` column was never actually enforced, so there was no real permission tier to preserve). Review `Admin\Users` and demote any account that shouldn't have full admin access.
- Two of the six migrated accounts have irregular data (`risto5`: malformed email `risto@rt29`; `risto6`: a second `risto`-named account) — confirm with the site owner whether these are still in use.

## Pre-deploy checklist

- [ ] `.env` has real DB credentials, `CI_ENVIRONMENT = production`, correct `app.baseURL`
- [ ] Fresh `encryption.key` generated on the target server
- [ ] TLS certificate installed and working
- [ ] `php spark migrate` run, `php spark migrate:status` shows all migrations applied
- [ ] `php spark test` (or `vendor/bin/phpunit`) green — requires a `rt29mino_test` MySQL database (`mysql -u root -e "CREATE DATABASE rt29mino_test CHARACTER SET utf8mb4"`) and `phpunit.xml.dist`'s `database.tests.*` `<env>` values pointing at it
- [ ] `php spark routes` reviewed - confirm no unexpected routes
- [ ] Full DB backup taken immediately before deploying (`mysqldump`)
