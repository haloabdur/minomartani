<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * The pre-Shield `user` table. No longer read or written by the app
 * (Shield's `users`/`auth_identities` replace it - see
 * MigrateLegacyUsersToShield), kept only so a fresh install reproduces
 * the full historical schema and so the original rows remain available
 * for audit. Not dropped automatically; that's a manual, deliberate
 * step for later.
 */
class CreateLegacyUserTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_user'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'username'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'password'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'role'        => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'status_user' => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'timestamp'   => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_user');
        $this->forge->createTable('user', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'latin1', 'COLLATE' => 'latin1_swedish_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('user', true);
    }
}
