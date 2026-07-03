<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-off data migration: ports the 6 accounts in the legacy `user`
 * table (CI3 custom auth) into Shield's users/auth_identities/
 * auth_groups_users tables.
 *
 * The legacy `role` column (1 vs 2) was never actually checked by any
 * CI3 controller - it was only ever saved into the session - so there
 * was no real permission distinction between accounts. All 6 land in
 * Shield's `admin` group; the site owner can demote anyone who
 * shouldn't have full access after go-live.
 *
 * Legacy passwords were already hashed with
 * password_hash($pw, PASSWORD_DEFAULT) (confirmed: all 6 are $2y$...,
 * 60 chars - the same bcrypt format Shield uses), so hashes are reused
 * as-is. No password reset is forced.
 *
 * Idempotent: matches on email before inserting, safe to re-run.
 */
class MigrateLegacyUsersToShield extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('user') || ! $db->tableExists('users')) {
            return;
        }

        // The one legacy account already migrated (id_user = 1,
        // Abdurrahman, humas@rt29minomartani.com -> Shield users.id = 1)
        // landed in group 'user' instead of 'admin'. Fix it.
        $db->table('auth_groups_users')
            ->where('user_id', 1)
            ->where('group', 'user')
            ->update(['group' => 'admin']);

        $legacyUsers = $db->table('user')
            ->select('id_user, username, email, password')
            ->where('id_user !=', 1) // already handled above
            ->get()
            ->getResult();

        foreach ($legacyUsers as $legacy) {
            if (empty($legacy->email)) {
                continue;
            }

            $existingIdentity = $db->table('auth_identities')
                ->where('type', 'email_password')
                ->where('secret', $legacy->email)
                ->get()
                ->getRow();

            if ($existingIdentity !== null) {
                continue; // already migrated on a previous run
            }

            $username = $this->uniqueUsername($db, $legacy->email, $legacy->id_user);
            $now      = date('Y-m-d H:i:s');

            $db->table('users')->insert([
                'username'   => $username,
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $newUserId = $db->insertID();

            $db->table('auth_identities')->insert([
                'user_id'    => $newUserId,
                'type'       => 'email_password',
                'secret'     => $legacy->email,
                'secret2'    => $legacy->password, // bcrypt hash, reused as-is
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $db->table('auth_groups_users')->insert([
                'user_id'    => $newUserId,
                'group'      => 'admin',
                'created_at' => $now,
            ]);
        }
    }

    public function down()
    {
        // Intentionally a no-op: these accounts may be in active use by
        // the time anyone rolls back. Removing them isn't safe to
        // automate - deactivate manually via Admin\Users if needed.
    }

    /**
     * Derives a Shield username from the email local-part, disambiguating
     * collisions (e.g. legacy id_user 2/5/6 all localize to "risto") by
     * appending the legacy id_user.
     */
    private function uniqueUsername($db, string $email, int $legacyId): string
    {
        $base = strtolower(explode('@', $email)[0]);
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'user';

        $exists = $db->table('users')->where('username', $base)->countAllResults() > 0;

        return $exists ? $base . $legacyId : $base;
    }
}
