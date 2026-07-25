<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kesehatan Lansia used to be unconditionally available to the whole
 * 'rw' group regardless of per-user permissions (MenuAccessFilter's
 * bypass group on the 'kesehatan' route). It's now a plain per-user
 * 'menu.kesehatan' permission for 'rw' too - independently assignable
 * from Rekap RW, same as any other menu (see Config\AuthGroups +
 * Admin\Users) - so every existing 'rw' user needs it backfilled or
 * they'd lose access to Kesehatan Lansia the moment this deploys.
 *
 * Idempotent: skips users that already have the permission, safe to
 * re-run.
 */
class BackfillRwKesehatanPermission extends Migration
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
                ->where('permission', 'menu.kesehatan')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.kesehatan',
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
