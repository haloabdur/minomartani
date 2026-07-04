<?php

/**
 * KBW Helper - Migrated from CI3 to CI4
 * @author  : Abdurrahman
 */

/**
 * Print data for debugging
 */
if (!function_exists('pre')) {
    function pre($data)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        exit;
    }
}

/**
 * Set flash data to session
 */
if (!function_exists('setFlashData')) {
    function setFlashData($status, $flashMsg)
    {
        session()->setFlashdata($status, $flashMsg);
    }
}

/**
 * Convert number to Rupiah format
 */
if (!function_exists('convert_to_rupiah')) {
    function convert_to_rupiah($angka)
    {
        if (!empty($angka)) {
            return '<small>Rp</small> ' . strrev(implode('.', str_split(strrev(strval($angka)), 3))) . ',-';
        } else {
            return '-';
        }
    }
}

/**
 * Get Indonesian month name
 */
if (!function_exists('bulan_indo')) {
    function bulan_indo($i)
    {
        if (!empty($i)) {
            $bulan = array(
                1 => 'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember'
            );
            return $bulan[$i];
        } else {
            return '-';
        }
    }
}

/**
 * Format date to dd-mm-yyyy
 */
if (!function_exists('tanggal')) {
    function tanggal($date)
    {
        if (!empty($date)) {
            return date('d-m-Y', strtotime($date));
        } else {
            return '-';
        }
    }
}

/**
 * Calculate age from birthdate
 */
if (!function_exists('umur')) {
    function umur($date)
    {
        $biday = new DateTime($date);
        $today = new DateTime('today');
        $diff = $today->diff($biday);
        return $diff->y;
    }
}

/**
 * Get asset URL
 */
if (!function_exists('assets')) {
    function assets($uri = null)
    {
        return !empty($uri) ? base_url('public/' . $uri) : base_url('public');
    }
}

/**
 * Nice time ago format
 */
if (!function_exists('nicetime')) {
    function nicetime($date)
    {
        if (!isset($date) && !strtotime($date)) {
            return "-";
        }

        $now = time();
        $date = strtotime($date);

        $periods = array(
            array("second", 1),
            array("minute", 60),
            array("hour", 60),
            array("day", 24),
            array("week", 7),
            array("month", 4.35),
            array("year", 12)
        );

        if ($now > $date) {
            $difference = $now - $date;
            $tense = "ago";
        } else {
            $difference = $date - $now;
            $tense = "from now";
        }

        if ($difference < 60) {
            return "just now";
        }

        $figure = $difference;

        for ($index = 1; ($figure >= 1 && ($figure / $periods[$index][1]) >= 1) && $index < count($periods); $index++) {
            $figure /= $periods[$index][1];
            if ($figure != 1) {
                $periods[$index][0] .= "s";
            }
        }
        return round($figure) . " " . $periods[$index - 1][0] . " " . $tense;
    }
}

/**
 * Go back to previous page
 */
if (!function_exists('back')) {
    function back()
    {
        return empty($_SERVER['HTTP_REFERER']) ? '' : trim($_SERVER['HTTP_REFERER']);
    }
}

/**
 * Load flash data as toast HTML
 */
if (!function_exists('loadFlashData')) {
    function loadFlashData()
    {
        $session = session();

        if ($session->getFlashdata('error')) {
            return '<div class="toast" role="alert" data-delay="5000" data-animation="true" aria-live="assertive" aria-atomic="true" style="position: absolute; top: 70px; right: 1rem; z-index:1000">
            <div class="toast-header">
            <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"><rect fill="#dc3545" width="100%" height="100%"></rect></svg>
            <strong class="mr-auto text-danger">Gagal!</strong>
            <small class="text-muted">Baru saja</small>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <div class="toast-body">
            ' . $session->getFlashdata('error') . '
            </div>
            </div>';
        } elseif ($session->getFlashdata('success')) {
            return '<div class="toast" role="alert" data-delay="5000" data-animation="true" aria-live="assertive" aria-atomic="true" style="position: absolute; top: 70px; right: 1rem; z-index:1000">
            <div class="toast-header">
            <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"><rect fill="#28a745" width="100%" height="100%"></rect></svg>
            <strong class="mr-auto text-success">Berhasil!</strong>
            <small class="text-muted">Baru saja</small>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <div class="toast-body">
            ' . $session->getFlashdata('success') . '
            </div>
            </div>';
        } else {
            return '';
        }
    }
}

/**
 * Format one WargaModel::EXPORT_COLUMNS field for display in the
 * export table. Handles the 3 coded fields (jenis_kelamin,
 * status_kawin, is_hidup); everything else is echoed as-is.
 */
if (!function_exists('export_format_warga_field')) {
    function export_format_warga_field(string $key, object $row): string
    {
        $value = $row->{$key} ?? null;

        if ($key === 'jenis_kelamin') {
            return $value === 'L' ? 'Laki-Laki' : ($value === 'P' ? 'Perempuan' : '-');
        }

        if ($key === 'status_kawin') {
            $labels = ['0' => 'Belum Kawin', '1' => 'Kawin', '2' => 'Cerai Hidup', '3' => 'Cerai Mati'];
            return $labels[(string) $value] ?? '-';
        }

        if ($key === 'is_hidup') {
            return ((int) $value === 1) ? 'Hidup' : 'Meninggal';
        }

        return ($value !== null && $value !== '') ? esc((string) $value) : '-';
    }
}
