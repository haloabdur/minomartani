<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pendataan kucing warga. Satu warga bisa punya banyak kucing; kucing yang
 * tidak terdaftar dianggap kucing liar (kesepakatan rapat RT). Kucing yang
 * mati/hilang/pindah dihapus, jadi tidak ada kolom status.
 *
 * Tidak ada FK ke warga (konsisten dengan relasi tenant lain di DB ini,
 * lihat DATABASE.md) - integritas ditegakkan di query aplikasi.
 */
class CreateKucingTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kucing'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'id_rt'         => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'id_warga'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'warna_ciri'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jenis_kelamin' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'steril'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'vaksin'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id_kucing');
        $this->forge->addKey('id_rt');
        $this->forge->addKey('id_warga');
        $this->forge->createTable('kucing', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('kucing', true);
    }
}
