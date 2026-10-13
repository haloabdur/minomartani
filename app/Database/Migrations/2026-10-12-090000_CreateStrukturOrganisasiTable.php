<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Struktur organisasi RT dan RW. Satu baris = satu jabatan yang dipegang satu
 * warga, sehingga warga yang merangkap jabatan (di RT, di RW, atau keduanya)
 * muncul sebagai beberapa baris dengan id_warga yang sama.
 *
 * Kepemilikan dua level (pola presensi_acara / kesehatan_kegiatan):
 *   level = 'rt' -> id_rt terisi, id_rw NULL
 *   level = 'rw' -> id_rw terisi, id_rt NULL
 * id_atasan menunjuk baris lain pada level dan pemilik yang sama (NULL = akar
 * pohon). Tidak ada FK ke warga / self-reference (konsisten dengan relasi
 * tenant lain di DB ini, lihat DATABASE.md) - integritas ditegakkan di
 * StrukturOrganisasiModel.
 */
class CreateStrukturOrganisasiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_struktur' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'level'       => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => false],
            'id_rt'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_rw'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_warga'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'jabatan'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'id_atasan'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'urutan'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id_struktur');
        $this->forge->addKey('id_rt');
        $this->forge->addKey('id_rw');
        $this->forge->addKey('id_warga');
        $this->forge->addKey('id_atasan');
        $this->forge->createTable('struktur_organisasi', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('struktur_organisasi', true);
    }
}
