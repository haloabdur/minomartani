<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds public-facing profile fields to `rt` so each tenant's landing
 * page (beranda.php) can show its own address, about text, WhatsApp
 * contact, and hero photo instead of the hardcoded RT 29 copy.
 * Nullable, no backfill - views fall back to generic copy when unset.
 * Idempotent: fieldExists() guard, matching AddKodeRumahToAlamat.
 */
class AddProfileFieldsToRt extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('alamat', 'rt')) {
            $this->forge->addColumn('rt', [
                'alamat' => [
                    'type' => 'VARCHAR', 'constraint' => 255,
                    'null' => true, 'after' => 'nama',
                ],
            ]);
        }

        if (! $this->db->fieldExists('deskripsi', 'rt')) {
            $this->forge->addColumn('rt', [
                'deskripsi' => [
                    'type' => 'TEXT', 'null' => true, 'after' => 'alamat',
                ],
            ]);
        }

        if (! $this->db->fieldExists('no_wa', 'rt')) {
            $this->forge->addColumn('rt', [
                'no_wa' => [
                    'type' => 'VARCHAR', 'constraint' => 20,
                    'null' => true, 'after' => 'deskripsi',
                ],
            ]);
        }

        if (! $this->db->fieldExists('foto_hero', 'rt')) {
            $this->forge->addColumn('rt', [
                'foto_hero' => [
                    'type' => 'VARCHAR', 'constraint' => 255,
                    'null' => true, 'after' => 'no_wa',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        foreach (['foto_hero', 'no_wa', 'deskripsi', 'alamat'] as $column) {
            if ($this->db->fieldExists($column, 'rt')) {
                $this->forge->dropColumn('rt', $column);
            }
        }
    }
}
