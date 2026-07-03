<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Regression guard for app/Database/Migrations - runs the real
 * migrations against a real MySQL test DB (see phpunit.xml.dist) and
 * checks the resulting schema matches what the app expects.
 *
 * @internal
 */
final class MigrationsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    // Default is 'Tests\Support' (the framework's example migrations).
    // null runs migrations from every discovered namespace, including
    // our App migrations under test.
    protected $namespace = null;

    public function testWargaTableHasExpectedColumns(): void
    {
        $db     = Database::connect();
        $fields = array_map(static fn ($f) => $f->name, $db->getFieldData('warga'));

        foreach (['id_warga', 'id_alamat', 'no_kk', 'nama_warga', 'nik', 'id_pekerjaan', 'id_status_penduduk'] as $expected) {
            $this->assertContains($expected, $fields, "warga.{$expected} is missing");
        }
    }

    public function testAlamatTableHasExpectedColumns(): void
    {
        $db     = Database::connect();
        $fields = array_map(static fn ($f) => $f->name, $db->getFieldData('alamat'));

        $this->assertSame(['id_alamat', 'alamat', 'qrcode', 'timestamp', 'id_rt'], $fields);
    }

    public function testSuratTableHasExpectedColumns(): void
    {
        $db     = Database::connect();
        $fields = array_map(static fn ($f) => $f->name, $db->getFieldData('surat'));

        foreach (['id_surat', 'id_warga', 'maksut', 'perlu', 'status_surat'] as $expected) {
            $this->assertContains($expected, $fields, "surat.{$expected} is missing");
        }
    }

    /**
     * Regression guard for the FixInventarisPrimaryKey migration: the
     * live table used to have no PRIMARY KEY / AUTO_INCREMENT on `id`,
     * which silently broke Admin\Inventaris::store().
     */
    public function testInventarisIdIsPrimaryKey(): void
    {
        $db     = Database::connect();
        $fields = $db->getFieldData('inventaris');

        $idField = null;
        foreach ($fields as $field) {
            if ($field->name === 'id') {
                $idField = $field;
                break;
            }
        }

        $this->assertNotNull($idField, 'inventaris.id column is missing');
        $this->assertTrue((bool) $idField->primary_key, 'inventaris.id is not a PRIMARY KEY');
    }

    public function testDawisHasForeignKeyToWarga(): void
    {
        $db = Database::connect();

        $foreignKeys = $db->getForeignKeyData('dawis');
        $this->assertNotEmpty($foreignKeys, 'dawis table has no foreign keys defined');

        $referencesWarga = false;
        foreach ($foreignKeys as $fk) {
            if ($fk->foreign_table_name === 'warga') {
                $referencesWarga = true;
            }
        }
        $this->assertTrue($referencesWarga, 'dawis has no FK referencing warga');
    }
}
