<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Surat (Layanan requests) becomes a per-user menu permission
 * ('menu.surat', 'admin'-group only - see Config\AuthGroups +
 * Admin\Users). Grant it to every account currently in the 'admin' group,
 * mirroring BackfillInventarisMenuPermission, so existing RT admins keep
 * reaching admin/surat the moment this deploys.
 *
 * Idempotent: skips users that already have the permission.
 */
class BackfillSuratMenuPermission extends Migration
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
                ->where('permission', 'menu.surat')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.surat',
                'created_at' => $now,
            ]);
        }
    }

    public function down()
    {
        // Intentionally a no-op, same reasoning as BackfillInventarisMenuPermission.
    }
}
