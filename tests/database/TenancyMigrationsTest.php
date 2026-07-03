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

    public function testAllTenantDataTablesHaveIdRtColumn(): void
    {
        $db = Database::connect();
        // The migration itself calls fieldExists() before ADD COLUMN,
        // which caches the pre-alter field list on this connection.
        $db->resetDataCache();

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

    public function testUsersTableHasTenantColumns(): void
    {
        $db = Database::connect();
        $db->resetDataCache();

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

    public function testTenantSeedIsIdempotent(): void
    {
        $db = Database::connect();

        $migration = new \App\Database\Migrations\CreateTenantTables(Database::forge());
        $migration->up(); // second run must not duplicate seed rows

        $this->assertSame(1, $db->table('rt')->where('slug', 'rt29')->countAllResults());
        $this->assertSame(1, $db->table('rw')->where('slug', 'rw-minomartani')->countAllResults());
    }
}
