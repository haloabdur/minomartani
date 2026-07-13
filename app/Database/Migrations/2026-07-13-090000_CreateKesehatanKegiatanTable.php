<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Health check-up sessions (e.g. monthly Posyandu Lansia). Owned by
 * exactly one of id_rt (RT-created) or id_rw (RW-created, spans every
 * RT under that RW) - enforced in the model/controller, not the DB.
 */
class CreateKesehatanKegiatanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kegiatan'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_kegiatan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tanggal_kegiatan' => ['type' => 'DATE', 'null' => false],
            'kategori'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'lansia'],
            'id_rt'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_rw'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'id_user'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'       => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'        => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_kegiatan');
        $this->forge->addKey('id_rt');
        $this->forge->addKey('id_rw');
        $this->forge->createTable('kesehatan_kegiatan', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('kesehatan_kegiatan', true);
    }
}
