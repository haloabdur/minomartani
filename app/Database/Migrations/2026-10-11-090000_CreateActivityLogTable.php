<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Log aktivitas umum: siapa mengubah apa, kapan, dari nilai berapa ke
 * berapa. Diisi otomatis oleh trait App\Models\Concerns\Auditable di model
 * yang memakainya (saat ini WargaModel). Tabel tenant-scoped modern:
 * id_rt dibake langsung, utf8mb4. `user_name` dan `record_label` sengaja
 * snapshot supaya log tetap terbaca setelah user/warga dihapus.
 */
class CreateActivityLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'id_rt'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'default' => 'system'],
            'module'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'record_id'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'record_label' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'changes'      => ['type' => 'LONGTEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['id_rt', 'created_at']);
        $this->forge->addKey(['module', 'record_id']);
        $this->forge->createTable('activity_log', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('activity_log', true);
    }
}
