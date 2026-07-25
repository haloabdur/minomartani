<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rekap RW used to be unconditionally available to the whole 'rw'
 * group (route filter was 'group:rw,superadmin'). It's now gated by the
 * per-user 'menu.rekap' permission instead (see Config\AuthGroups +
 * Admin\Users), so every existing 'rw' user needs it backfilled or
 * they'd lose access to Rekap RW the moment this deploys.
 *
 * Idempotent: skips users that already have the permission, safe to
 * re-run.
 */
class BackfillRwMenuPermissions extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('auth_groups_users') || ! $db->tableExists('auth_permissions_users')) {
            return;
        }

        $rwUsers = $db->table('auth_groups_users')
            ->select('user_id')
            ->where('group', 'rw')
            ->get()
            ->getResult();

        $now = date('Y-m-d H:i:s');

        foreach ($rwUsers as $row) {
            $alreadyGranted = $db->table('auth_permissions_users')
                ->where('user_id', $row->user_id)
                ->where('permission', 'menu.rekap')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.rekap',
                'created_at' => $now,
            ]);
        }
    }

    public function down()
    {
        // Intentionally a no-op: accounts may already be relying on
        // this permission by the time anyone rolls back. Narrow access
        // manually via Admin\Users if needed.
    }
}
