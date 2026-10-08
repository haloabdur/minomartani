<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Names printed in the signature block of the Surat Keterangan sheet:
 * Dukuh and Ketua RW (Ketua RT comes from the existing `ketua` table).
 * Nullable - when empty the signature line is simply left blank, like
 * the paper form.
 *
 * Idempotent: fieldExists() guards.
 */
class AddPejabatToRt extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('nama_dukuh', 'rt')) {
            $this->forge->addColumn('rt', [
                'nama_dukuh' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }

        if (! $this->db->fieldExists('nama_ketua_rw', 'rt')) {
            $this->forge->addColumn('rt', [
                'nama_ketua_rw' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        foreach (['nama_dukuh', 'nama_ketua_rw'] as $column) {
            if ($this->db->fieldExists($column, 'rt')) {
                $this->forge->dropColumn('rt', $column);
            }
        }
    }
}
