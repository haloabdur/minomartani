<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `ketua.foto_ketua` was VARCHAR(50), sized for local bare-filename
 * values. Admin\Ketua now stores the full R2 public URL returned by
 * R2Storage::upload() (e.g. "https://cdn.minomartani.com/ketua/rt29/
 * item-....webp"), which would be silently truncated at 50 chars. Same
 * fix as WidenBeritaFotoColumn. MODIFY without an explicit charset keeps
 * the table's existing latin1 default.
 */
class WidenKetuaFotoColumn extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('ketua', [
            'foto_ketua' => ['name' => 'foto_ketua', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('ketua', [
            'foto_ketua' => ['name' => 'foto_ketua', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }
}
