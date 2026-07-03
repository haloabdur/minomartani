<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDawisTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_dawis'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_warga'   => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nama_dawis' => ['type' => 'CHAR', 'constraint' => 10, 'null' => false],
            'timestamp'  => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id_dawis');
        $this->forge->addKey('id_warga');
        $this->forge->addForeignKey('id_warga', 'warga', 'id_warga', '', '', 'dawis_ibfk_1');
        $this->forge->createTable('dawis', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('dawis', true);
    }
}
