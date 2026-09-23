<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * `berita.timestamp` has ON UPDATE CURRENT_TIMESTAMP, so editing a berita
 * (fixing a typo, swapping a photo, managing the gallery) silently bumped
 * it - and both the public list (Home::index) and admin list
 * (BeritaModel::all()) sort by `timestamp` DESC, so an unrelated edit
 * reordered the whole feed. `created_time` is set once (DB default on
 * insert, no ON UPDATE) and never touched again - list ordering and the
 * public detail page's displayed date move to it; `timestamp` keeps its
 * existing "last modified" meaning, just isn't used for ordering anymore.
 *
 * Backfilled from the current `timestamp` value, which is the best
 * available approximation of original creation time for existing rows.
 */
class AddCreatedTimeToBerita extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('created_time', 'berita')) {
            return;
        }

        $this->forge->addColumn('berita', [
            'created_time' => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'timestamp'],
        ]);

        $this->db->query('UPDATE berita SET created_time = timestamp WHERE created_time IS NULL');

        $this->forge->modifyColumn('berita', [
            'created_time' => ['name' => 'created_time', 'type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('created_time', 'berita')) {
            $this->forge->dropColumn('berita', 'created_time');
        }
    }
}
