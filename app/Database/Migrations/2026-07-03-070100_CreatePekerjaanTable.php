<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePekerjaanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pekerjaan'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_pekerjaan' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'timestamp'      => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_pekerjaan');
        $this->forge->createTable('pekerjaan', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('pekerjaan', true);
    }
}
