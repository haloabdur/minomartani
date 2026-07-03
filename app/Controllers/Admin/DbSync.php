<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DbSync extends BaseController
{
    public function index()
    {
        $this->global['pageTitle'] = 'Sinkronisasi Database';

        $data = [
            'env' => env('CI_ENVIRONMENT') ?? 'development',
            'token' => env('dbsync.token') ?? getenv('dbsync.token'),
            'productionURL' => env('dbsync.productionURL') ?? getenv('dbsync.productionURL'),
        ];

        return $this->loadViews('admin/dbsync', $this->global, $data);
    }

    public function export()
    {
        $structureOnly = $this->request->getGet('structure_only') === '1';
        try {
            $sql = $this->dumpDatabase($structureOnly);
            $filename = 'rt29mino_db_' . ($structureOnly ? 'structure_' : '') . date('Ymd_His') . '.sql';

            return $this->response
                ->setHeader('Content-Type', 'application/octet-stream')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($sql);
        } catch (\Exception $e) {
            setFlashData('error', 'Gagal mengekspor database: ' . $e->getMessage());
            return redirect()->to('admin/dbsync');
        }
    }

    public function import()
    {
        $file = $this->request->getFile('sql_file');

        if (!$file || !$file->isValid()) {
            setFlashData('error', 'Silakan pilih file SQL yang valid.');
            return redirect()->to('admin/dbsync');
        }

        try {
            $sql = file_get_contents($file->getTempName());
            if (empty($sql)) {
                throw new \Exception("File SQL kosong.");
            }

            $this->executeSql($sql);
            setFlashData('success', 'Database berhasil diimpor dan diperbarui!');
        } catch (\Exception $e) {
            setFlashData('error', 'Gagal mengimpor database: ' . $e->getMessage());
        }

        return redirect()->to('admin/dbsync');
    }

    public function push()
    {
        if (env('CI_ENVIRONMENT') === 'production') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Push hanya bisa diinisiasi dari lingkungan lokal.'
            ]);
        }

        $token = env('dbsync.token') ?? getenv('dbsync.token');
        $prodUrl = env('dbsync.productionURL') ?? getenv('dbsync.productionURL');

        if (empty($token) || empty($prodUrl)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Konfigurasi dbsync.token atau dbsync.productionURL belum diset di .env.'
            ]);
        }

        // Clean prod URL trailing slash
        $prodUrl = rtrim($prodUrl, '/');
        $endpoint = $prodUrl . '/api/dbsync';

        $structureOnly = $this->request->getPost('structure_only') === '1';

        try {
            $sql = $this->dumpDatabase($structureOnly);
            $sqlGz = base64_encode(gzencode($sql, 9));

            // Send via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'token' => $token,
                'action' => 'push',
                'sql_gz' => $sqlGz
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout
            // Bypass SSL check for dev testing if necessary, but keep secure
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \Exception("cURL Error: " . $curlError);
            }

            $result = json_decode($response, true);
            if ($httpCode !== 200 || !isset($result['status']) || $result['status'] !== 'success') {
                $errMsg = $result['message'] ?? 'Respons tidak dikenal dari server produksi.';
                throw new \Exception("HTTP $httpCode: $errMsg");
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Berhasil mensinkronkan database lokal ke produksi!'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal Push DB: ' . $e->getMessage()
            ]);
        }
    }

    public function pull()
    {
        if (env('CI_ENVIRONMENT') === 'production') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Pull hanya bisa diinisiasi dari lingkungan lokal.'
            ]);
        }

        $token = env('dbsync.token') ?? getenv('dbsync.token');
        $prodUrl = env('dbsync.productionURL') ?? getenv('dbsync.productionURL');

        if (empty($token) || empty($prodUrl)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Konfigurasi dbsync.token atau dbsync.productionURL belum diset di .env.'
            ]);
        }

        // Clean prod URL trailing slash
        $prodUrl = rtrim($prodUrl, '/');
        $endpoint = $prodUrl . '/api/dbsync';

        $structureOnly = $this->request->getPost('structure_only') === '1';

        try {
            // Call via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'token' => $token,
                'action' => 'pull',
                'structure_only' => $structureOnly ? '1' : '0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new \Exception("cURL Error: " . $curlError);
            }

            $result = json_decode($response, true);
            if ($httpCode !== 200 || !isset($result['status']) || $result['status'] !== 'success') {
                $errMsg = $result['message'] ?? 'Respons tidak dikenal dari server produksi.';
                throw new \Exception("HTTP $httpCode: $errMsg");
            }

            $sqlGz = $result['sql_gz'] ?? '';
            if (empty($sqlGz)) {
                throw new \Exception("Server produksi tidak mengirimkan data SQL.");
            }

            $sql = gzdecode(base64_decode($sqlGz));
            if ($sql === false) {
                throw new \Exception("Gagal dekompresi data SQL dari server produksi.");
            }

            $this->executeSql($sql);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Berhasil menarik (pull) database dari produksi ke lokal!'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal Pull DB: ' . $e->getMessage()
            ]);
        }
    }

    public function migrate()
    {
        try {
            $runner = \Config\Services::migrations();
            $runner->latest();
            setFlashData('success', 'Migrasi database berhasil dijalankan!');
        } catch (\Throwable $e) {
            setFlashData('error', 'Gagal menjalankan migrasi: ' . $e->getMessage());
        }

        return redirect()->to('admin/dbsync');
    }

    public function checkMigrations()
    {
        $db = \Config\Database::connect();
        
        $history = [];
        if ($db->tableExists('migrations')) {
            $history = $db->table('migrations')->select('class')->get()->getResultArray();
            $history = array_column($history, 'class');
        }

        $migrationDir = APPPATH . 'Database/Migrations/';
        $files = glob($migrationDir . '*.php');
        
        $pending = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^\d{4}-\d{2}-\d{2}-\d{6}_(.+)\.php$/', $filename, $matches)) {
                $className = $matches[1];
                $fullClass = 'App\Database\Migrations\\' . $className;
                
                if (!in_array($fullClass, $history) && !in_array($className, $history)) {
                    $content = file_get_contents($file);
                    $upCode = '';
                    
                    $pos = strpos($content, 'function up()');
                    if ($pos !== false) {
                        $startBrace = strpos($content, '{', $pos);
                        if ($startBrace !== false) {
                            $braceCount = 1;
                            $i = $startBrace + 1;
                            $len = strlen($content);
                            while ($i < $len && $braceCount > 0) {
                                if ($content[$i] === '{') {
                                    $braceCount++;
                                } elseif ($content[$i] === '}') {
                                    $braceCount--;
                                }
                                $i++;
                            }
                            $upCode = substr($content, $startBrace + 1, $i - $startBrace - 2);
                        }
                    }
                    
                    $pending[] = [
                        'file' => $filename,
                        'class' => $className,
                        'code' => trim($upCode)
                    ];
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'pending' => $pending
        ]);
    }


    public function api()
    {
        $token = env('dbsync.token') ?? getenv('dbsync.token');
        if (empty($token)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'DB Sync Token belum dikonfigurasi di server ini.'
            ]);
        }

        $receivedToken = $this->request->getPost('token') ?: $this->request->getHeaderLine('X-DB-Sync-Token');
        if ($receivedToken !== $token) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'DB Sync Token tidak cocok.'
            ]);
        }

        $action = $this->request->getPost('action');
        if ($action === 'push') {
            $sqlGz = $this->request->getPost('sql_gz');
            if (empty($sqlGz)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Data SQL kosong.'
                ]);
            }

            try {
                $sql = gzdecode(base64_decode($sqlGz));
                if ($sql === false) {
                    throw new \Exception("Gagal mendekompresi payload SQL.");
                }

                $this->executeSql($sql);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Database produksi berhasil diperbarui dari lokal.'
                ]);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal memproses SQL: ' . $e->getMessage()
                ]);
            }
        } elseif ($action === 'pull') {
            $structureOnly = $this->request->getPost('structure_only') === '1';
            try {
                $sql = $this->dumpDatabase($structureOnly);
                $sqlGz = base64_encode(gzencode($sql, 9));

                return $this->response->setJSON([
                    'status' => 'success',
                    'sql_gz' => $sqlGz
                ]);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal mengekspor DB di server produksi: ' . $e->getMessage()
                ]);
            }
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status' => 'error',
            'message' => 'Aksi tidak dikenal.'
        ]);
    }

    private function dumpDatabase(bool $structureOnly = false): string
    {
        $db = \Config\Database::connect();
        
        $sql = "-- Database Sync Dump\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Environment: " . (env('CI_ENVIRONMENT') ?? 'unknown') . "\n";
        $sql .= "-- Mode: " . ($structureOnly ? 'Structure Only' : 'Structure & Data') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $db->listTables();
        foreach ($tables as $table) {
            $query = $db->query("SHOW CREATE TABLE " . $db->escapeIdentifiers($table));
            $row = $query->getRowArray();
            
            $sql .= "DROP TABLE IF EXISTS " . $db->escapeIdentifiers($table) . ";\n";
            $sql .= $row['Create Table'] . ";\n\n";

            if ($structureOnly) {
                continue;
            }

            $builder = $db->table($table);
            $totalRows = $builder->countAllResults(false);
            
            if ($totalRows > 0) {
                $limit = 500;
                $offset = 0;
                
                while ($offset < $totalRows) {
                    $rows = $builder->get($limit, $offset)->getResultArray();
                    if (empty($rows)) {
                        break;
                    }
                    
                    $fields = array_keys($rows[0]);
                    
                    $sql .= "INSERT INTO " . $db->escapeIdentifiers($table) . " (";
                    $sql .= implode(', ', array_map(function($f) use ($db) { return $db->escapeIdentifiers($f); }, $fields));
                    $sql .= ") VALUES\n";

                    $valueLines = [];
                    foreach ($rows as $r) {
                        $escapedValues = [];
                        foreach ($fields as $field) {
                            $val = $r[$field];
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } else {
                                $escapedValues[] = $db->escape($val);
                            }
                        }
                        $valueLines[] = "(" . implode(', ', $escapedValues) . ")";
                    }
                    
                    $sql .= implode(",\n", $valueLines) . ";\n\n";
                    $offset += $limit;
                }
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $sql;
    }

    private function executeSql(string $sql)
    {
        $db = \Config\Database::connect();
        
        $lines = explode("\n", $sql);
        $query = '';
        $inString = false;
        $esc = false;
        
        $db->query("SET FOREIGN_KEY_CHECKS=0;");
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            
            $query .= $line . "\n";
            
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];
                if ($char === '\\') {
                    $esc = !$esc;
                } else {
                    if (($char === "'" || $char === '"') && !$esc) {
                        $inString = !$inString;
                    }
                    $esc = false;
                }
            }
            
            if (!$inString && substr(rtrim($trimmed), -1) === ';') {
                $db->query($query);
                $query = '';
            }
        }
        
        $db->query("SET FOREIGN_KEY_CHECKS=1;");
    }
}
