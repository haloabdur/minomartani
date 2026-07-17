<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `kode_rfid` to `warga` for the Kesehatan e-KTP RFID scan feature.
 *
 * The RFID reader used by admins at Posyandu Lansia reads the chip's
 * physical UID, not the printed NIK - the NIK stored on an e-KTP chip is
 * encrypted and only readable through a certified Dukcapil SDK, which
 * this app does not have access to. So a scan can only resolve to a
 * resident after that resident has been enrolled once (admin picks the
 * warga from a search list, the scanned UID is then saved here).
 *
 * Nullable + UNIQUE: existing residents have no card enrolled until an
 * admin links one via the "Kartu belum dikenali" flow on the kegiatan
 * kesehatan screen. MySQL allows multiple NULLs in a UNIQUE index, so
 * nullability doesn't weaken uniqueness for rows that do have a card.
 *
 * Idempotent: fieldExists() guard, matching AddSubdomainToTenantTables.
 */
class AddKodeRfidToWarga extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('kode_rfid', 'warga')) {
            $this->forge->addColumn('warga', [
                'kode_rfid' => [
                    'type' => 'VARCHAR', 'constraint' => 100,
                    'null' => true, 'after' => 'nik',
                ],
            ]);
            $this->db->query('ALTER TABLE `warga` ADD UNIQUE `uq_warga_kode_rfid` (`kode_rfid`)');
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('kode_rfid', 'warga')) {
            $this->forge->dropColumn('warga', 'kode_rfid');
        }
    }
}
