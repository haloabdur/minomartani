<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Gallery images for a berita (1-5 per berita, enforced in
 * Admin\Berita, not at the DB level). `berita.foto` stays the single
 * "cover" pointer used everywhere a berita is shown as a thumbnail
 * (admin list, public beranda); this table holds every uploaded image,
 * shown together on the public detail page. Pre-existing berita rows
 * (single foto, no gallery rows) are left as-is - Admin\Berita::edit()
 * lazily materializes a legacy foto into a berita_foto row the first
 * time an admin opens that berita for editing.
 */
class CreateBeritaFotoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_berita_foto' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_berita'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'foto'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'urutan'         => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => false, 'default' => 1],
            'is_cover'       => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'id_rt'          => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'timestamp'      => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_berita_foto');
        $this->forge->addKey('id_berita');
        $this->forge->addKey('id_rt');
        $this->forge->addForeignKey('id_berita', 'berita', 'id_berita', '', 'CASCADE', 'berita_foto_ibfk_1');
        $this->forge->createTable('berita_foto', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('berita_foto', true);
    }
}
