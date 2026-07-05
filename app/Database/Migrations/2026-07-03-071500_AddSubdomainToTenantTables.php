<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds `subdomain` to `rt` and `rw` for subdomain-based tenant
 * resolution (rt29.minomartani.com, rw06.minomartani.com, ...), layered
 * on top of the existing slug/path-prefix mechanism.
 *
 * Nullable + UNIQUE per table: existing rows have no subdomain until an
 * admin fills one in via Admin > Tenants. MySQL allows multiple NULLs
 * in a UNIQUE index, so nullability doesn't weaken uniqueness for rows
 * that do have one set.
 *
 * Idempotent: fieldExists() guard, matching AddTenantColumnToDataTables.
 */
class AddSubdomainToTenantTables extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('subdomain', 'rw')) {
            $this->forge->addColumn('rw', [
                'subdomain' => [
                    'type' => 'VARCHAR', 'constraint' => 63,
                    'null' => true, 'after' => 'slug',
                ],
            ]);
            $this->db->query('ALTER TABLE `rw` ADD UNIQUE `uq_rw_subdomain` (`subdomain`)');
        }

        if (! $this->db->fieldExists('subdomain', 'rt')) {
            $this->forge->addColumn('rt', [
                'subdomain' => [
                    'type' => 'VARCHAR', 'constraint' => 63,
                    'null' => true, 'after' => 'slug',
                ],
            ]);
            $this->db->query('ALTER TABLE `rt` ADD UNIQUE `uq_rt_subdomain` (`subdomain`)');
        }

        $this->backfillRt29();
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('subdomain', 'rt')) {
            $this->forge->dropColumn('rt', 'subdomain');
        }
        if ($this->db->fieldExists('subdomain', 'rw')) {
            $this->forge->dropColumn('rw', 'subdomain');
        }
    }

    /**
     * RT 29's production host already IS rt29.minomartani.com (see
     * dbsync.productionURL in .env) — backfill so the live site keeps
     * working the moment host-based resolution ships.
     */
    private function backfillRt29(): void
    {
        $db   = $this->db;
        $rt29 = $db->table('rt')->where('slug', 'rt29')->get()->getRow();

        if ($rt29 !== null && empty($rt29->subdomain)) {
            $db->table('rt')->where('id_rt', $rt29->id_rt)->update(['subdomain' => 'rt29']);
        }
    }
}
