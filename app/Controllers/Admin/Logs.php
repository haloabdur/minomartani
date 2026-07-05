<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Logs extends BaseController
{
    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
    private const PER_PAGE = 50;

    public function index()
    {
        $this->global['pageTitle'] = 'Log Error';

        $date = (string) $this->request->getGet('date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
            $date = date('Y-m-d');
        }

        $level = strtolower((string) $this->request->getGet('level'));
        if (!in_array($level, self::LEVELS, true)) {
            $level = '';
        }

        $search = trim((string) $this->request->getGet('q'));

        $page = max(1, (int) $this->request->getGet('page'));

        $logFile = WRITEPATH . 'logs/log-' . $date . '.log';
        $entries = is_file($logFile) ? $this->parseLogFile($logFile) : [];

        // Chart always reflects the whole day, independent of the table filter.
        $counts = array_fill_keys(self::LEVELS, 0);
        foreach ($entries as $entry) {
            if (isset($counts[$entry['level']])) {
                $counts[$entry['level']]++;
            }
        }

        $filtered = $level === ''
            ? $entries
            : array_values(array_filter($entries, fn ($e) => $e['level'] === $level));

        if ($search !== '') {
            $filtered = array_values(array_filter(
                $filtered,
                fn ($e) => stripos($e['message'], $search) !== false || stripos($e['context'], $search) !== false
            ));
        }

        // Newest first.
        $filtered = array_reverse($filtered);

        $totalEntries = count($filtered);
        $totalPages = max(1, (int) ceil($totalEntries / self::PER_PAGE));
        $page = min($page, $totalPages);
        $pageEntries = array_slice($filtered, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        $data = [
            'date' => $date,
            'level' => $level,
            'search' => $search,
            'levels' => self::LEVELS,
            'counts' => $counts,
            'entries' => $pageEntries,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalEntries' => $totalEntries,
            'logFileExists' => is_file($logFile),
        ];

        return $this->loadViews('admin/logs', $this->global, $data);
    }

    public function delete()
    {
        $date = (string) $this->request->getPost('date');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date)) {
            $logFile = WRITEPATH . 'logs/log-' . $date . '.log';
            if (is_file($logFile)) {
                unlink($logFile);
                setFlashData('success', 'File log tanggal ' . $date . ' berhasil dihapus.');
            } else {
                setFlashData('error', 'File log tanggal ' . $date . ' tidak ditemukan.');
            }
        }

        return redirect()->to('admin/logs');
    }

    /**
     * Parse a CI4 log file into entries, attaching any following
     * non-matching lines (stack traces) to the preceding entry as context.
     *
     * @return list<array{level: string, timestamp: string, message: string, context: string}>
     */
    private function parseLogFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        $pattern = '/^(' . implode('|', array_map('strtoupper', self::LEVELS)) . ')\s+-\s+(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+-->\s+(.*)$/';

        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = [
                    'level' => strtolower($m[1]),
                    'timestamp' => $m[2],
                    'message' => $m[3],
                    'context' => '',
                ];
            } elseif ($current !== null) {
                $current['context'] .= ($current['context'] === '' ? '' : "\n") . $line;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return $entries;
    }
}
