<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Papan Informasi: himbauan/tatib yang berlaku tetap per RT, ditampilkan
 * di beranda publik tenant. Beda dari `berita` (feed yang berganti terus)
 * - ini "aturan yang berlaku", jadi punya delete sungguhan di
 * Admin\PapanInformasi, bukan cuma draft/publish seperti Berita.
 *
 * Modern tenant-scoped table: id_rt dibake langsung (bukan retrofit lewat
 * AddTenantColumnToDataTables), utf8mb4 seperti kesehatan_kegiatan/
 * presensi_acara.
 */
class CreatePapanInformasiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_papan'   => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'judul'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'isi'        => ['type' => 'TEXT', 'null' => false],
            'lampiran'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_status'  => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 0],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'timestamp'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
            'id_rt'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id_papan');
        $this->forge->addKey('id_rt');
        $this->forge->createTable('papan_informasi', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('papan_informasi', true);
    }
}
