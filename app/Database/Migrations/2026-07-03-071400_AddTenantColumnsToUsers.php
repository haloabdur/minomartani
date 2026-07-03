<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Links Shield accounts to tenants. Nullable on purpose:
 *  - superadmin: id_rt NULL, id_rw NULL (all tenants)
 *  - RT admin (group 'admin'): id_rt set
 *  - RW account (group 'rw'): id_rw set
 *
 * Backfill: every pre-existing admin-group account is an RT 29
 * pengurus, so they get id_rt = 1. Idempotent via fieldExists() and
 * the IS NULL condition on the backfill.
 */
class AddTenantColumnsToUsers extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('id_rt', 'users')) {
            $this->forge->addColumn('users', [
                'id_rt' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'id_rw' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
        }

        $adminIds = array_column(
            $this->db->table('auth_groups_users')->select('user_id')->where('group', 'admin')->get()->getResultArray(),
            'user_id',
        );

        if ($adminIds !== []) {
            $this->db->table('users')
                ->whereIn('id', $adminIds)
                ->where('id_rt IS NULL')
                ->update(['id_rt' => 1]);
        }
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('id_rt', 'users')) {
            $this->forge->dropColumn('users', ['id_rt', 'id_rw']);
        }
    }
}
