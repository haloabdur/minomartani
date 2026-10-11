<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kucing is a per-user menu permission ('menu.kucing', 'admin'-group only -
 * see Config\AuthGroups + Admin\Users). The feature currently exists only
 * for RT 29 (id_rt = 1), so unlike the other menu backfills this one grants
 * the permission just to 'admin'-group accounts bound to RT 29. Other RTs
 * can be given the menu later by ticking it in Admin\Users.
 *
 * Idempotent: skips users that already have the permission, safe to
 * re-run.
 */
class BackfillKucingMenuPermission extends Migration
{
    private const RT_ID = 1;

    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('auth_groups_users') || ! $db->tableExists('auth_permissions_users') || ! $db->tableExists('users')) {
            return;
        }

        $adminUsers = $db->table('auth_groups_users')
            ->select('auth_groups_users.user_id')
            ->join('users', 'users.id = auth_groups_users.user_id')
            ->where('auth_groups_users.group', 'admin')
            ->where('users.id_rt', self::RT_ID)
            ->get()
            ->getResult();

        $now = date('Y-m-d H:i:s');

        foreach ($adminUsers as $row) {
            $alreadyGranted = $db->table('auth_permissions_users')
                ->where('user_id', $row->user_id)
                ->where('permission', 'menu.kucing')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.kucing',
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
