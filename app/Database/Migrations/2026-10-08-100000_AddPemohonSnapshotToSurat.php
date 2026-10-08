<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Layanan form now lets the resident edit every field that is auto-filled
 * from `warga`, so the values they actually submitted are kept on the
 * `surat` row as a snapshot (admin compares them against the live `warga`
 * data and flags differences). `warga` itself is never overwritten.
 *
 * Rows created before this migration have these columns NULL; the admin
 * screens treat that as "no snapshot" and fall back to `warga`.
 *
 * `lampiran` held a comma-separated string (varchar(255)); the new form
 * has up to 10 separate lines, stored as a JSON array, which can exceed
 * 255 chars - widened to TEXT. Old comma-separated values stay readable
 * (SuratModel::parseLampiran() handles both formats).
 *
 * `alasan_tolak` backs the new "Ditolak" state (status_surat = 2).
 *
 * Idempotent: fieldExists() guards, matching AddKodeRumahToAlamat.
 */
class AddPemohonSnapshotToSurat extends Migration
{
    private const COLUMNS = [
        'nama_pemohon'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        'nik_pemohon'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        'alamat_pemohon' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        'tempat_lahir'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        'tanggal_lahir' => ['type' => 'DATE', 'null' => true],
        'agama'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        'no_hp'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        'alasan_tolak'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
    ];

    public function up()
    {
        $this->db->resetDataCache();

        $missing = [];
        foreach (self::COLUMNS as $name => $definition) {
            if (! $this->db->fieldExists($name, 'surat')) {
                $missing[$name] = $definition;
            }
        }

        if ($missing !== []) {
            $this->forge->addColumn('surat', $missing);
        }

        $this->forge->modifyColumn('surat', [
            'lampiran' => ['name' => 'lampiran', 'type' => 'TEXT', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->db->resetDataCache();

        foreach (array_keys(self::COLUMNS) as $name) {
            if ($this->db->fieldExists($name, 'surat')) {
                $this->forge->dropColumn('surat', $name);
            }
        }

        // lampiran is intentionally left as TEXT: shrinking it back to
        // varchar(255) could truncate JSON written since up().
    }
}
