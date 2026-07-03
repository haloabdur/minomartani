<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBeritaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_berita'  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'judul'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'deskripsi'  => ['type' => 'TEXT', 'null' => false],
            'kategori'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'foto'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'lampiran'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sumber'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_status'  => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false],
            'created_by' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true],
            'timestamp'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_berita');
        $this->forge->createTable('berita', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('berita', true);
    }
}
