<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStatusTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_status_keluarga' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'timestamp'           => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_status_keluarga');
        $this->forge->createTable('status_keluarga', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);

        $this->forge->addField([
            'id_status_penduduk' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'label'               => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'timestamp'           => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_status_penduduk');
        $this->forge->createTable('status_penduduk', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('status_penduduk', true);
        $this->forge->dropTable('status_keluarga', true);
    }
}
