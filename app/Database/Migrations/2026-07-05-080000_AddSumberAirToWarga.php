<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `sumber_air` to `warga`.
 *
 * CreateWargaTable declares this column, but its createTable() call
 * uses ifNotExists=true and production's `warga` table already existed
 * from the CI3 era, so that migration no-opped there and the column
 * was never actually created — causing "Unknown column 'sumber_air'
 * in 'SET'" on update. Idempotent: fieldExists() guard, matching
 * AddTenantColumnToDataTables.
 */
class AddSumberAirToWarga extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('sumber_air', 'warga')) {
            $this->forge->addColumn('warga', [
                'sumber_air' => [
                    'type' => 'VARCHAR', 'constraint' => 20,
                    'null' => true, 'after' => 'email',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('sumber_air', 'warga')) {
            $this->forge->dropColumn('warga', 'sumber_air');
        }
    }
}
