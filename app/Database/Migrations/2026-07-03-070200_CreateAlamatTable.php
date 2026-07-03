<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAlamatTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_alamat' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'alamat'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'qrcode'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'timestamp' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_alamat');
        $this->forge->createTable('alamat', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('alamat', true);
    }
}
