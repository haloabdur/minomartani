<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateWargaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_warga'           => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_alamat'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'alamat_lengkap'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'no_kk'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'nama_warga'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'nik'                => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'jenis_kelamin'      => ['type' => 'CHAR', 'constraint' => 5, 'null' => true],
            'tempat_lahir'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'tanggal_lahir'      => ['type' => 'DATE', 'null' => false],
            'gol_darah'          => ['type' => 'CHAR', 'constraint' => 10, 'null' => true],
            'agama'              => ['type' => 'CHAR', 'constraint' => 10, 'null' => true],
            'pendidikan'         => ['type' => 'CHAR', 'constraint' => 10, 'null' => true],
            'id_pekerjaan'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'status_kawin'       => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true, 'comment' => '0=tidak;1=kawin;2=cerai hidup;3=cerai mati'],
            'tanggal_kawin'      => ['type' => 'DATE', 'null' => true],
            'id_status_keluarga' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true],
            'ayah'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ibu'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'no_hp'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sumber_air'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'id_status_penduduk' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'status_warga'       => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'is_hidup'           => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'id_user'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'         => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'          => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_warga');
        $this->forge->addUniqueKey('nik');
        $this->forge->createTable('warga', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('warga', true);
    }
}
