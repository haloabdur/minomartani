<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Presensi Acara is a new per-user menu permission ('menu.presensi',
 * assignable to both 'admin' and 'rw' - see Config\AuthGroups +
 * Admin\Users). Grant it to every account that already holds
 * 'menu.kesehatan': same audience (kader/pengurus who run the events
 * these two menus record), so they get the new menu without a superadmin
 * having to tick a box on each account after deploy.
 *
 * Idempotent: skips users that already have the permission, safe to
 * re-run.
 */
class BackfillPresensiPermission extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('auth_permissions_users')) {
            return;
        }

        $candidates = $db->table('auth_permissions_users')
            ->select('user_id')
            ->where('permission', 'menu.kesehatan')
            ->get()
            ->getResult();

        $now = date('Y-m-d H:i:s');

        foreach ($candidates as $row) {
            $alreadyGranted = $db->table('auth_permissions_users')
                ->where('user_id', $row->user_id)
                ->where('permission', 'menu.presensi')
                ->countAllResults() > 0;

            if ($alreadyGranted) {
                continue;
            }

            $db->table('auth_permissions_users')->insert([
                'user_id'    => $row->user_id,
                'permission' => 'menu.presensi',
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
