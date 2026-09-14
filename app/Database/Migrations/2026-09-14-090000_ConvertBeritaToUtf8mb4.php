<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `berita` is still `latin1`/`latin1_swedish_ci` (legacy CI3 default, see
 * DATABASE.md). latin1 can't store 4-byte characters, so emoji typed into
 * judul/deskripsi/etc. get silently mangled into "?" on insert (already-saved
 * rows can't be recovered - the emoji bytes are gone by the time this runs).
 * Converts the whole table to utf8mb4 so new content keeps emoji intact.
 * Idempotent: checks information_schema before converting either direction.
 */
class ConvertBeritaToUtf8mb4 extends Migration
{
    public function up()
    {
        if ($this->currentCollation() !== 'utf8mb4_general_ci') {
            $this->db->query('ALTER TABLE `berita` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        }
    }

    public function down()
    {
        if ($this->currentCollation() !== 'latin1_swedish_ci') {
            $this->db->query('ALTER TABLE `berita` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci');
        }
    }

    private function currentCollation(): ?string
    {
        $row = $this->db->query(
            'SELECT table_collation FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            ['berita']
        )->getRow();

        return $row->table_collation ?? null;
    }
}
