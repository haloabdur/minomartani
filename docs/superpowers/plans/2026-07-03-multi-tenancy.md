# Multi-tenancy RT/RW Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the single-tenant RT 29 app into a multi-tenant system where every RT and RW gets its own account and all data is isolated per RT, per the approved spec `docs/superpowers/specs/2026-07-03-multi-tenancy-design.md`.

**Architecture:** Single database, `id_rt` column on every tenant-owned table, enforced two ways: a `tenant` route filter that establishes the tenant context in the session at request time, plus an explicit `WHERE id_rt = current_rt_id()` in every model query. Hierarchy: `rw` → `rt`. Public pages become slug-prefixed (`/rt29/...`). RW accounts are read-only (rekap only). Only superadmin provisions tenants.

**Tech Stack:** CodeIgniter 4.7 + Shield, MySQL (`rt29mino` live / `rt29mino_test` for tests), PHPUnit.

## Global Constraints

- **The dev DB contains live production data for RT 29.** Every migration MUST be idempotent (safe to re-run, safe against a DB that already has the tables) and non-destructive. Backfill always assigns existing rows to `id_rt = 1` (RT 29).
- New tables use `utf8mb4`; never alter the charset of existing legacy `latin1` tables (adding an INT column is charset-neutral and fine).
- Views are rendered via `BaseController::loadViews()` (admin) / `load_view()` (public) — never CI4 `extend`/`section`.
- `form_open()` auto-injects CSRF; hand-rolled `<form>` needs `<?= csrf_field() ?>`.
- Route/filter tests: call `$this->collection->setHTTPVerb('get'|'post')` before asserting, and use the literal registered route pattern (e.g. `admin/users/delete/([0-9]+)`).
- Database tests: `use DatabaseTestTrait;` with `protected $namespace = null;` so the app's real migrations run.
- Run tests with `vendor/bin/phpunit` (or `php spark test`). Never run `php spark migrate` against the live DB as part of this plan — tests use `rt29mino_test`.
- All user-facing copy (flash messages, labels, menu items) is Indonesian, matching existing copy style (`setFlashData('success', 'Data ... berhasil ...')`).

---

### Task 1: Tenant master tables `rw` and `rt` + seed RT 29

**Files:**
- Create: `app/Database/Migrations/2026-07-03-071200_CreateTenantTables.php`
- Test: `tests/database/TenancyMigrationsTest.php`

**Interfaces:**
- Produces: tables `rw` (`id_rw`, `nama`, `slug`, `is_aktif`, `created_at`) and `rt` (`id_rt`, `id_rw`, `nama`, `slug`, `is_aktif`, `created_at`); seeded rows: RW "RW Minomartani" (slug `rw-minomartani`) and RT "RT 29" (slug `rt29`, `id_rt = 1`, belongs to that RW). Later tasks depend on `id_rt = 1` being RT 29 and on the `slug` values.

- [ ] **Step 1: Write the failing test**

Create `tests/database/TenancyMigrationsTest.php`:

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Regression guard for the multi-tenancy migrations: tenant master
 * tables, per-table id_rt columns, and Shield users tenant columns.
 *
 * @internal
 */
final class TenancyMigrationsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    // null runs migrations from every discovered namespace, including
    // our App migrations under test (default is 'Tests\Support').
    protected $namespace = null;

    public function testRwTableHasExpectedColumns(): void
    {
        $db     = Database::connect();
        $fields = array_map(static fn ($f) => $f->name, $db->getFieldData('rw'));

        foreach (['id_rw', 'nama', 'slug', 'is_aktif', 'created_at'] as $expected) {
            $this->assertContains($expected, $fields, "rw.{$expected} is missing");
        }
    }

    public function testRtTableHasExpectedColumns(): void
    {
        $db     = Database::connect();
        $fields = array_map(static fn ($f) => $f->name, $db->getFieldData('rt'));

        foreach (['id_rt', 'id_rw', 'nama', 'slug', 'is_aktif', 'created_at'] as $expected) {
            $this->assertContains($expected, $fields, "rt.{$expected} is missing");
        }
    }

    public function testRt29IsSeededAsTenantOne(): void
    {
        $db = Database::connect();

        $rt = $db->table('rt')->where('slug', 'rt29')->get()->getRow();
        $this->assertNotNull($rt, 'seed row rt29 is missing');
        $this->assertSame(1, (int) $rt->id_rt, 'RT 29 must be id_rt = 1 (existing data is backfilled to 1)');

        $rw = $db->table('rw')->where('id_rw', $rt->id_rw)->get()->getRow();
        $this->assertNotNull($rw, 'seeded RT 29 does not belong to a seeded RW');
    }

    public function testTenantSeedIsIdempotent(): void
    {
        $db = Database::connect();

        $migration = new \App\Database\Migrations\CreateTenantTables(Database::forge());
        $migration->up(); // second run must not duplicate seed rows

        $this->assertSame(1, $db->table('rt')->where('slug', 'rt29')->countAllResults());
        $this->assertSame(1, $db->table('rw')->where('slug', 'rw-minomartani')->countAllResults());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php`
Expected: FAIL — `Table 'rt29mino_test.rw' doesn't exist` (or class `CreateTenantTables` not found).

- [ ] **Step 3: Write the migration**

Create `app/Database/Migrations/2026-07-03-071200_CreateTenantTables.php`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Multi-tenancy master tables. Hierarchy: rw -> rt. Every tenant-owned
 * data row carries an id_rt (added in the next migration).
 *
 * Idempotent: createTable(..., true) no-ops when the table exists, and
 * the seed matches on slug before inserting. RT 29 (the original
 * single-tenant install) is deliberately the first rt row so it gets
 * id_rt = 1 - the value the data backfill migration assigns to every
 * pre-existing row.
 */
class CreateTenantTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_rw'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'is_aktif'   => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_rw');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('rw', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        $this->forge->addField([
            'id_rt'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_rw'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'is_aktif'   => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_rt');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('id_rw');
        $this->forge->createTable('rt', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        $this->seedFirstTenant();
    }

    public function down()
    {
        $this->forge->dropTable('rt', true);
        $this->forge->dropTable('rw', true);
    }

    private function seedFirstTenant(): void
    {
        $db = $this->db;

        if ($db->table('rw')->where('slug', 'rw-minomartani')->countAllResults() === 0) {
            $db->table('rw')->insert([
                'nama' => 'RW Minomartani',
                'slug' => 'rw-minomartani',
            ]);
        }

        $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        if ($db->table('rt')->where('slug', 'rt29')->countAllResults() === 0) {
            $db->table('rt')->insert([
                'id_rw' => $idRw,
                'nama'  => 'RT 29',
                'slug'  => 'rt29',
            ]);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Run the full existing suite to check for regressions**

Run: `vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Database/Migrations/2026-07-03-071200_CreateTenantTables.php tests/database/TenancyMigrationsTest.php
git commit -m "feat: add rw/rt tenant master tables with RT 29 seed"
```

---

### Task 2: `id_rt` column on the 7 tenant data tables + backfill

**Files:**
- Create: `app/Database/Migrations/2026-07-03-071300_AddTenantColumnToDataTables.php`
- Test: `tests/database/TenancyMigrationsTest.php` (add methods)

**Interfaces:**
- Consumes: `rt` table from Task 1 (`id_rt = 1` is RT 29).
- Produces: column `id_rt INT NOT NULL DEFAULT 1` + index on tables `warga`, `alamat`, `berita`, `surat`, `inventaris`, `dawis`, `ketua`. The DEFAULT 1 both backfills existing rows and acts as a last-resort safety net; application code always sets `id_rt` explicitly (Task 6).

- [ ] **Step 1: Add failing tests to `tests/database/TenancyMigrationsTest.php`**

```php
    public function testAllTenantDataTablesHaveIdRtColumn(): void
    {
        $db = Database::connect();

        foreach (['warga', 'alamat', 'berita', 'surat', 'inventaris', 'dawis', 'ketua'] as $table) {
            $this->assertTrue(
                $db->fieldExists('id_rt', $table),
                "{$table}.id_rt is missing",
            );
        }
    }

    public function testLookupTablesStayGlobal(): void
    {
        $db = Database::connect();

        // Master/lookup data is shared across tenants by design.
        foreach (['pekerjaan', 'status_keluarga', 'status_penduduk'] as $table) {
            $this->assertFalse(
                $db->fieldExists('id_rt', $table),
                "{$table} must NOT be tenant-scoped",
            );
        }
    }

    public function testIdRtDefaultsToRt29(): void
    {
        $db = Database::connect();

        $db->table('alamat')->insert(['alamat' => 'Uji Backfill']);
        $row = $db->table('alamat')->where('alamat', 'Uji Backfill')->get()->getRow();

        $this->assertSame(1, (int) $row->id_rt, 'rows without explicit id_rt must land in RT 29');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php`
Expected: FAIL — `warga.id_rt is missing`.

- [ ] **Step 3: Write the migration**

Create `app/Database/Migrations/2026-07-03-071300_AddTenantColumnToDataTables.php`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the tenant discriminator to every tenant-owned data table.
 *
 * NOT NULL DEFAULT 1: existing production rows (all RT 29) are
 * backfilled to id_rt = 1 by the DEFAULT during ADD COLUMN, and any
 * code path that somehow skips setting id_rt still lands in RT 29
 * instead of failing. Application code always sets id_rt explicitly.
 *
 * Idempotent: fieldExists() guard makes re-runs and runs against a
 * DB that already has the column a no-op. Lookup tables (pekerjaan,
 * status_keluarga, status_penduduk, layanan) intentionally stay
 * global - do not add id_rt to them.
 */
class AddTenantColumnToDataTables extends Migration
{
    private array $tables = ['warga', 'alamat', 'berita', 'surat', 'inventaris', 'dawis', 'ketua'];

    public function up()
    {
        foreach ($this->tables as $table) {
            if (! $this->db->tableExists($table) || $this->db->fieldExists('id_rt', $table)) {
                continue;
            }

            $this->forge->addColumn($table, [
                'id_rt' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            ]);
            $this->db->query("ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_id_rt` (`id_rt`)");
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('id_rt', $table)) {
                $this->forge->dropColumn($table, 'id_rt');
            }
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Database/Migrations/2026-07-03-071300_AddTenantColumnToDataTables.php tests/database/TenancyMigrationsTest.php
git commit -m "feat: add id_rt tenant column to all tenant data tables"
```

---

### Task 3: Tenant columns on Shield `users` + new `rw` group

**Files:**
- Create: `app/Database/Migrations/2026-07-03-071400_AddTenantColumnsToUsers.php`
- Modify: `app/Config/AuthGroups.php`
- Test: `tests/database/TenancyMigrationsTest.php` (add methods), `tests/unit/AuthGroupsTest.php` (create)

**Interfaces:**
- Produces: nullable `users.id_rt` and `users.id_rw` columns; Shield group `rw`. Account semantics used by all later tasks: superadmin → both NULL; RT admin (group `admin`) → `id_rt` set; RW account (group `rw`) → `id_rw` set.

- [ ] **Step 1: Add failing tests**

Add to `tests/database/TenancyMigrationsTest.php`:

```php
    public function testUsersTableHasTenantColumns(): void
    {
        $db = Database::connect();

        $this->assertTrue($db->fieldExists('id_rt', 'users'), 'users.id_rt is missing');
        $this->assertTrue($db->fieldExists('id_rw', 'users'), 'users.id_rw is missing');
    }

    public function testExistingAdminAccountsAreBackfilledToRt29(): void
    {
        $db = Database::connect();

        $unassigned = $db->table('users')
            ->join('auth_groups_users', 'auth_groups_users.user_id = users.id')
            ->where('auth_groups_users.group', 'admin')
            ->where('users.id_rt IS NULL')
            ->countAllResults();

        $this->assertSame(0, $unassigned, 'admin-group users must be backfilled to id_rt = 1');
    }
```

Create `tests/unit/AuthGroupsTest.php`:

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthGroups;

/**
 * Guard for the tenant-related Shield group config.
 *
 * @internal
 */
final class AuthGroupsTest extends CIUnitTestCase
{
    public function testRwGroupIsDefined(): void
    {
        $config = new AuthGroups();

        $this->assertArrayHasKey('rw', $config->groups, "the 'rw' Shield group is missing");
        $this->assertArrayHasKey('rw', $config->matrix, "the 'rw' group has no permissions matrix entry");
        $this->assertSame([], $config->matrix['rw'], 'rw accounts are read-only: no admin permissions');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php tests/unit/AuthGroupsTest.php`
Expected: FAIL — `users.id_rt is missing` and `the 'rw' Shield group is missing`.

- [ ] **Step 3: Write the migration**

Create `app/Database/Migrations/2026-07-03-071400_AddTenantColumnsToUsers.php`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Links Shield accounts to tenants. Nullable on purpose:
 *  - superadmin: id_rt NULL, id_rw NULL (all tenants)
 *  - RT admin (group 'admin'): id_rt set
 *  - RW account (group 'rw'): id_rw set
 *
 * Backfill: every pre-existing admin-group account is an RT 29
 * pengurus, so they get id_rt = 1. Idempotent via fieldExists() and
 * the IS NULL condition on the backfill.
 */
class AddTenantColumnsToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('id_rt', 'users')) {
            $this->forge->addColumn('users', [
                'id_rt' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'id_rw' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
        }

        $adminIds = array_column(
            $this->db->table('auth_groups_users')->select('user_id')->where('group', 'admin')->get()->getResultArray(),
            'user_id',
        );

        if ($adminIds !== []) {
            $this->db->table('users')
                ->whereIn('id', $adminIds)
                ->where('id_rt IS NULL')
                ->update(['id_rt' => 1]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('id_rt', 'users')) {
            $this->forge->dropColumn('users', ['id_rt', 'id_rw']);
        }
    }
}
```

- [ ] **Step 4: Add the `rw` group to `app/Config/AuthGroups.php`**

In `$groups`, after the `'user'` entry, add:

```php
        'rw' => [
            'title'       => 'Pengurus RW',
            'description' => 'Read-only recap access over the RTs in their RW.',
        ],
```

In `$matrix`, after `'user' => [],`, add:

```php
        'rw' => [],
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/database/TenancyMigrationsTest.php tests/unit/AuthGroupsTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Database/Migrations/2026-07-03-071400_AddTenantColumnsToUsers.php app/Config/AuthGroups.php tests/database/TenancyMigrationsTest.php tests/unit/AuthGroupsTest.php
git commit -m "feat: link Shield users to tenants and add rw group"
```

---

### Task 4: Tenant context — helper, `TenantContext`, `RtModel`/`RwModel`

**Files:**
- Create: `app/Libraries/TenantContext.php`, `app/Helpers/tenant_helper.php`, `app/Models/RtModel.php`, `app/Models/RwModel.php`
- Modify: `app/Config/Autoload.php:91` (`public $helpers = ['auth', 'setting'];`)
- Test: `tests/unit/TenantHelperTest.php`

**Interfaces:**
- Produces (used by every later task):
  - `current_rt_id(): ?int` — request override first, then session `tenant_rt_id`.
  - `current_rt(): ?object` — the `rt` row for `current_rt_id()`.
  - `current_rw_id(): ?int` — session `tenant_rw_id` (RW accounts).
  - `tenant_set_rt(?int $idRt): void` — request-scoped override (public slug pages, tests).
  - `App\Libraries\TenantContext::$rtId` / `TenantContext::reset()`.
  - `RtModel` (`$returnType = 'object'`): `bySlug(string $slug): ?object`, `aktif(): array`, `byRw(int $idRw): array`.
  - `RwModel` (`$returnType = 'object'`): `aktif(): array`.

- [ ] **Step 1: Write the failing test**

Create `tests/unit/TenantHelperTest.php`:

```php
<?php

use App\Libraries\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TenantHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function testCurrentRtIdIsNullWithoutContext(): void
    {
        $this->assertNull(current_rt_id());
    }

    public function testRequestOverrideWins(): void
    {
        tenant_set_rt(7);
        $this->assertSame(7, current_rt_id());

        tenant_set_rt(null);
        $this->assertNull(current_rt_id());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/unit/TenantHelperTest.php`
Expected: FAIL — helper file `tenant` not found.

- [ ] **Step 3: Write the implementation**

Create `app/Libraries/TenantContext.php`:

```php
<?php

namespace App\Libraries;

/**
 * Request-scoped tenant override. Public slug-prefixed pages (and
 * tests) set this explicitly; admin pages rely on the session values
 * written by TenantFilter instead.
 */
final class TenantContext
{
    public static ?int $rtId = null;

    public static function reset(): void
    {
        self::$rtId = null;
    }
}
```

Create `app/Helpers/tenant_helper.php`:

```php
<?php

use App\Libraries\TenantContext;
use App\Models\RtModel;

if (! function_exists('tenant_set_rt')) {
    /**
     * Sets the request-scoped tenant (public slug pages, tests).
     */
    function tenant_set_rt(?int $idRt): void
    {
        TenantContext::$rtId = $idRt;
    }
}

if (! function_exists('current_rt_id')) {
    /**
     * The active tenant. Request override first (public pages), then
     * the session context established by TenantFilter (admin pages).
     */
    function current_rt_id(): ?int
    {
        if (TenantContext::$rtId !== null) {
            return TenantContext::$rtId;
        }

        $id = session('tenant_rt_id');

        return $id === null ? null : (int) $id;
    }
}

if (! function_exists('current_rt')) {
    function current_rt(): ?object
    {
        $id = current_rt_id();

        return $id === null ? null : model(RtModel::class)->find($id);
    }
}

if (! function_exists('current_rw_id')) {
    function current_rw_id(): ?int
    {
        $id = session('tenant_rw_id');

        return $id === null ? null : (int) $id;
    }
}
```

Create `app/Models/RtModel.php`:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class RtModel extends Model
{
    protected $table         = 'rt';
    protected $primaryKey    = 'id_rt';
    protected $returnType    = 'object';
    protected $allowedFields = ['id_rw', 'nama', 'slug', 'is_aktif'];

    public function bySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->where('is_aktif', 1)->first();
    }

    /** @return list<object> */
    public function aktif(): array
    {
        return $this->where('is_aktif', 1)->orderBy('nama')->findAll();
    }

    /** @return list<object> */
    public function byRw(int $idRw): array
    {
        return $this->where('id_rw', $idRw)->where('is_aktif', 1)->orderBy('nama')->findAll();
    }
}
```

Create `app/Models/RwModel.php`:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class RwModel extends Model
{
    protected $table         = 'rw';
    protected $primaryKey    = 'id_rw';
    protected $returnType    = 'object';
    protected $allowedFields = ['nama', 'slug', 'is_aktif'];

    /** @return list<object> */
    public function aktif(): array
    {
        return $this->where('is_aktif', 1)->orderBy('nama')->findAll();
    }
}
```

In `app/Config/Autoload.php` change:

```php
    public $helpers = ['auth', 'setting'];
```

to:

```php
    public $helpers = ['auth', 'setting', 'tenant'];
```

(Autoload — not `BaseController::$helpers` — so the helper is also loaded in CLI/tests and inside models.)

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/unit/TenantHelperTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Libraries/TenantContext.php app/Helpers/tenant_helper.php app/Models/RtModel.php app/Models/RwModel.php app/Config/Autoload.php tests/unit/TenantHelperTest.php
git commit -m "feat: add tenant context helper and rt/rw models"
```

---

### Task 5: `TenantFilter` + admin route wiring

**Files:**
- Create: `app/Filters/TenantFilter.php`
- Modify: `app/Config/Filters.php` (alias), `app/Config/Routes.php:21` (admin group filter)
- Test: `tests/filters/RouteFilterTest.php` (add methods)

**Interfaces:**
- Consumes: helper functions from Task 4; `users.id_rt`/`id_rw` and group `rw` from Task 3.
- Produces: filter alias `tenant`; behavior contract:
  - superadmin: auto-selects the first active RT into `session('tenant_rt_id')` if none chosen yet; always allowed through.
  - group `rw`: writes `session('tenant_rw_id')`; any URI outside `admin/rekap*` redirects to `admin/rekap`.
  - other logged-in users: must have `users.id_rt`, which is written to `session('tenant_rt_id')`; users with no tenant are logged out.

- [ ] **Step 1: Add failing wiring tests to `tests/filters/RouteFilterTest.php`**

```php
    public function testAdminRoutesRequireTenantContext(): void
    {
        $this->assertFilter('admin/warga', 'before', 'tenant');
        $this->assertFilter('admin/dashboard', 'before', 'tenant');

        $this->collection->setHTTPVerb('post');
        $this->assertFilter('admin/warga/store', 'before', 'tenant');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php`
Expected: FAIL — filter `tenant` not applied (and alias unknown).

- [ ] **Step 3: Write the filter**

Create `app/Filters/TenantFilter.php`:

```php
<?php

namespace App\Filters;

use App\Models\RtModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Establishes the tenant context for the admin panel. Runs after
 * Shield's 'session' filter (route group order), so an unauthenticated
 * request never reaches the tenant checks.
 *
 * This is one of two isolation layers: the second is the explicit
 * WHERE id_rt = current_rt_id() in every model query.
 */
class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return; // Shield's session filter handles this case
        }

        $user    = auth()->user();
        $session = session();
        $path    = implode('/', $request->getUri()->getSegments());

        if ($user->inGroup('superadmin')) {
            if ($session->get('tenant_rt_id') === null) {
                $first = model(RtModel::class)->aktif()[0] ?? null;
                if ($first !== null) {
                    $session->set('tenant_rt_id', (int) $first->id_rt);
                }
            }

            return;
        }

        if ($user->inGroup('rw')) {
            if (empty($user->id_rw)) {
                auth()->logout();

                return redirect()->to('login');
            }

            $session->set('tenant_rw_id', (int) $user->id_rw);

            // RW accounts are read-only: rekap is their only surface.
            if (strpos($path, 'admin/rekap') !== 0) {
                return redirect()->to('admin/rekap');
            }

            return;
        }

        // Regular RT admin: must belong to an RT.
        if (empty($user->id_rt)) {
            auth()->logout();
            // kbw helper is normally loaded by BaseController, which
            // has not run yet inside a before-filter.
            helper('kbw');
            setFlashData('error', 'Akun Anda belum terhubung ke RT mana pun. Hubungi superadmin.');

            return redirect()->to('login');
        }

        $session->set('tenant_rt_id', (int) $user->id_rt);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
```

- [ ] **Step 4: Wire the alias and the route group**

In `app/Config/Filters.php` add the import and alias:

```php
use App\Filters\TenantFilter;
```

```php
        'tenant'        => TenantFilter::class,
```

In `app/Config/Routes.php` change line 21:

```php
$routes->group('admin', ['filter' => 'session'], function ($routes) {
```

to:

```php
$routes->group('admin', ['filter' => ['session', 'tenant']], function ($routes) {
```

- [ ] **Step 5: Run the filter tests**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php`
Expected: PASS — including the pre-existing assertions (`session` on `admin/warga`, `group:admin` on `admin/users`), which must keep passing with the array filter syntax.

- [ ] **Step 6: Commit**

```bash
git add app/Filters/TenantFilter.php app/Config/Filters.php app/Config/Routes.php tests/filters/RouteFilterTest.php
git commit -m "feat: add tenant filter establishing per-request tenant context"
```

---

### Task 6: Tenant-scope every model query + controller inserts

**Files:**
- Modify: `app/Models/WargaModel.php`, `app/Models/AlamatModel.php`, `app/Models/BeritaModel.php`, `app/Models/SuratModel.php`, `app/Models/InventarisModel.php`
- Modify: `app/Controllers/Admin/Warga.php` (`store()`), `app/Controllers/Admin/Alamat.php` (`store()`), `app/Controllers/Admin/Berita.php` (`store()`), `app/Controllers/Admin/Inventaris.php` (`store()`), `app/Controllers/Admin/Surat.php` (`store()`)
- Test: `tests/database/TenantIsolationTest.php` (create)

**Interfaces:**
- Consumes: `current_rt_id()` / `tenant_set_rt()` from Task 4; `id_rt` columns from Task 2.
- Produces: every read in the five models is filtered by `id_rt = current_rt_id()`; every model gains `'id_rt'` in `$allowedFields`; every admin `store()` sets `'id_rt' => current_rt_id()`.

- [ ] **Step 1: Write the failing isolation test**

Create `tests/database/TenantIsolationTest.php`:

```php
<?php

use App\Libraries\TenantContext;
use App\Models\AlamatModel;
use App\Models\BeritaModel;
use App\Models\WargaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * The core multi-tenancy guarantee: a query executed in tenant A's
 * context never returns tenant B's rows.
 *
 * @internal
 */
final class TenantIsolationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        $this->seedTwoTenants();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function seedTwoTenants(): void
    {
        $db = Database::connect();

        // Second tenant next to the seeded RT 29 (id_rt = 1).
        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test']);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;

        $db->table('berita')->insertBatch([
            ['judul' => 'Berita A', 'slug' => 'berita-a', 'deskripsi' => 'x', 'is_status' => 1, 'id_rt' => 1],
            ['judul' => 'Berita B', 'slug' => 'berita-b', 'deskripsi' => 'x', 'is_status' => 1, 'id_rt' => $this->rtB],
        ]);

        // id_alamat is set on both warga rows so the nik() lookup's
        // INNER JOIN to alamat cannot mask a missing id_rt filter.
        $db->table('alamat')->insert(['alamat' => 'Jalan A1', 'id_rt' => 1]);
        $idAlamatA = $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'Jalan B1', 'id_rt' => $this->rtB]);
        $idAlamatB = $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'Jalan B2', 'id_rt' => $this->rtB]);

        $db->table('warga')->insertBatch([
            [
                'no_kk' => '111', 'nama_warga' => 'Warga A', 'nik' => '1111111111111111',
                'jenis_kelamin' => 'L', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
                'id_pekerjaan' => 1, 'id_alamat' => $idAlamatA, 'id_rt' => 1,
            ],
            [
                'no_kk' => '222', 'nama_warga' => 'Warga B', 'nik' => '2222222222222222',
                'jenis_kelamin' => 'P', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
                'id_pekerjaan' => 1, 'id_alamat' => $idAlamatB, 'id_rt' => $this->rtB,
            ],
        ]);
    }

    private int $rtB;

    public function testBeritaIsIsolatedPerTenant(): void
    {
        tenant_set_rt(1);
        $judul = array_column((array) (new BeritaModel())->all(), 'judul');
        $this->assertContains('Berita A', $judul);
        $this->assertNotContains('Berita B', $judul, 'tenant A sees tenant B berita!');

        tenant_set_rt($this->rtB);
        $judul = array_column((array) (new BeritaModel())->all(), 'judul');
        $this->assertContains('Berita B', $judul);
        $this->assertNotContains('Berita A', $judul, 'tenant B sees tenant A berita!');
    }

    public function testWargaCountsAreIsolatedPerTenant(): void
    {
        $model = new WargaModel();

        tenant_set_rt($this->rtB);
        $this->assertSame(1, $model->count());
        $this->assertSame(0, $model->laki_count(), 'RT B has no male warga');
        $this->assertSame(1, $model->perempuan_count());
    }

    public function testAlamatCountIsIsolatedPerTenant(): void
    {
        $model = new AlamatModel();

        tenant_set_rt($this->rtB);
        $this->assertSame(2, $model->alamat_count());
    }

    public function testNikLookupIsIsolatedPerTenant(): void
    {
        // Layanan (public) verifies warga by NIK - must not leak
        // across tenants.
        tenant_set_rt(1);
        $this->assertNull((new WargaModel())->nik('2222222222222222'), 'NIK lookup crossed tenants');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/database/TenantIsolationTest.php`
Expected: FAIL — `tenant A sees tenant B berita!` (models are unscoped).

- [ ] **Step 3: Scope the models**

Every changed method below filters on the table-qualified column. `count()` in each model also gets the WHERE. Add `'id_rt'` to each model's `$allowedFields`.

`app/Models/BeritaModel.php` — full new file:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class BeritaModel extends Model
{
    protected $table         = 'berita';
    protected $primaryKey    = 'id_berita';
    protected $allowedFields = ['judul', 'slug', 'deskripsi', 'lampiran', 'foto', 'kategori', 'is_status', 'created_by', 'timestamp', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->where('berita.id_rt', current_rt_id())
            ->orderBy('timestamp', 'desc')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_berita', $id)
            ->where('berita.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function detail_berita($slug)
    {
        return $this->db->table($this->table)
            ->where('slug', $slug)
            ->where('berita.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('berita.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
```

`app/Models/SuratModel.php` — full new file:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class SuratModel extends Model
{
    protected $table         = 'surat';
    protected $primaryKey    = 'id_surat';
    protected $allowedFields = ['no_surat', 'id_warga', 'id_alamat', 'maksut', 'perlu', 'lampiran', 'status_surat', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('nama_warga, no_hp, surat.*')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->where('surat.id_rt', current_rt_id())
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('surat.*, warga.*, alamat.alamat')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('surat.id_surat', $id)
            ->where('surat.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('surat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
```

`app/Models/InventarisModel.php` — full new file:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class InventarisModel extends Model
{
    protected $table         = 'inventaris';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama_barang', 'stok', 'foto', 'created_at', 'updated_at', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->where('id_rt', current_rt_id())
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function hapus($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('id_rt', current_rt_id())
            ->delete();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
```

`app/Models/AlamatModel.php` — full new file:

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class AlamatModel extends Model
{
    protected $table         = 'alamat';
    protected $primaryKey    = 'id_alamat';
    protected $allowedFields = ['nomor', 'alamat', 'kode_rumah', 'qrcode', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('alamat.id_alamat, alamat.alamat, alamat.qrcode, COUNT(warga.id_warga) jumlah')
            ->join('warga', 'warga.id_alamat = alamat.id_alamat AND warga.status_warga = 1', 'left')
            ->where('alamat.id_rt', current_rt_id())
            ->groupBy('alamat.id_alamat')
            ->get()->getResult();
    }

    public function alamat_detail($kode)
    {
        return $this->db->table($this->table)
            ->select('nama_warga, alamat')
            ->where('qrcode', $kode)
            ->where('id_status_keluarga', 1)
            ->where('alamat.id_rt', current_rt_id())
            ->join('warga', 'warga.id_alamat = alamat.id_alamat')
            ->get()->getRow();
    }

    public function cek_alamat($alamat, $nomor)
    {
        return $this->db->table($this->table)
            ->where('alamat', $alamat)
            ->where('nomor', $nomor)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function alamat_count()
    {
        return $this->db->table($this->table)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function kosong_count()
    {
        return $this->db->table($this->table)
            ->where('id_alamat NOT IN (SELECT id_alamat FROM warga WHERE status_warga = 1 AND id_alamat IS NOT NULL)', null, false)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_alamat', $id)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getRow();
    }
}
```

`app/Models/WargaModel.php` — add `'id_rt'` to `$allowedFields`, and add the tenant WHERE to every method. The changed methods (same order as the existing file):

```php
    public function all()
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
            ->where('warga.id_rt', current_rt_id())
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('id_warga', $id)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function kk_count()
    {
        return $this->db->table($this->table)
            ->select('DISTINCT(warga.`no_kk`)')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function laki_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'L')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function perempuan_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'P')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function nik($nik)
    {
        return $this->db->table($this->table)
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('nik', $nik)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function export()
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getResult();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
```

(`get_status_keluarga()` / `get_status_penduduk()` stay unscoped — lookup tables.)

- [ ] **Step 4: Set `id_rt` on every admin insert**

In each `store()` data array, add one line `'id_rt' => current_rt_id(),`:

- `app/Controllers/Admin/Warga.php` `store()` — in the `$data = [...]` array (after `'sumber_air'`).
- `app/Controllers/Admin/Alamat.php` `store()` — in the `$data = [...]` array (after `'qrcode'`).
- `app/Controllers/Admin/Berita.php` `store()` — in its `$data` array.
- `app/Controllers/Admin/Inventaris.php` `store()` — in its `$data` array.
- `app/Controllers/Admin/Surat.php` `store()` — in its `$data` array.

The NIK-uniqueness rule (`is_unique[warga.nik]`) deliberately stays **global**: NIK is nationally unique; one person must not exist in two RTs.

- [ ] **Step 5: Run the isolation test, then the full suite**

Run: `vendor/bin/phpunit tests/database/TenantIsolationTest.php`
Expected: PASS.

Run: `vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Models tests/database/TenantIsolationTest.php app/Controllers/Admin
git commit -m "feat: enforce tenant isolation in all model queries and inserts"
```

---

### Task 7: Slug-prefixed public routes + legacy redirects + QR URLs

**Files:**
- Modify: `app/Config/Routes.php` (public section), `app/Controllers/BaseController.php`, `app/Controllers/Home.php`, `app/Controllers/Layanan.php`, `app/Controllers/Admin/Alamat.php` (QR URL)
- Modify views: `app/Views/beranda.php:48`, `app/Views/layanan.php:37`, `app/Views/layanan_sukses.php:11`, `app/Views/includes/nav-white.php:4`
- Create: `app/Views/pilih_rt.php`
- Test: `tests/filters/PublicTenantRoutingTest.php` (create)

**Interfaces:**
- Consumes: `RtModel::bySlug()`, `tenant_set_rt()`, scoped models from Task 6.
- Produces: URL scheme `/{slug}`, `/{slug}/berita/(:any)`, `/{slug}/detail/(:any)`, `/{slug}/layanan[...]`; `BaseController::resolveTenant(string $slug): object` (throws `PageNotFoundException` on unknown/inactive slug, calls `tenant_set_rt()`, returns the rt row) — used by `Home` and `Layanan`.

- [ ] **Step 1: Write the failing routing test**

Create `tests/filters/PublicTenantRoutingTest.php`:

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guards the public URL scheme: slug-prefixed tenant routes are
 * registered (and last), and the legacy single-tenant URLs still
 * resolve so printed QR codes and old links keep working.
 *
 * @internal
 */
final class PublicTenantRoutingTest extends CIUnitTestCase
{
    private array $getRoutes;

    protected function setUp(): void
    {
        parent::setUp();
        $collection      = service('routes')->loadRoutes();
        $collection->setHTTPVerb('get');
        $this->getRoutes = $collection->getRoutes('get');
    }

    public function testTenantPublicRoutesAreRegistered(): void
    {
        $uris = array_keys($this->getRoutes);

        $this->assertContains('([^/]+)', $uris, 'slug landing route missing');
        $this->assertContains('([^/]+)/berita/(.*)', $uris, 'slug berita route missing');
        $this->assertContains('([^/]+)/detail/(.*)', $uris, 'slug alamat-detail route missing');
        $this->assertContains('([^/]+)/layanan', $uris, 'slug layanan route missing');
    }

    public function testSlugCatchAllIsRegisteredAfterAdmin(): void
    {
        $uris     = array_keys($this->getRoutes);
        $slugPos  = array_search('([^/]+)', $uris, true);
        $adminPos = array_search('admin', $uris, true);

        $this->assertNotFalse($slugPos);
        $this->assertNotFalse($adminPos);
        $this->assertGreaterThan($adminPos, $slugPos, 'slug catch-all must be registered AFTER admin routes');
    }

    public function testLegacyUrlsStillResolve(): void
    {
        $uris = array_keys($this->getRoutes);

        $this->assertContains('detail/(.*)', $uris, 'legacy QR route detail/(:any) was removed');
        $this->assertContains('berita/(.*)', $uris, 'legacy berita route was removed');
        $this->assertContains('layanan', $uris, 'legacy layanan route was removed');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/filters/PublicTenantRoutingTest.php`
Expected: FAIL — `slug landing route missing`.

- [ ] **Step 3: Rewrite the public route section**

In `app/Config/Routes.php`, replace the front-end block (lines 9–15) with:

```php
// Legacy single-tenant URLs - redirect into the default tenant so old
// links and already-printed alamat QR codes keep working.
$routes->get('/', 'Home::landing');
$routes->get('detail/(:any)', 'Home::legacyAlamat/$1');
$routes->get('berita/(:any)', 'Home::legacyBerita/$1');
$routes->get('layanan', 'Layanan::legacyIndex');
```

Then, at the very **bottom** of the file (after the whole `admin` group — CI4 matches routes in registration order, so the slug catch-all must come last), add:

```php
// Tenant public routes - slug-prefixed, one per RT. MUST stay the
// last registered routes: (:segment) would otherwise swallow
// 'admin', 'login', etc.
$routes->get('(:segment)', 'Home::index/$1');
$routes->get('(:segment)/detail/(:any)', 'Home::alamat/$1/$2');
$routes->get('(:segment)/berita/(:any)', 'Home::berita/$1/$2');
$routes->get('(:segment)/layanan', 'Layanan::index/$1');
$routes->post('(:segment)/layanan/store', 'Layanan::store/$1');
$routes->get('(:segment)/layanan/sukses', 'Layanan::sukses/$1');
```

- [ ] **Step 4: Add `resolveTenant()` to `BaseController`**

In `app/Controllers/BaseController.php` add:

```php
    /**
     * Resolves a public URL tenant slug to its rt row, sets the
     * request tenant context, and 404s on unknown/inactive slugs.
     */
    protected function resolveTenant(string $slug): object
    {
        $rt = model(\App\Models\RtModel::class)->bySlug($slug);

        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        tenant_set_rt((int) $rt->id_rt);

        return $rt;
    }
```

- [ ] **Step 5: Rework `Home` and `Layanan` for slugs**

`app/Controllers/Home.php` — full new file:

```php
<?php

namespace App\Controllers;

use App\Models\AlamatModel;
use App\Models\BeritaModel;
use App\Models\RtModel;
use App\Models\WargaModel;

class Home extends BaseController
{
    protected $alamatModel;
    protected $beritaModel;
    protected $wargaModel;

    public function __construct()
    {
        $this->alamatModel = new AlamatModel();
        $this->beritaModel = new BeritaModel();
        $this->wargaModel  = new WargaModel();
    }

    /**
     * Root URL: single active RT redirects straight to it, several
     * active RTs render a chooser.
     */
    public function landing()
    {
        $rts = model(RtModel::class)->aktif();

        if (count($rts) === 1) {
            return redirect()->to('/' . $rts[0]->slug);
        }

        return $this->load_view('pilih_rt', ['rts' => $rts, 'rt' => null]);
    }

    public function index($slug)
    {
        $rt = $this->resolveTenant($slug);
        $db = \Config\Database::connect();

        $data['rt']        = $rt;
        $data['ketuas']    = $db->table('ketua')->where('id_rt', current_rt_id())->get()->getResult();
        $data['beritas']   = $db->table('berita')->where('is_status', 1)->where('id_rt', current_rt_id())->orderBy('timestamp', 'desc')->limit(3)->get()->getResult();
        $data['kk']        = $this->wargaModel->kk_count();
        $data['laki']      = $this->wargaModel->laki_count();
        $data['perempuan'] = $this->wargaModel->perempuan_count();

        return $this->load_view('beranda', $data);
    }

    public function alamat($slug, $kode)
    {
        $data['rt']     = $this->resolveTenant($slug);
        $data['alamat'] = $this->alamatModel->alamat_detail($kode);

        if (empty($data['alamat'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('alamat_detail', $data);
    }

    public function berita($slug, $beritaSlug)
    {
        $data['rt']     = $this->resolveTenant($slug);
        $data['berita'] = $this->beritaModel->detail_berita($beritaSlug);

        if (empty($data['berita'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('berita_detail', $data);
    }

    /**
     * Legacy /detail/{kode} QR URL: printed QR codes predate the slug
     * scheme. Resolve the alamat's owning RT, then redirect.
     */
    public function legacyAlamat($kode)
    {
        $db  = \Config\Database::connect();
        $row = $db->table('alamat')
            ->select('alamat.qrcode, rt.slug')
            ->join('rt', 'rt.id_rt = alamat.id_rt')
            ->where('alamat.qrcode', $kode)
            ->get()->getRow();

        if ($row === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return redirect()->to("/{$row->slug}/detail/{$kode}", null, 301);
    }

    public function legacyBerita($beritaSlug)
    {
        return redirect()->to('/' . $this->defaultSlug() . '/berita/' . $beritaSlug, null, 301);
    }

    /**
     * The pre-multi-tenant install is tenant id 1 (RT 29).
     */
    protected function defaultSlug(): string
    {
        $rt = model(RtModel::class)->find(1);

        return $rt->slug ?? 'rt29';
    }
}
```

`app/Controllers/Layanan.php` — full new file:

```php
<?php

namespace App\Controllers;

use App\Models\RtModel;
use App\Models\SuratModel;
use App\Models\WargaModel;

class Layanan extends BaseController
{
    protected $suratModel;
    protected $wargaModel;

    public function __construct()
    {
        $this->suratModel = new SuratModel();
        $this->wargaModel = new WargaModel();
    }

    public function index($slug)
    {
        $data['rt'] = $this->resolveTenant($slug);

        return $this->load_view('layanan', $data);
    }

    public function store($slug)
    {
        $rt = $this->resolveTenant($slug);

        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $nik = $this->wargaModel->nik($this->request->getPost('nik'));

        if (!empty($nik)) {
            if ($this->request->getPost('pin') != $nik->kode_rumah) {
                setFlashData('error', 'Data NIK atau PIN Anda salah!');
                return redirect()->to(back());
            }
        } else {
            setFlashData('error', 'Data NIK tidak terdaftar!');
            return redirect()->to(back());
        }

        $data = [
            'id_warga' => $nik->id_warga,
            'maksut'   => $this->request->getPost('maksut'),
            'perlu'    => $this->request->getPost('perlu'),
            'lampiran' => $this->request->getPost('lampiran'),
            'id_rt'    => current_rt_id(),
        ];

        $this->suratModel->insert($data);

        return redirect()->to($rt->slug . '/layanan/sukses');
    }

    public function sukses($slug)
    {
        $data['rt'] = $this->resolveTenant($slug);

        return $this->load_view('layanan_sukses', $data);
    }

    public function legacyIndex()
    {
        $rt = model(RtModel::class)->find(1);

        return redirect()->to('/' . ($rt->slug ?? 'rt29') . '/layanan', null, 301);
    }
}
```

- [ ] **Step 6: Create the chooser view and update tenant links in public views**

Create `app/Views/pilih_rt.php`:

```php
<section class="page-section">
    <div class="container px-4 px-lg-5 mt-5">
        <h2 class="text-center mt-0">Pilih RT</h2>
        <hr class="divider" />
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <?php foreach ($rts as $item): ?>
                <div class="col-lg-3 col-md-4 text-center">
                    <div class="mt-3">
                        <a class="btn btn-primary btn-xl" href="<?= base_url($item->slug) ?>"><?= esc($item->nama) ?></a>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
```

Edit the tenant-dependent links (all these views now receive `$rt` from the controller):

- `app/Views/beranda.php:48`: change `base_url('berita/' . $berita->slug)` → `base_url($rt->slug . '/berita/' . $berita->slug)`
- `app/Views/layanan.php:37`: change `form_open('layanan/store')` → `form_open($rt->slug . '/layanan/store')`
- `app/Views/layanan_sukses.php:11`: change `href="<?= base_url() ?>"` → `href="<?= base_url($rt->slug) ?>"`
- `app/Views/includes/nav-white.php:4`: change `href="<?= base_url() ?>"` → `href="<?= base_url(isset($rt) && $rt ? $rt->slug : '') ?>"`

- [ ] **Step 7: Tenant-aware QR URLs in `Admin\Alamat`**

In `app/Controllers/Admin/Alamat.php`, both `createQrCodeImage()` call sites (lines 48 and 90) currently pass `base_url('detail/' . $kode)`. Change both to:

```php
        $this->createQrCodeImage($kode, base_url(current_rt()->slug . '/detail/' . $kode));
```

- [ ] **Step 8: Run tests**

Run: `vendor/bin/phpunit tests/filters/PublicTenantRoutingTest.php`
Expected: PASS.

Run: `vendor/bin/phpunit`
Expected: PASS (existing `RouteFilterTest::testCsrfAppliesToPublicFormRoute` asserts `layanan/store` — update that assertion to the new registered pattern `([^/]+)/layanan/store`).

- [ ] **Step 9: Commit**

```bash
git add app/Config/Routes.php app/Controllers app/Views tests/filters/PublicTenantRoutingTest.php tests/filters/RouteFilterTest.php
git commit -m "feat: slug-prefixed public tenant routes with legacy redirects"
```

---

### Task 8: `Admin\Tenants` (superadmin CRUD) + RT switcher + header/sidebar UI

**Files:**
- Create: `app/Controllers/Admin/Tenants.php`, `app/Views/admin/tenants.php`, `app/Views/admin/ubah_rt.php`, `app/Views/admin/ubah_rw.php`
- Modify: `app/Config/Routes.php` (inside admin group), `app/Views/layouts/header.php:67`, `app/Views/layouts/sidebar_menu.php` (before the Users `<li>`)
- Test: `tests/filters/RouteFilterTest.php` (add method)

**Interfaces:**
- Consumes: `RtModel`, `RwModel`, `current_rt()`, session key `tenant_rt_id`.
- Produces: routes `admin/tenants` (GET index), `admin/tenants/rw/store`, `admin/tenants/rt/store` (POST), `admin/tenants/rt/edit|update/(:num)`, `admin/tenants/rw/edit|update/(:num)`, `admin/tenants/switch-rt/(:num)` (GET) — all `group:superadmin`.

- [ ] **Step 1: Add failing wiring test to `tests/filters/RouteFilterTest.php`**

```php
    public function testTenantManagementRequiresSuperadminGroup(): void
    {
        $this->assertFilter('admin/tenants', 'before', 'group:superadmin');
        $this->assertFilter('admin/tenants/switch-rt/([0-9]+)', 'before', 'group:superadmin');

        $this->collection->setHTTPVerb('post');
        $this->assertFilter('admin/tenants/rt/store', 'before', 'group:superadmin');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php --filter testTenantManagementRequiresSuperadminGroup`
Expected: FAIL — route not found.

- [ ] **Step 3: Add the routes**

Inside the `admin` group in `app/Config/Routes.php`, after the `users` sub-group, add:

```php
    // Tenant provisioning - superadmin only
    $routes->group('tenants', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Tenants::index');
        $routes->post('rw/store', 'Admin\Tenants::storeRw');
        $routes->post('rt/store', 'Admin\Tenants::storeRt');
        $routes->get('rt/edit/(:num)', 'Admin\Tenants::editRt/$1');
        $routes->post('rt/update/(:num)', 'Admin\Tenants::updateRt/$1');
        $routes->get('rw/edit/(:num)', 'Admin\Tenants::editRw/$1');
        $routes->post('rw/update/(:num)', 'Admin\Tenants::updateRw/$1');
        $routes->get('switch-rt/(:num)', 'Admin\Tenants::switchRt/$1');
    });
```

- [ ] **Step 4: Write the controller**

Create `app/Controllers/Admin/Tenants.php`:

```php
<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\RwModel;

/**
 * Tenant provisioning (superadmin only - enforced by the
 * group:superadmin route filter, do not relax it).
 */
class Tenants extends BaseController
{
    protected $rtModel;
    protected $rwModel;

    public function __construct()
    {
        $this->rtModel = new RtModel();
        $this->rwModel = new RwModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola RT/RW';
        $data['rws'] = $this->rwModel->orderBy('nama')->findAll();
        $data['rts'] = $this->rtModel
            ->select('rt.*, rw.nama nama_rw')
            ->join('rw', 'rw.id_rw = rt.id_rw')
            ->orderBy('rw.nama, rt.nama')
            ->findAll();

        return $this->loadViews('admin/tenants', $this->global, $data);
    }

    public function storeRw()
    {
        $nama = trim((string) $this->request->getPost('nama'));
        $slug = url_title(trim((string) $this->request->getPost('slug')), '-', true);

        if ($nama === '' || $slug === '') {
            setFlashData('error', 'Nama dan slug RW wajib diisi!');
            return redirect()->to('admin/tenants');
        }

        if ($this->rwModel->where('slug', $slug)->countAllResults() > 0) {
            setFlashData('error', 'Slug RW sudah dipakai!');
            return redirect()->to('admin/tenants');
        }

        $this->rwModel->insert(['nama' => $nama, 'slug' => $slug]);
        setFlashData('success', 'Data RW berhasil di tambahkan!');

        return redirect()->to('admin/tenants');
    }

    public function storeRt()
    {
        $idRw = (int) $this->request->getPost('id_rw');
        $nama = trim((string) $this->request->getPost('nama'));
        $slug = url_title(trim((string) $this->request->getPost('slug')), '-', true);

        if ($idRw === 0 || $nama === '' || $slug === '') {
            setFlashData('error', 'RW, nama, dan slug RT wajib diisi!');
            return redirect()->to('admin/tenants');
        }

        if ($this->rtModel->where('slug', $slug)->countAllResults() > 0) {
            setFlashData('error', 'Slug RT sudah dipakai!');
            return redirect()->to('admin/tenants');
        }

        $this->rtModel->insert(['id_rw' => $idRw, 'nama' => $nama, 'slug' => $slug]);
        setFlashData('success', 'Data RT berhasil di tambahkan!');

        return redirect()->to('admin/tenants');
    }

    public function editRt($id)
    {
        $this->global['pageTitle'] = 'Ubah RT';
        $data['rt']  = $this->rtModel->find($id);
        $data['rws'] = $this->rwModel->orderBy('nama')->findAll();

        if ($data['rt'] === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_rt', $this->global, $data);
    }

    public function updateRt($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $slug = url_title(trim((string) $this->request->getPost('slug')), '-', true);

        if ($this->rtModel->where('slug', $slug)->where('id_rt !=', $id)->countAllResults() > 0) {
            setFlashData('error', 'Slug RT sudah dipakai!');
            return redirect()->to(back());
        }

        $this->rtModel->update($id, [
            'id_rw'    => (int) $this->request->getPost('id_rw'),
            'nama'     => trim((string) $this->request->getPost('nama')),
            'slug'     => $slug,
            'is_aktif' => (int) $this->request->getPost('is_aktif'),
        ]);
        setFlashData('success', 'Data RT berhasil di diubah!');

        return redirect()->to('admin/tenants');
    }

    public function editRw($id)
    {
        $this->global['pageTitle'] = 'Ubah RW';
        $data['rw'] = $this->rwModel->find($id);

        if ($data['rw'] === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_rw', $this->global, $data);
    }

    public function updateRw($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $slug = url_title(trim((string) $this->request->getPost('slug')), '-', true);

        if ($this->rwModel->where('slug', $slug)->where('id_rw !=', $id)->countAllResults() > 0) {
            setFlashData('error', 'Slug RW sudah dipakai!');
            return redirect()->to(back());
        }

        $this->rwModel->update($id, [
            'nama'     => trim((string) $this->request->getPost('nama')),
            'slug'     => $slug,
            'is_aktif' => (int) $this->request->getPost('is_aktif'),
        ]);
        setFlashData('success', 'Data RW berhasil di diubah!');

        return redirect()->to('admin/tenants');
    }

    /**
     * Superadmin's active-RT switcher (header dropdown).
     */
    public function switchRt($id)
    {
        $rt = $this->rtModel->find($id);

        if ($rt === null || (int) $rt->is_aktif !== 1) {
            setFlashData('error', 'RT tidak ditemukan!');
            return redirect()->to('admin/dashboard');
        }

        session()->set('tenant_rt_id', (int) $rt->id_rt);
        setFlashData('success', 'Beralih ke ' . $rt->nama);

        return redirect()->to('admin/dashboard');
    }
}
```

- [ ] **Step 5: Create the views**

Create `app/Views/admin/tenants.php`:

```php
<div class="container-fluid">
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header"><strong>Daftar RW</strong></div>
				<div class="card-body">
					<?= form_open('admin/tenants/rw/store') ?>
						<div class="form-row align-items-end">
							<div class="col"><input type="text" name="nama" class="form-control" placeholder="Nama RW" required></div>
							<div class="col"><input type="text" name="slug" class="form-control" placeholder="slug-rw" required></div>
							<div class="col-auto"><button type="submit" class="btn btn-primary">Tambah RW</button></div>
						</div>
					<?= form_close() ?>
					<table class="table table-bordered table-striped mt-3">
						<thead><tr><th width="1">No.</th><th>Nama</th><th>Slug</th><th>Aktif</th><th>Action</th></tr></thead>
						<tbody>
							<?php foreach ($rws as $i => $rw): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($rw->nama) ?></td>
								<td><?= esc($rw->slug) ?></td>
								<td><?= $rw->is_aktif ? 'Ya' : 'Tidak' ?></td>
								<td><a href="<?= base_url('admin/tenants/rw/edit/' . $rw->id_rw) ?>"><i class="far fa-edit"></i></a></td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card">
				<div class="card-header"><strong>Daftar RT</strong></div>
				<div class="card-body">
					<?= form_open('admin/tenants/rt/store') ?>
						<div class="form-row align-items-end">
							<div class="col">
								<select name="id_rw" class="form-control" required>
									<option value="">-- RW --</option>
									<?php foreach ($rws as $rw): ?>
										<option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
									<?php endforeach ?>
								</select>
							</div>
							<div class="col"><input type="text" name="nama" class="form-control" placeholder="Nama RT" required></div>
							<div class="col"><input type="text" name="slug" class="form-control" placeholder="slug-rt" required></div>
							<div class="col-auto"><button type="submit" class="btn btn-primary">Tambah RT</button></div>
						</div>
					<?= form_close() ?>
					<table class="table table-bordered table-striped mt-3">
						<thead><tr><th width="1">No.</th><th>Nama</th><th>RW</th><th>Slug</th><th>Aktif</th><th>Action</th></tr></thead>
						<tbody>
							<?php foreach ($rts as $i => $rt): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($rt->nama) ?></td>
								<td><?= esc($rt->nama_rw) ?></td>
								<td><?= esc($rt->slug) ?></td>
								<td><?= $rt->is_aktif ? 'Ya' : 'Tidak' ?></td>
								<td><a href="<?= base_url('admin/tenants/rt/edit/' . $rt->id_rt) ?>"><i class="far fa-edit"></i></a></td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
```

Create `app/Views/admin/ubah_rt.php`:

```php
<div class="container-fluid">
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<?= form_open('admin/tenants/rt/update/' . $rt->id_rt) ?>
						<div class="form-group">
							<label>RW</label>
							<select name="id_rw" class="form-control" required>
								<?php foreach ($rws as $rw): ?>
									<option value="<?= $rw->id_rw ?>" <?= $rw->id_rw == $rt->id_rw ? 'selected' : '' ?>><?= esc($rw->nama) ?></option>
								<?php endforeach ?>
							</select>
						</div>
						<div class="form-group">
							<label>Nama RT</label>
							<input type="text" name="nama" class="form-control" value="<?= esc($rt->nama) ?>" required>
						</div>
						<div class="form-group">
							<label>Slug (URL publik)</label>
							<input type="text" name="slug" class="form-control" value="<?= esc($rt->slug) ?>" required>
						</div>
						<div class="form-group">
							<label>Status</label>
							<select name="is_aktif" class="form-control">
								<option value="1" <?= $rt->is_aktif ? 'selected' : '' ?>>Aktif</option>
								<option value="0" <?= !$rt->is_aktif ? 'selected' : '' ?>>Nonaktif</option>
							</select>
						</div>
						<a href="<?= base_url('admin/tenants') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					<?= form_close() ?>
				</div>
			</div>
		</div>
	</div>
</div>
```

Create `app/Views/admin/ubah_rw.php`:

```php
<div class="container-fluid">
	<div class="row">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<?= form_open('admin/tenants/rw/update/' . $rw->id_rw) ?>
						<div class="form-group">
							<label>Nama RW</label>
							<input type="text" name="nama" class="form-control" value="<?= esc($rw->nama) ?>" required>
						</div>
						<div class="form-group">
							<label>Slug</label>
							<input type="text" name="slug" class="form-control" value="<?= esc($rw->slug) ?>" required>
						</div>
						<div class="form-group">
							<label>Status</label>
							<select name="is_aktif" class="form-control">
								<option value="1" <?= $rw->is_aktif ? 'selected' : '' ?>>Aktif</option>
								<option value="0" <?= !$rw->is_aktif ? 'selected' : '' ?>>Nonaktif</option>
							</select>
						</div>
						<a href="<?= base_url('admin/tenants') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					<?= form_close() ?>
				</div>
			</div>
		</div>
	</div>
</div>
```

- [ ] **Step 6: Header RT indicator/switcher and sidebar menu items**

In `app/Views/layouts/header.php`, inside `<ul class="navbar-nav ml-auto">` (line 67), **before** the existing user dropdown `<li>`, add:

```php
                <?php $activeRt = current_rt(); ?>
                <?php if (auth()->user() && auth()->user()->inGroup('superadmin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-toggle="dropdown" href="#">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= $activeRt ? esc($activeRt->nama) : 'Pilih RT' ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php foreach (model(\App\Models\RtModel::class)->aktif() as $rtItem): ?>
                                <a href="<?= base_url('admin/tenants/switch-rt/' . $rtItem->id_rt) ?>" class="dropdown-item">
                                    <?= esc($rtItem->nama) ?>
                                </a>
                            <?php endforeach ?>
                        </div>
                    </li>
                <?php elseif ($activeRt): ?>
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-map-marker-alt"></i> <?= esc($activeRt->nama) ?></span>
                    </li>
                <?php endif ?>
```

In `app/Views/layouts/sidebar_menu.php`, immediately **before** the Users `<li class="nav-item">` (the one linking `admin/users`), add:

```php
        <?php if (auth()->user() && auth()->user()->inGroup('superadmin')): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/tenants') ?>" class="nav-link">
                <i class="nav-icon fas fa-sitemap"></i>
                <p>
                    Kelola RT/RW
                </p>
            </a>
        </li>
        <?php endif ?>

        <?php if (auth()->user() && (auth()->user()->inGroup('rw') || auth()->user()->inGroup('superadmin'))): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/rekap') ?>" class="nav-link">
                <i class="nav-icon fas fa-chart-bar"></i>
                <p>
                    Rekap RW
                </p>
            </a>
        </li>
        <?php endif ?>
```

- [ ] **Step 7: Run tests**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Controllers/Admin/Tenants.php app/Views/admin/tenants.php app/Views/admin/ubah_rt.php app/Views/admin/ubah_rw.php app/Config/Routes.php app/Views/layouts tests/filters/RouteFilterTest.php
git commit -m "feat: superadmin tenant management and active-RT switcher"
```

---

### Task 9: Tenant assignment in `Admin\Users`

**Files:**
- Modify: `app/Controllers/Admin/Users.php`, `app/Views/admin/tambah_user.php`, `app/Views/admin/ubah_user.php`

**Interfaces:**
- Consumes: `RtModel::aktif()`, `RwModel::aktif()`, `current_rt_id()`, groups `admin`/`rw`/`superadmin`.
- Produces: user provisioning rules — superadmin may create any account type for any tenant; an RT admin may only create `admin` accounts for their own RT; `Users::index` lists only same-RT users for non-superadmin.

- [ ] **Step 1: Update `Users::index` to scope the listing**

Replace the body of `index()` in `app/Controllers/Admin/Users.php`:

```php
    public function index()
    {
        $this->global['pageTitle'] = 'Kelola User';

        $provider = auth()->getProvider();

        if (auth()->user()->inGroup('superadmin')) {
            $data['users'] = $provider->findAll();
        } else {
            // RT admins only manage accounts of their own RT.
            $data['users'] = $provider->where('id_rt', current_rt_id())->findAll();
        }

        return $this->loadViews('admin/users', $this->global, $data);
    }
```

- [ ] **Step 2: Update `Users::add` to pass tenant lists**

```php
    public function add()
    {
        $this->global['pageTitle'] = 'Tambah User';
        $data['rts'] = model(\App\Models\RtModel::class)->aktif();
        $data['rws'] = model(\App\Models\RwModel::class)->aktif();

        return $this->loadViews('admin/tambah_user', $this->global, $data);
    }
```

- [ ] **Step 3: Update `Users::store` to assign group + tenant**

Replace everything in `store()` from `$users->save($user);` (line 58) to the end of the method with:

```php
        $users->save($user);

        $user = $users->findById($users->getInsertID());

        $isSuperadmin = auth()->user()->inGroup('superadmin');
        $group        = $this->request->getPost('group') ?: 'admin';
        $idRt         = (int) $this->request->getPost('id_rt') ?: null;
        $idRw         = (int) $this->request->getPost('id_rw') ?: null;

        if (! $isSuperadmin) {
            // RT admins can only create fellow admins for their own RT.
            $group = 'admin';
            $idRt  = current_rt_id();
            $idRw  = null;
        }

        if (! in_array($group, ['admin', 'rw', 'superadmin'], true)) {
            $group = 'admin';
        }

        $user->addGroup($group);

        \Config\Database::connect()->table('users')->where('id', $user->id)->update([
            'id_rt' => $group === 'admin' ? $idRt : null,
            'id_rw' => $group === 'rw' ? $idRw : null,
        ]);

        setFlashData('success', 'Data user berhasil ditambahkan!');
        return redirect()->to('admin/users');
```

Also add validation right after reading the POST fields at the top of `store()` (after the `$password != $cpassword` check):

```php
        if (auth()->user()->inGroup('superadmin')) {
            $group = $this->request->getPost('group');
            if ($group === 'admin' && empty($this->request->getPost('id_rt'))) {
                setFlashData('error', 'Akun admin RT harus dihubungkan ke sebuah RT!');
                return redirect()->to(back());
            }
            if ($group === 'rw' && empty($this->request->getPost('id_rw'))) {
                setFlashData('error', 'Akun RW harus dihubungkan ke sebuah RW!');
                return redirect()->to(back());
            }
        }
```

- [ ] **Step 4: Add the form fields to `app/Views/admin/tambah_user.php`**

Inside the form, before the submit/back buttons (line ~46), add (superadmin-only fields):

```php
						<?php if (auth()->user()->inGroup('superadmin')): ?>
							<div class="form-group">
								<label>Jenis Akun</label>
								<select name="group" class="form-control" id="group-select">
									<option value="admin">Admin RT</option>
									<option value="rw">Pengurus RW (read-only)</option>
									<option value="superadmin">Superadmin</option>
								</select>
							</div>
							<div class="form-group" id="rt-select">
								<label>RT</label>
								<select name="id_rt" class="form-control">
									<option value="">-- Pilih RT --</option>
									<?php foreach ($rts as $rt): ?>
										<option value="<?= $rt->id_rt ?>"><?= esc($rt->nama) ?></option>
									<?php endforeach ?>
								</select>
							</div>
							<div class="form-group" id="rw-select" style="display:none">
								<label>RW</label>
								<select name="id_rw" class="form-control">
									<option value="">-- Pilih RW --</option>
									<?php foreach ($rws as $rw): ?>
										<option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
									<?php endforeach ?>
								</select>
							</div>
							<script>
								document.getElementById('group-select').addEventListener('change', function () {
									document.getElementById('rt-select').style.display = this.value === 'admin' ? '' : 'none';
									document.getElementById('rw-select').style.display = this.value === 'rw' ? '' : 'none';
								});
							</script>
						<?php endif ?>
```

- [ ] **Step 5: Show (read-only) tenant info in `app/Views/admin/ubah_user.php`**

Before the buttons (line ~46), add:

```php
						<?php if (! empty($user->id_rt) && ($rt = model(\App\Models\RtModel::class)->find($user->id_rt))): ?>
							<div class="form-group">
								<label>RT</label>
								<input type="text" class="form-control" value="<?= esc($rt->nama) ?>" disabled>
							</div>
						<?php endif ?>
```

(Re-assigning an existing account to another tenant is out of scope — delete + recreate covers it; note this in the commit message body if desired.)

- [ ] **Step 6: Run the full suite and commit**

Run: `vendor/bin/phpunit`
Expected: PASS.

```bash
git add app/Controllers/Admin/Users.php app/Views/admin/tambah_user.php app/Views/admin/ubah_user.php
git commit -m "feat: assign Shield group and tenant when provisioning users"
```

---

### Task 10: `Admin\Rekap` — RW read-only recap

**Files:**
- Create: `app/Controllers/Admin/Rekap.php`, `app/Views/admin/rekap.php`, `app/Views/admin/rekap_warga.php`
- Modify: `app/Config/Routes.php` (inside admin group), `app/Models/RtModel.php` (add `rekap()`)
- Test: `tests/filters/RouteFilterTest.php` (add method), `tests/database/TenantIsolationTest.php` (add method)

**Interfaces:**
- Consumes: `current_rw_id()`, `RtModel`, `WargaModel::all()` (tenant-scoped), `tenant_set_rt()`.
- Produces: routes `admin/rekap` and `admin/rekap/warga/(:num)` with filter `group:rw,superadmin`; `RtModel::rekap(?int $idRw): array` returning rt rows with `jml_warga`, `jml_kk`, `jml_l`, `jml_p`, `jml_surat` counts (all RTs when `$idRw` is null — the superadmin case).

- [ ] **Step 1: Add failing tests**

To `tests/filters/RouteFilterTest.php`:

```php
    public function testRekapRoutesAllowRwAndSuperadminOnly(): void
    {
        $this->assertFilter('admin/rekap', 'before', 'group:rw,superadmin');
        $this->assertFilter('admin/rekap/warga/([0-9]+)', 'before', 'group:rw,superadmin');
    }
```

To `tests/database/TenantIsolationTest.php`:

```php
    public function testRekapOnlyCountsRtsOfTheGivenRw(): void
    {
        $db = Database::connect();

        // A second RW with its own RT and one warga.
        if ($db->table('rw')->where('slug', 'rw-lain')->countAllResults() === 0) {
            $db->table('rw')->insert(['nama' => 'RW Lain', 'slug' => 'rw-lain']);
        }
        $idRwLain = (int) $db->table('rw')->where('slug', 'rw-lain')->get()->getRow()->id_rw;

        if ($db->table('rt')->where('slug', 'rt99-test')->countAllResults() === 0) {
            $db->table('rt')->insert(['id_rw' => $idRwLain, 'nama' => 'RT 99 Test', 'slug' => 'rt99-test']);
        }

        $idRwUtama = (int) $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        $rekap = (new \App\Models\RtModel())->rekap($idRwUtama);
        $slugs = array_column($rekap, 'slug');

        $this->assertContains('rt29', $slugs);
        $this->assertContains('rt30-test', $slugs);
        $this->assertNotContains('rt99-test', $slugs, 'rekap leaked an RT from another RW');

        $rekapAll = (new \App\Models\RtModel())->rekap(null);
        $this->assertContains('rt99-test', array_column($rekapAll, 'slug'), 'superadmin rekap must include all RTs');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php tests/database/TenantIsolationTest.php`
Expected: FAIL — rekap route missing; `rekap()` undefined.

- [ ] **Step 3: Add `rekap()` to `RtModel`**

```php
    /**
     * Per-RT aggregate counts for the RW recap screen. Pass null to
     * include every RT (superadmin view).
     *
     * @return list<object>
     */
    public function rekap(?int $idRw): array
    {
        $builder = $this->db->table('rt')
            ->select("rt.id_rt, rt.nama, rt.slug,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.status_warga = 1) jml_warga,
                (SELECT COUNT(DISTINCT w.no_kk) FROM warga w WHERE w.id_rt = rt.id_rt AND w.status_warga = 1) jml_kk,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.jenis_kelamin = 'L' AND w.status_warga = 1) jml_l,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.jenis_kelamin = 'P' AND w.status_warga = 1) jml_p,
                (SELECT COUNT(*) FROM surat s WHERE s.id_rt = rt.id_rt) jml_surat", false)
            ->where('rt.is_aktif', 1)
            ->orderBy('rt.nama');

        if ($idRw !== null) {
            $builder->where('rt.id_rw', $idRw);
        }

        return $builder->get()->getResult();
    }
```

- [ ] **Step 4: Routes + controller + views**

Inside the `admin` group in `app/Config/Routes.php` (after the `tenants` sub-group):

```php
    // RW recap - read-only, rw group (and superadmin)
    $routes->group('rekap', ['filter' => 'group:rw,superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Rekap::index');
        $routes->get('warga/(:num)', 'Admin\Rekap::warga/$1');
    });
```

Create `app/Controllers/Admin/Rekap.php`:

```php
<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\WargaModel;

/**
 * Read-only recap for RW accounts: aggregate numbers per RT plus a
 * read-only warga drill-down. No mutation routes exist on purpose.
 */
class Rekap extends BaseController
{
    protected $rtModel;

    public function __construct()
    {
        $this->rtModel = new RtModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Rekap RW';

        // RW accounts see their own RTs; superadmin sees all.
        $data['rekap'] = $this->rtModel->rekap(current_rw_id());

        return $this->loadViews('admin/rekap', $this->global, $data);
    }

    public function warga($idRt)
    {
        $rt = $this->rtModel->find($idRt);

        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // RW may only open RTs inside their RW.
        if (current_rw_id() !== null && (int) $rt->id_rw !== current_rw_id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        tenant_set_rt((int) $rt->id_rt);

        $this->global['pageTitle'] = 'Warga ' . $rt->nama;
        $data['rt']     = $rt;
        $data['wargas'] = (new WargaModel())->all();

        return $this->loadViews('admin/rekap_warga', $this->global, $data);
    }
}
```

Create `app/Views/admin/rekap.php`:

```php
<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>RT</th>
								<th>Jumlah Warga</th>
								<th>Jumlah KK</th>
								<th>Laki-laki</th>
								<th>Perempuan</th>
								<th>Surat</th>
								<th>Detail</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rekap as $i => $rt): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($rt->nama) ?></td>
								<td><?= $rt->jml_warga ?></td>
								<td><?= $rt->jml_kk ?></td>
								<td><?= $rt->jml_l ?></td>
								<td><?= $rt->jml_p ?></td>
								<td><?= $rt->jml_surat ?></td>
								<td>
									<a href="<?= base_url('admin/rekap/warga/' . $rt->id_rt) ?>">
										<i class="far fa-eye"></i> Lihat Warga
									</a>
								</td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
```

Create `app/Views/admin/rekap_warga.php` (read-only warga listing — no add/edit/export buttons):

```php
<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?= base_url('admin/rekap') ?>" class="btn btn-light"><i class="fa fa-arrow-left"></i> Kembali ke Rekap</a></div>
	</div>
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Nama</th>
								<th>NIK</th>
								<th>No. KK</th>
								<th>Alamat</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wargas as $i => $warga): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($warga->nama_warga) ?></td>
								<td><?= esc($warga->nik) ?></td>
								<td><?= esc($warga->no_kk) ?></td>
								<td><?= esc($warga->alamat) ?></td>
								<td><?= esc($warga->status_keluarga) ?></td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/phpunit tests/filters/RouteFilterTest.php tests/database/TenantIsolationTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Controllers/Admin/Rekap.php app/Views/admin/rekap.php app/Views/admin/rekap_warga.php app/Config/Routes.php app/Models/RtModel.php tests
git commit -m "feat: read-only RW recap over member RTs"
```

---

### Task 11: Docs + full verification

**Files:**
- Modify: `CLAUDE.md` (Architecture section)

- [ ] **Step 1: Document the tenancy architecture in `CLAUDE.md`**

Add this subsection under "### Auth: CodeIgniter Shield, not the legacy `user` table":

```markdown
### Multi-tenancy: one DB, `id_rt` on every tenant table

The app is multi-tenant (hierarchy: `rw` → `rt`; RT 29 is tenant `id_rt = 1`). Tenant-owned tables (`warga`, `alamat`, `berita`, `surat`, `inventaris`, `dawis`, `ketua`) carry an `id_rt` column; lookup tables (`pekerjaan`, `status_*`, `layanan`) are shared. Isolation is enforced twice: the `tenant` filter (`App\Filters\TenantFilter`) establishes the session tenant context for `admin/*`, and **every model query adds `->where('<table>.id_rt', current_rt_id())`** — new model methods must do the same, and inserts must set `'id_rt' => current_rt_id()`. The `tenant` helper (autoloaded via `Config\Autoload::$helpers`) provides `current_rt_id()`/`current_rt()`/`current_rw_id()`/`tenant_set_rt()`. Public pages are slug-prefixed (`/rt29/...`, resolved via `BaseController::resolveTenant()`); legacy unprefixed URLs 301-redirect so printed QR codes keep working. Account model: superadmin (no tenant, header dropdown switches active RT), group `admin` + `users.id_rt` (RT admin), group `rw` + `users.id_rw` (read-only recap at `admin/rekap`). Tenant provisioning is superadmin-only (`admin/tenants`).
```

- [ ] **Step 2: Run the complete test suite**

Run: `vendor/bin/phpunit`
Expected: PASS — zero failures across unit, config, filters, and database suites.

- [ ] **Step 3: Smoke-check routes**

Run: `php spark routes`
Expected: slug routes listed last; `admin/tenants/*` shows `group:superadmin`; `admin/rekap/*` shows `group:rw,superadmin`; `admin/*` shows `session tenant`.

- [ ] **Step 4: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: document multi-tenancy architecture"
```
