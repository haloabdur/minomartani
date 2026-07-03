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
