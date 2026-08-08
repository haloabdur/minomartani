<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * One row per warga per presensi_acara. A row only exists once someone
 * has actually been marked - absence of a row means "belum dipresensi",
 * which is distinct from status = 'tidak' ("dicatat tidak hadir").
 *
 * id_rt is a denormalized copy of warga.id_rt at record time, kept so
 * this table follows the same tenant-isolation query pattern as other
 * tenant tables even though its parent acara may be RW-owned (mirrors
 * kesehatan_catatan).
 */
class CreatePresensiKehadiranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_presensi' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_acara'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_warga'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_rt'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false, 'default' => 'hadir', 'comment' => 'hadir|tidak'],
            'waktu'       => ['type' => 'DATETIME', 'null' => true],
            'id_user'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'   => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_presensi');
        $this->forge->addUniqueKey(['id_acara', 'id_warga']);
        $this->forge->addKey('id_warga');
        $this->forge->addKey('id_rt');
        $this->forge->createTable('presensi_kehadiran', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('presensi_kehadiran', true);
    }
}
