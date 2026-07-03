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
        // fieldExists() answers from the connection's metadata cache;
        // earlier migration steps in the same process may have cached
        // stale field lists (this matters when the test suite migrates
        // down/up repeatedly on one connection).
        $this->db->resetDataCache();

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
        $this->db->resetDataCache();

        foreach ($this->tables as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('id_rt', $table)) {
                $this->forge->dropColumn($table, 'id_rt');
            }
        }
    }
}
