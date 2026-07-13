<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * One row per warga per kesehatan_kegiatan session. id_rt is a
 * denormalized copy of warga.id_rt at record time, kept so this table
 * follows the same tenant-isolation query pattern as other tenant
 * tables even though its parent kegiatan may be RW-owned.
 */
class CreateKesehatanCatatanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_catatan'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_kegiatan'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_warga'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_rt'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'tensi_sistol'   => ['type' => 'SMALLINT', 'constraint' => 5, 'null' => true],
            'tensi_diastol'  => ['type' => 'SMALLINT', 'constraint' => 5, 'null' => true],
            'berat_badan'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tinggi_badan'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'lingkar_perut'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'gula_darah'     => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'gula_darah_ket' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'puasa|sewaktu'],
            'kolesterol'     => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'asam_urat'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'id_user'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_catatan');
        $this->forge->addUniqueKey(['id_kegiatan', 'id_warga']);
        $this->forge->addKey('id_warga');
        $this->forge->addKey('id_rt');
        $this->forge->createTable('kesehatan_catatan', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('kesehatan_catatan', true);
    }
}
