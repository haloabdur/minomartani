<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInventarisTable extends Migration
{
    /**
     * Fresh installs get id as PRIMARY KEY + AUTO_INCREMENT from the start.
     * The live DB predates this and is fixed separately by
     * FixInventarisPrimaryKey (see next migration) since createTable()'s
     * ifNotExists guard skips this up() entirely when the table already
     * exists.
     */
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_barang' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'stok'        => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'foto'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('inventaris', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8', 'COLLATE' => 'utf8_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('inventaris', true);
    }
}
