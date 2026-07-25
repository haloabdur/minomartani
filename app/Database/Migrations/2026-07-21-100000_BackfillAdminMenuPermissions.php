<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-user "menu.*" permissions (Admin\Users menu access checkboxes) are
 * now the only thing gating Warga/Alamat/Berita/Kesehatan for the 'admin'
 * group - the group matrix itself grants none of them (see
 * Config\AuthGroups). Without this backfill every existing admin-group
 * user would lose access to those menus the moment this deploys.
 *
 * Grants all four menu permissions to every user currently in the
 * 'admin' group; narrow individual users afterwards via Admin\Users.
 *
 * Idempotent: skips permissions a user already has, safe to re-run.
 */
class BackfillAdminMenuPermissions extends Migration
{
    private const MENU_PERMISSIONS = [
        'menu.warga',
        'menu.alamat',
        'menu.berita',
        'menu.kesehatan',
    ];

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
            $existingPermissions = array_column(
                $db->table('auth_permissions_users')
                    ->select('permission')
                    ->where('user_id', $row->user_id)
                    ->get()
                    ->getResult(),
                'permission'
            );

            foreach (self::MENU_PERMISSIONS as $permission) {
                if (in_array($permission, $existingPermissions, true)) {
                    continue;
                }

                $db->table('auth_permissions_users')->insert([
                    'user_id'    => $row->user_id,
                    'permission' => $permission,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // Intentionally a no-op: accounts may already be relying on
        // these permissions by the time anyone rolls back. Narrow
        // access manually via Admin\Users if needed.
    }
}
