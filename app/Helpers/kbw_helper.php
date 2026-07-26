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
 * Format date as "26 Juli 2026" (Indonesian long form), for printed/
 * exported documents (rekap headers, letters) where tanggal()'s
 * numeric d-m-Y is too terse.
 */
if (!function_exists('tanggal_indo')) {
    function tanggal_indo($date)
    {
        if (empty($date)) {
            return '-';
        }
        $ts = strtotime($date);

        return date('j', $ts) . ' ' . bulan_indo((int) date('n', $ts)) . ' ' . date('Y', $ts);
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
 * Whether a kesehatan_catatan row has any actual health measurement
 * filled in, vs. being a blank placeholder row created when a resident
 * is added to a kegiatan's participant list but not yet measured.
 */
if (!function_exists('kesehatan_has_data')) {
    function kesehatan_has_data(?object $row): bool
    {
        if ($row === null) {
            return false;
        }
        foreach (['tensi_sistol', 'tensi_diastol', 'berat_badan', 'tinggi_badan', 'lingkar_perut', 'gula_darah', 'kolesterol', 'asam_urat', 'catatan'] as $field) {
            if ($row->{$field} !== null && $row->{$field} !== '') {
                return true;
            }
        }
        return false;
    }
}

/**
 * A single numeric measurement (berat_badan, tinggi_badan, ...) for
 * display on a printed/exported rekap: trims a trailing ".00" so
 * whole-number results ("65") don't print as "65.00", but keeps real
 * decimals ("65.5"). Returns '-' for null/empty, matching the blank-cell
 * convention used across the rekap exports.
 */
if (!function_exists('kesehatan_num_text')) {
    function kesehatan_num_text($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $num = (float) $value;

        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.') ?: '0';
    }
}

/**
 * Display-ready participant name for a rekap: "Bp. "/"Ibu " gender prefix
 * (from jenis_kelamin 'L'/'P') plus title case, since names are stored as
 * typed (often all-caps) in the warga table.
 */
if (!function_exists('kesehatan_nama_text')) {
    function kesehatan_nama_text(?string $nama, ?string $jenisKelamin): string
    {
        $nama   = ucwords(strtolower(trim((string) $nama)));
        $prefix = $jenisKelamin === 'L' ? 'Bp. ' : ($jenisKelamin === 'P' ? 'Ibu ' : '');

        return $prefix . $nama;
    }
}

/**
 * Title-cased alamat_lengkap for a rekap, but leaves Roman-numeral street/
 * block tokens (VIII, IV, ...) untouched instead of mangling them into
 * "Viii" the way plain ucwords() would.
 */
if (!function_exists('kesehatan_alamat_text')) {
    function kesehatan_alamat_text(?string $alamat): string
    {
        $alamat = trim((string) $alamat);
        if ($alamat === '') {
            return '-';
        }

        return preg_replace_callback('/\p{L}+/u', static function (array $m) {
            $word = $m[0];
            if (preg_match('/^(?=[IVXLCDM])M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/i', $word)) {
                return $word;
            }

            return ucfirst(strtolower($word));
        }, $alamat);
    }
}

/**
 * "155/83" tekanan darah text for a kesehatan_catatan row, or '-' when
 * either half is missing.
 */
if (!function_exists('kesehatan_td_text')) {
    function kesehatan_td_text(?object $row): string
    {
        if ($row === null || $row->tensi_sistol === null || $row->tensi_diastol === null) {
            return '-';
        }

        return kesehatan_num_text($row->tensi_sistol) . '/' . kesehatan_num_text($row->tensi_diastol);
    }
}

/**
 * Gula darah text with a (P)uasa/(S)ewaktu suffix, since a single rekap
 * (especially the RW-wide "gabungan" export) commonly mixes both test
 * types across participants - the plain number alone would be
 * ambiguous/misleading next to the GDS column header.
 */
if (!function_exists('kesehatan_gds_text')) {
    function kesehatan_gds_text(?object $row): string
    {
        if ($row === null || $row->gula_darah === null || $row->gula_darah === '') {
            return '-';
        }
        $suffix = $row->gula_darah_ket === 'puasa' ? ' (P)' : ($row->gula_darah_ket === 'sewaktu' ? ' (S)' : '');

        return kesehatan_num_text($row->gula_darah) . $suffix;
    }
}

/**
 * A kesehatan_catatan row's filled-in measurements as separate labeled
 * parts (e.g. ["TD 120/80", "BB 65kg", "GD 110 (puasa)"]), for showing
 * recorded data inline under a participant's name without expanding the
 * form. Callers render each part as its own badge so the separation is
 * visually clear on one line.
 *
 * @return string[]
 */
if (!function_exists('kesehatan_summary_parts')) {
    function kesehatan_summary_parts(?object $row): array
    {
        if ($row === null) {
            return [];
        }

        $parts = [];

        if ($row->tensi_sistol !== null && $row->tensi_diastol !== null) {
            $parts[] = 'TD ' . $row->tensi_sistol . '/' . $row->tensi_diastol;
        }
        if ($row->berat_badan !== null) {
            $parts[] = 'BB ' . number_format((float) $row->berat_badan, 2, '.', '') . 'kg';
        }
        if ($row->tinggi_badan !== null) {
            $parts[] = 'TB ' . number_format((float) $row->tinggi_badan, 2, '.', '') . 'cm';
        }
        if ($row->lingkar_perut !== null) {
            $parts[] = 'LP ' . number_format((float) $row->lingkar_perut, 2, '.', '') . 'cm';
        }
        if ($row->gula_darah !== null) {
            $parts[] = 'GD ' . number_format((float) $row->gula_darah, 2, '.', '') . ($row->gula_darah_ket ? ' (' . $row->gula_darah_ket . ')' : '');
        }
        if ($row->kolesterol !== null) {
            $parts[] = 'Kol ' . number_format((float) $row->kolesterol, 2, '.', '');
        }
        if ($row->asam_urat !== null) {
            $parts[] = 'AU ' . number_format((float) $row->asam_urat, 2, '.', '');
        }

        return $parts;
    }
}

/**
 * Evaluate a kesehatan_catatan row's measurements against general medical
 * screening reference ranges (Kemenkes RI / PERKENI for blood sugar and
 * waist circumference, AHA for blood pressure, Asia-Pacific/Kemenkes
 * cutoffs for BMI), so the admin UI can flag values that fall outside the
 * normal range. This is a rough screening aid for posyandu-style checks,
 * not a diagnosis - abnormal flags should be followed up by a health
 * professional, not acted on directly.
 *
 * @return array<int, array{label: string, level: 'normal'|'warning'|'danger', note: string}>
 */
if (!function_exists('kesehatan_evaluate')) {
    function kesehatan_evaluate(?object $row, ?string $jenisKelamin = null): array
    {
        if ($row === null) {
            return [];
        }

        $flags = [];

        if ($row->tensi_sistol !== null && $row->tensi_diastol !== null) {
            $sistol  = (float) $row->tensi_sistol;
            $diastol = (float) $row->tensi_diastol;
            if ($sistol >= 140 || $diastol >= 90) {
                $flags[] = ['label' => 'Tensi', 'level' => 'danger', 'note' => 'Hipertensi (>=140/>=90)'];
            } elseif ($sistol >= 120 || $diastol >= 80) {
                $flags[] = ['label' => 'Tensi', 'level' => 'warning', 'note' => 'Agak tinggi (120-139/80-89)'];
            } else {
                $flags[] = ['label' => 'Tensi', 'level' => 'normal', 'note' => 'Normal (<120/<80)'];
            }
        }

        if ($row->berat_badan !== null && $row->tinggi_badan !== null && (float) $row->tinggi_badan > 0) {
            $tinggiM = ((float) $row->tinggi_badan) / 100;
            $imt     = ((float) $row->berat_badan) / ($tinggiM ** 2);
            $imtText = number_format($imt, 2, '.', '');
            if ($imt < 18.5) {
                $flags[] = ['label' => 'IMT', 'level' => 'warning', 'note' => 'Kurus (IMT ' . $imtText . ', <18.50)'];
            } elseif ($imt < 23) {
                $flags[] = ['label' => 'IMT', 'level' => 'normal', 'note' => 'Normal (IMT ' . $imtText . ')'];
            } elseif ($imt < 25) {
                $flags[] = ['label' => 'IMT', 'level' => 'warning', 'note' => 'Berisiko/overweight (IMT ' . $imtText . ', 23.00-24.99)'];
            } else {
                $flags[] = ['label' => 'IMT', 'level' => 'danger', 'note' => 'Obesitas (IMT ' . $imtText . ', >=25.00)'];
            }
        }

        if ($row->lingkar_perut !== null) {
            $lp    = (float) $row->lingkar_perut;
            $batas = ($jenisKelamin === 'P') ? 80 : 90;
            $batasText = number_format($batas, 2, '.', '');
            if ($lp >= $batas) {
                $flags[] = ['label' => 'Lingkar Perut', 'level' => 'danger', 'note' => 'Obesitas sentral (>=' . $batasText . 'cm)'];
            } else {
                $flags[] = ['label' => 'Lingkar Perut', 'level' => 'normal', 'note' => 'Normal (<' . $batasText . 'cm)'];
            }
        }

        if ($row->gula_darah !== null) {
            $gd    = (float) $row->gula_darah;
            $puasa = ($row->gula_darah_ket === 'puasa');
            if ($puasa) {
                if ($gd >= 126) {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'danger', 'note' => 'Diabetes (puasa >=126.00)'];
                } elseif ($gd >= 100) {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'warning', 'note' => 'Prediabetes (puasa 100.00-125.99)'];
                } else {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'normal', 'note' => 'Normal (puasa <100.00)'];
                }
            } else {
                if ($gd >= 200) {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'danger', 'note' => 'Diabetes (sewaktu >=200.00)'];
                } elseif ($gd >= 140) {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'warning', 'note' => 'Prediabetes (sewaktu 140.00-199.99)'];
                } else {
                    $flags[] = ['label' => 'Gula Darah', 'level' => 'normal', 'note' => 'Normal (sewaktu <140.00)'];
                }
            }
        }

        if ($row->kolesterol !== null) {
            $kol = (float) $row->kolesterol;
            if ($kol >= 240) {
                $flags[] = ['label' => 'Kolesterol', 'level' => 'danger', 'note' => 'Tinggi (>=240.00)'];
            } elseif ($kol >= 200) {
                $flags[] = ['label' => 'Kolesterol', 'level' => 'warning', 'note' => 'Ambang batas (200.00-239.99)'];
            } else {
                $flags[] = ['label' => 'Kolesterol', 'level' => 'normal', 'note' => 'Normal (<200.00)'];
            }
        }

        if ($row->asam_urat !== null) {
            $au    = (float) $row->asam_urat;
            $batas = ($jenisKelamin === 'P') ? 6.0 : 7.0;
            $batasText = number_format($batas, 2, '.', '');
            if ($au > $batas) {
                $flags[] = ['label' => 'Asam Urat', 'level' => 'danger', 'note' => 'Tinggi/hiperurisemia (>' . $batasText . ')'];
            } else {
                $flags[] = ['label' => 'Asam Urat', 'level' => 'normal', 'note' => 'Normal (<=' . $batasText . ')'];
            }
        }

        return $flags;
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
