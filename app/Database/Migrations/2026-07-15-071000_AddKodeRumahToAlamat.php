<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `kode_rumah` (household PIN) to `alamat`.
 *
 * AlamatModel::$allowedFields already referenced `kode_rumah`, and
 * Layanan::store() already compares the submitted `pin` POST field
 * against it - but the column never actually existed on the live
 * `alamat` table, so the PIN check silently compared against NULL and
 * never meaningfully validated anything (or errored, depending on
 * query shape). This migration creates the real column so that
 * comparison becomes meaningful.
 *
 * Nullable, no backfill: existing addresses start with no PIN set
 * (fail-closed - residents at that address can't submit the Layanan
 * form until an RT admin sets one via Admin > Alamat). Idempotent:
 * fieldExists() guard, matching AddSumberAirToWarga.
 */
class AddKodeRumahToAlamat extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('kode_rumah', 'alamat')) {
            $this->forge->addColumn('alamat', [
                'kode_rumah' => [
                    'type' => 'VARCHAR', 'constraint' => 20,
                    'null' => true, 'after' => 'qrcode',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('kode_rumah', 'alamat')) {
            $this->forge->dropColumn('alamat', 'kode_rumah');
        }
    }
}
