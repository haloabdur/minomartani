<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Sync extends BaseController
{
    public function index()
    {
        $this->global['pageTitle'] = 'Sinkronisasi Struktur Database';
        return $this->loadViews('admin/sync', $this->global);
    }

    public function export_schema()
    {
        $db = \Config\Database::connect();
        $schema = [];
        $tables = $db->listTables();

        foreach ($tables as $table) {
            $fields = $db->getFieldData($table);
            $schema[$table] = $fields;
        }

        $jsonContent = json_encode($schema, JSON_PRETTY_PRINT);
        $filename = 'rt29_schema_local_' . date('Y_m_d_His') . '.json';

        return $this->response->download($filename, $jsonContent);
    }

    public function import_schema()
    {
        $file = $this->request->getFile('schema_json');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'json') {
            session()->setFlashdata('error', 'Silakan upload file JSON yang valid hasil dari Export Local.');
            return redirect()->to(base_url('admin/sync'));
        }

        $jsonContent = file_get_contents($file->getTempName());
        $schemaLocal = json_decode($jsonContent, true);

        if (!$schemaLocal) {
            session()->setFlashdata('error', 'Format JSON rusak atau tidak bisa dibaca.');
            return redirect()->to(base_url('admin/sync'));
        }

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();
        $logs = [];

        foreach ($schemaLocal as $tableName => $fieldsLocal) {
            if (!$db->tableExists($tableName)) {
                // Buat Tabel Baru
                $forgeFields = [];
                $primaryKey = null;

                foreach ($fieldsLocal as $field) {
                    $fieldName = $field['name'];
                    $forgeFields[$fieldName] = [
                        'type' => $field['type']
                    ];

                    if (!empty($field['max_length'])) {
                        $forgeFields[$fieldName]['constraint'] = $field['max_length'];
                    }
                    if (isset($field['default']) && $field['default'] !== null && $field['default'] !== 'NULL') {
                        $forgeFields[$fieldName]['default'] = $field['default'];
                    }
                    if (isset($field['nullable']) && $field['nullable'] == true) {
                        $forgeFields[$fieldName]['null'] = true;
                    }
                    if (!empty($field['primary_key']) && $field['primary_key'] == 1) {
                        $primaryKey = $fieldName;
                        // Auto increment untuk integer PK
                        if (strpos(strtolower($field['type']), 'int') !== false) {
                            $forgeFields[$fieldName]['auto_increment'] = true;
                        }
                    }
                }

                $forge->addField($forgeFields);
                if ($primaryKey) {
                    $forge->addKey($primaryKey, true);
                }

                try {
                    $forge->createTable($tableName);
                    $logs[] = "<span class='text-success'><i class='fas fa-plus-circle'></i> Tabel baru dibuat: <b>{$tableName}</b></span>";
                } catch (\Exception $e) {
                    $logs[] = "<span class='text-danger'><i class='fas fa-exclamation-triangle'></i> Gagal membuat tabel <b>{$tableName}</b>: " . $e->getMessage() . "</span>";
                }
            } else {
                // Tabel sudah ada, periksa per kolom
                $existingFieldsRaw = $db->getFieldData($tableName);
                $existingFieldNames = array_map(function ($f) {
                    return $f->name;
                }, $existingFieldsRaw);

                foreach ($fieldsLocal as $field) {
                    $fieldName = $field['name'];
                    if (!in_array($fieldName, $existingFieldNames)) {
                        // Tambah kolom baru
                        $forgeField = [
                            'type' => $field['type']
                        ];

                        if (!empty($field['max_length'])) {
                            $forgeField['constraint'] = $field['max_length'];
                        }
                        if (isset($field['default']) && $field['default'] !== null && $field['default'] !== 'NULL') {
                            $forgeField['default'] = $field['default'];
                        }
                        if (isset($field['nullable']) && $field['nullable'] == true) {
                            $forgeField['null'] = true;
                        }

                        try {
                            $forge->addColumn($tableName, [
                                $fieldName => $forgeField
                            ]);
                            $logs[] = "<span class='text-primary'><i class='fas fa-columns'></i> Kolom ditambahkan: <b>{$fieldName}</b> pada tabel <b>{$tableName}</b></span>";
                        } catch (\Exception $e) {
                            $logs[] = "<span class='text-danger'><i class='fas fa-exclamation-triangle'></i> Gagal menambah kolom <b>{$fieldName}</b> di tabel <b>{$tableName}</b>: " . $e->getMessage() . "</span>";
                        }
                    }
                }
            }
        }

        if (empty($logs)) {
            $logs[] = "<span class='text-muted'><i class='fas fa-check-circle'></i> Struktur database sudah sinkron. Tidak ada perubahan struktur (Tabel/Kolom baru) yang diperlukan.</span>";
        }

        session()->setFlashdata('sync_logs', $logs);
        session()->setFlashdata('message', 'Proses sinkronisasi struktur database selesai.');

        return redirect()->to(base_url('admin/sync'));
    }
}
