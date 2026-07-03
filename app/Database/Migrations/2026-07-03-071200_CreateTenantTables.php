<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Multi-tenancy master tables. Hierarchy: rw -> rt. Every tenant-owned
 * data row carries an id_rt (added in the next migration).
 *
 * Idempotent: createTable(..., true) no-ops when the table exists, and
 * the seed matches on slug before inserting. RT 29 (the original
 * single-tenant install) is deliberately the first rt row so it gets
 * id_rt = 1 - the value the data backfill migration assigns to every
 * pre-existing row.
 */
class CreateTenantTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_rw'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'is_aktif'   => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_rw');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('rw', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        $this->forge->addField([
            'id_rt'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_rw'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'is_aktif'   => ['type' => 'TINYINT', 'constraint' => 4, 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_rt');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('id_rw');
        $this->forge->createTable('rt', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        $this->seedFirstTenant();
    }

    public function down()
    {
        $this->forge->dropTable('rt', true);
        $this->forge->dropTable('rw', true);
    }

    private function seedFirstTenant(): void
    {
        $db = $this->db;

        if ($db->table('rw')->where('slug', 'rw-minomartani')->countAllResults() === 0) {
            $db->table('rw')->insert([
                'nama' => 'RW Minomartani',
                'slug' => 'rw-minomartani',
            ]);
        }

        $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        if ($db->table('rt')->where('slug', 'rt29')->countAllResults() === 0) {
            $db->table('rt')->insert([
                'id_rw' => $idRw,
                'nama'  => 'RT 29',
                'slug'  => 'rt29',
            ]);
        }
    }
}
