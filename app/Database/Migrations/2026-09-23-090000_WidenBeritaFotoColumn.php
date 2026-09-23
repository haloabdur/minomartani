<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `berita.foto` was VARCHAR(50), sized for the old local bare-filename
 * values (e.g. "abc123.jpg"). R2Storage::upload() now returns a full
 * public URL (e.g. "https://cdn.minomartani.com/berita/item-....jpg"),
 * which is longer than 50 chars and would be silently truncated by MySQL
 * on insert. Widens to match `berita_foto.foto` (VARCHAR(255)).
 */
class WidenBeritaFotoColumn extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('berita', [
            'foto' => ['name' => 'foto', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('berita', [
            'foto' => ['name' => 'foto', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }
}
