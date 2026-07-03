<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateKetuaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_ketua'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_ketua' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'mulai'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'selesai'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'foto_ketua' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'timestamp'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_ketua');
        $this->forge->createTable('ketua', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('ketua', true);
    }
}
