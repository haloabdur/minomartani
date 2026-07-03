<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSuratTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_surat'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_warga'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'maksut'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'perlu'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'lampiran'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_surat' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 0],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            // Live schema's default is the legacy zero-date
            // '0000-00-00 00:00:00', which strict sql_mode (the default
            // on a fresh connection/DB) rejects as an invalid TIMESTAMP
            // default. Using NULL here is the strict-mode-safe
            // equivalent - same nullable-until-updated behavior, same
            // pattern already used for warga.timestamp and
            // status_keluarga.timestamp above.
            'timestamp'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_surat');
        $this->forge->createTable('surat', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('surat', true);
    }
}
