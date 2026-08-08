<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * RT/RW events that residents are checked in to (rapat RT, kerja bakti,
 * pertemuan gabungan RW, ...). Owned by exactly one of id_rt (RT-created)
 * or id_rw (RW-created, spans every RT under that RW) - enforced in the
 * model/controller, not the DB. Same ownership shape as
 * kesehatan_kegiatan, which this feature mirrors.
 */
class CreatePresensiAcaraTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_acara'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_acara'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tanggal_acara' => ['type' => 'DATE', 'null' => false],
            'tempat'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_rt'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_rw'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'catatan'       => ['type' => 'TEXT', 'null' => true],
            'id_user'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'    => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'     => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_acara');
        $this->forge->addKey('id_rt');
        $this->forge->addKey('id_rw');
        $this->forge->createTable('presensi_acara', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('presensi_acara', true);
    }
}
