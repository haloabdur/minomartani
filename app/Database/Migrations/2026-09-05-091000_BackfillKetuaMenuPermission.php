<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ketua RT is a new per-user menu permission ('menu.ketua', 'admin'-group
 * only - see Config\AuthGroups + Admin\Users). Grant it to every account
 * currently in the 'admin' group, mirroring BackfillPapanInformasiPermission,
 * so existing RT admins don't silently lose access the moment this deploys.
 *
 * Idempotent: skips users that already have the permission, safe to
 * re-run.
 */
class BackfillKetuaMenuPermission extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('auth_groups_users') || ! $db->tableExists('auth_permissions_users')) {
            return;
        }

        $adminUsers = $db->table('auth_groups_users')
            ->select('user_id')
            ->where('group', 'admin')
            ->get()
            ->getResult();

        $now = date('Y-m-d H:i:s');

        foreach ($adminUsers as $row) {
            $alreadyGranted = $db->table('auth_permissions_users')
                ->where('user_id', $row->user_id)
                ->where('permission', 'menu.ketua')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.ketua',
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
