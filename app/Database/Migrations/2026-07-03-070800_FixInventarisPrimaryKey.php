<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The live `inventaris` table predates versioned migrations and was
 * created without a PRIMARY KEY or AUTO_INCREMENT on `id`. That means
 * Admin\Inventaris::store() currently fails (or silently misbehaves)
 * on insert. CreateInventarisTable defines the correct schema for
 * fresh installs, but its ifNotExists guard is a no-op against the
 * live DB where the table already exists - so this migration applies
 * the fix directly with a guarded raw ALTER TABLE.
 */
class FixInventarisPrimaryKey extends Migration
{
    public function up()
    {
        $db = $this->db;

        if (! $db->tableExists('inventaris')) {
            return;
        }

        $hasPrimaryKey = false;
        foreach ($db->getFieldData('inventaris') as $field) {
            if ($field->name === 'id' && ! empty($field->primary_key)) {
                $hasPrimaryKey = true;
                break;
            }
        }

        if (! $hasPrimaryKey) {
            $db->query('ALTER TABLE `inventaris` MODIFY `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
        }
    }

    public function down()
    {
        // Intentionally a no-op: reverting would drop the PRIMARY KEY /
        // AUTO_INCREMENT from a table that may already hold real rows
        // inserted using the fixed schema. Not safely reversible.
    }
}
