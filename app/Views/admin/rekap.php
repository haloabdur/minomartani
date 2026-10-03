<?php
// Calculate RW Overview Metrics
$total_rt         = count($rekap);
$total_warga      = 0;
$total_kk         = 0;
$total_l          = 0;
$total_p          = 0;
$total_surat      = 0;
$total_alamat     = 0;
$total_inventaris = 0;
$total_kesehatan  = 0;
$total_presensi   = 0;

foreach ($rekap as $rt) {
    $total_warga      += (int) $rt->jml_warga;
    $total_kk         += (int) $rt->jml_kk;
    $total_l          += (int) $rt->jml_l;
    $total_p          += (int) $rt->jml_p;
    $total_surat      += (int) $rt->jml_surat;
    $total_alamat     += (int) ($rt->jml_alamat ?? 0);
    $total_inventaris += (int) ($rt->jml_inventaris ?? 0);
    $total_kesehatan  += (int) ($rt->jml_kesehatan ?? 0);
    $total_presensi   += (int) ($rt->jml_presensi ?? 0);
}

// Sex ratio (Laki-laki per 100 Perempuan)
$sex_ratio = $total_p > 0 ? round(($total_l / $total_p) * 100, 1) : 0;
$pct_l     = $total_warga > 0 ? round(($total_l / $total_warga) * 100, 1) : 0;
$pct_p     = $total_warga > 0 ? round(($total_p / $total_warga) * 100, 1) : 0;
$avg_jiwa_kk = $total_kk > 0 ? round($total_warga / $total_kk, 1) : 0;

// Age demographics
$umur_balita  = 0; // 0-5
$umur_anak    = 0; // 6-11
$umur_remaja  = 0; // 12-25
$umur_dewasa  = 0; // 26-59
$umur_lansia  = 0; // 60+

// Education
$pend_belum   = 0;
$pend_sd      = 0;
$pend_smp     = 0;
$pend_sma     = 0;
$pend_kuliah  = 0;

// Marital status
$kawin_belum        = 0;
$kawin_kawin        = 0;
$kawin_cerai_hidup  = 0;
$kawin_cerai_mati   = 0;

// Blood group
$darah_counts = ['A' => 0, 'B' => 0, 'AB' => 0, 'O' => 0, 'Belum Terdata' => 0];

// Religion
$agama_counts = [];

// Occupations
$pekerjaan_counts = [];

// Clean water sources
$air_counts = [];

// Per-RT category breakdown
$rtCat = [];
foreach ($rekap as $rt) {
    $rtCat[$rt->id_rt] = [
        'age-balita'  => 0, 'age-anak' => 0, 'age-remaja' => 0, 'age-dewasa' => 0, 'age-lansia' => 0,
        'edu-belum'   => 0, 'edu-sd' => 0, 'edu-smp' => 0, 'edu-sma' => 0, 'edu-kuliah' => 0,
    ];
}

$today = new DateTime();
foreach ($wargas as $w) {
    // Age
    $age = 0;
    if (! empty($w->tanggal_lahir) && $w->tanggal_lahir !== '0000-00-00') {
        try {
            $tgl_lahir = new DateTime($w->tanggal_lahir);
            $age = $today->diff($tgl_lahir)->y;
        } catch (\Exception $e) {
            $age = 0;
        }
    }

    if ($age <= 5) {
        $umur_balita++;
        $ageKey = 'age-balita';
    } elseif ($age <= 11) {
        $umur_anak++;
        $ageKey = 'age-anak';
    } elseif ($age <= 25) {
        $umur_remaja++;
        $ageKey = 'age-remaja';
    } elseif ($age <= 59) {
        $umur_dewasa++;
        $ageKey = 'age-dewasa';
    } else {
        $umur_lansia++;
        $ageKey = 'age-lansia';
    }

    // Education
    $p = strtoupper(trim((string) ($w->pendidikan ?? '')));
    if ($p === '-' || empty($p) || $p === 'BELUM SEKOLAH' || $p === 'TIDAK SEKOLAH') {
        $pend_belum++;
        $eduKey = 'edu-belum';
    } elseif ($p === 'SD') {
        $pend_sd++;
        $eduKey = 'edu-sd';
    } elseif ($p === 'SMP') {
        $pend_smp++;
        $eduKey = 'edu-smp';
    } elseif ($p === 'SMA' || $p === 'SMK') {
        $pend_sma++;
        $eduKey = 'edu-sma';
    } else {
        $pend_kuliah++;
        $eduKey = 'edu-kuliah';
    }

    if (isset($rtCat[$w->id_rt])) {
        $rtCat[$w->id_rt][$ageKey]++;
        $rtCat[$w->id_rt][$eduKey]++;
    }

    // Marital status
    switch ((int) ($w->status_kawin ?? 0)) {
        case 1: $kawin_kawin++; break;
        case 2: $kawin_cerai_hidup++; break;
        case 3: $kawin_cerai_mati++; break;
        default: $kawin_belum++; break;
    }

    // Blood group
    $gd = strtoupper(trim((string) ($w->gol_darah ?? '')));
    if (in_array($gd, ['A', 'B', 'AB', 'O'], true)) {
        $darah_counts[$gd]++;
    } else {
        $darah_counts['Belum Terdata']++;
    }

    // Religion
    $ag = ucfirst(strtolower(trim((string) ($w->agama ?? 'Lainnya'))));
    if (empty($ag) || $ag === '-') {
        $ag = 'Belum Terdata';
    }
    $agama_counts[$ag] = ($agama_counts[$ag] ?? 0) + 1;

    // Occupation
    $pek = trim((string) ($w->nama_pekerjaan ?? 'Belum Diisi'));
    if (empty($pek) || $pek === '-') {
        $pek = 'Belum Diisi';
    }
    $pekerjaan_counts[$pek] = ($pekerjaan_counts[$pek] ?? 0) + 1;

    // Water source
    $air = trim((string) ($w->sumber_air ?? ''));
    if (! empty($air) && $air !== '-') {
        $air_counts[$air] = ($air_counts[$air] ?? 0) + 1;
    }
}

arsort($pekerjaan_counts);
arsort($agama_counts);
arsort($air_counts);

// Vulnerable population (Balita + Lansia) & Dependency Ratio
$total_rentan = $umur_balita + $umur_lansia;
$rasio_ketergantungan = $umur_dewasa > 0 ? round(($total_rentan / $umur_dewasa) * 100, 1) : 0;

$rwNamaDisplay = ($rwInfo !== null && ! empty($rwInfo->nama)) ? $rwInfo->nama : 'RW 06 Minomartani';
?>

<!-- Custom CSS for RW Aggregator Dashboard -->
<style>
    .rw-hero-card {
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        color: #ffffff;
    }
    .rw-hero-badge {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 50rem;
        padding: 5px 14px;
        font-size: 0.82rem;
        backdrop-filter: blur(4px);
    }
    .rw-kpi-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
    }
    .rw-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .rw-kpi-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .rw-nav-tabs .nav-link {
        font-weight: 600;
        font-size: 0.95rem;
        color: #495057;
        border-radius: 8px 8px 0 0;
        padding: 10px 18px;
    }
    .rw-kpi-card {
        display: flex;
        flex-direction: column;
    }
    .rw-kpi-card > .border-top,
    .rw-kpi-card > .progress-stacked-bar {
        margin-top: auto !important;
    }
    .rw-kpi-card > .progress-stacked-bar + div {
        margin-top: 8px;
    }
    .rw-nav-tabs .nav-link.active,
    .nav-tabs.rw-nav-tabs .nav-item .nav-link.active {
        color: #007bff !important;
        background-color: #fff !important;
        border-bottom-color: #fff;
    }
    .rw-nav-tabs .nav-link:not(.active) {
        background-color: transparent;
    }
    .progress-stacked-bar {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        display: flex;
        background-color: #e9ecef;
    }
    .badge-rt-pill {
        border-radius: 50rem;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .blood-chip {
        border-radius: 10px;
        padding: 12px 10px;
        text-align: center;
        background: #fff;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
    }
    .blood-chip:hover {
        border-color: #dc3545;
        box-shadow: 0 4px 12px rgba(220,53,69,0.15);
    }

    /* Print Styles */
    @media print {
        .no-print, .main-header, .main-sidebar, .main-footer, .btn, .nav-tabs, .filter-section, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
            display: none !important;
        }
        .content-wrapper, body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .tab-content > .tab-pane {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .print-only {
            display: block !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        th, td {
            border: 1px solid #333 !important;
            padding: 4px 6px !important;
            font-size: 9pt !important;
        }
    }
    .print-only {
        display: none;
    }
</style>

<!-- Formal Print Header -->
<div class="print-only mb-4 text-center">
    <h4 class="font-weight-bold mb-0">PEMERINTAH KABUPATEN SLEMAN - KAPANEWON DEPOK</h4>
    <h3 class="font-weight-bold mb-0">RUKUN WARGA (<?= esc($rwNamaDisplay) ?>)</h3>
    <p class="text-muted mb-2">Kalurahan Minomartani, Kapanewon Depok, Kabupaten Sleman, D.I. Yogyakarta</p>
    <div style="border-bottom: 2px solid #000; margin-bottom: 12px;"></div>
    <h5 class="font-weight-bold">LAPORAN REKAPITULASI KEPENDUDUKAN & KEWILAYAHAN TINGKAT RW</h5>
    <p class="small text-muted">Tanggal Cetak: <?= date('d F Y, H:i') ?> WIB</p>
</div>

<div class="container-fluid">
    <!-- RW Hero Banner -->
    <div class="card rw-hero-card p-4 mb-4 border-0 no-print">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="mb-3 mb-md-0">
                <div class="d-flex align-items-center mb-2">
                    <span class="rw-hero-badge mr-2">
                        <i class="fas fa-layer-group mr-1 text-warning"></i> Agregator Wilayah RW
                    </span>
                    <span class="rw-hero-badge">
                        <i class="fas fa-check-circle mr-1 text-success"></i> <?= $total_rt ?> RT Aktif Terhubung
                    </span>
                </div>
                <h2 class="font-weight-bold mb-1" style="letter-spacing: -0.5px;">
                    <i class="fas fa-sitemap mr-2"></i><?= esc($rwNamaDisplay) ?>
                </h2>
                <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                    Pusat konsolidasi dan analisis data kependudukan, sosial, kesehatan, serta aset dari seluruh RT binaan.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-light mr-2 mb-2" onclick="window.print()" title="Cetak Rekap RW">
                    <i class="fas fa-print mr-1"></i> Cetak Laporan
                </button>
                <button type="button" class="btn btn-success mb-2" id="btn-export-rekap-rw" title="Export CSV Data Agregat">
                    <i class="fas fa-file-excel mr-1"></i> Export Data Matriks
                </button>
            </div>
        </div>
    </div>

    <!-- Executive KPI Metric Cards (6 Summary Cards) -->
    <div class="row mb-4">
        <!-- 1. Total Warga -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Total Penduduk RW</span>
                        <h3 class="font-weight-bold text-dark mb-0 mt-1"><?= number_format($total_warga, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">Jiwa</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <!-- Mini Gender Progress Bar -->
                <div class="progress-stacked-bar mt-2 mb-2" title="L: <?= $total_l ?> (<?= $pct_l ?>%) | P: <?= $total_p ?> (<?= $pct_p ?>%)">
                    <div class="bg-primary" style="width: <?= $pct_l ?>%;"></div>
                    <div class="bg-danger" style="width: <?= $pct_p ?>%;"></div>
                </div>
                <div class="d-flex justify-content-between text-xs text-muted">
                    <span><i class="fas fa-male text-primary mr-1"></i> <strong><?= $total_l ?></strong> L (<?= $pct_l ?>%)</span>
                    <span><i class="fas fa-female text-danger mr-1"></i> <strong><?= $total_p ?></strong> P (<?= $pct_p ?>%)</span>
                    <span>Rasio: <strong><?= $sex_ratio ?></strong></span>
                </div>
            </div>
        </div>

        <!-- 2. Kepala Keluarga (KK) & Hunian -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Kepala Keluarga & Rumah</span>
                        <h3 class="font-weight-bold text-dark mb-0 mt-1"><?= number_format($total_kk, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">KK Terdata</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light text-warning">
                        <i class="fas fa-id-card"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-xs text-muted">
                    <span><i class="fas fa-home text-warning mr-1"></i> <strong><?= $total_alamat ?></strong> Unit Rumah/Alamat</span>
                    <span><i class="fas fa-user-friends text-secondary mr-1"></i> Rata-rata <strong><?= $avg_jiwa_kk ?></strong> Jiwa/KK</span>
                </div>
            </div>
        </div>

        <!-- 3. Kelompok Rentan (Balita & Lansia) -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Kelompok Usia Rentan</span>
                        <h3 class="font-weight-bold text-danger mb-0 mt-1"><?= number_format($total_rentan, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">Jiwa (<?= $total_warga > 0 ? round(($total_rentan/$total_warga)*100, 1) : 0 ?>%)</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light text-danger">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-xs">
                    <span class="text-info"><i class="fas fa-baby mr-1"></i> <strong><?= $umur_balita ?></strong> Balita (0-5 th)</span>
                    <span class="text-danger"><i class="fas fa-blind mr-1"></i> <strong><?= $umur_lansia ?></strong> Lansia (60+ th)</span>
                    <span class="text-muted" title="Rasio Ketergantungan terhadap Usia Dewasa">Dependensi: <strong><?= $rasio_ketergantungan ?>%</strong></span>
                </div>
            </div>
        </div>

        <!-- 4. Usia Produktif & Generasi Muda -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Usia Produktif & Pemuda</span>
                        <h3 class="font-weight-bold text-success mb-0 mt-1"><?= number_format($umur_dewasa, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">Jiwa Produktif (26-59 th)</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light text-success">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-xs text-muted">
                    <span><i class="fas fa-user-graduate text-info mr-1"></i> <strong><?= $umur_remaja ?></strong> Remaja (12-25 th)</span>
                    <span><i class="fas fa-child text-primary mr-1"></i> <strong><?= $umur_anak ?></strong> Anak (6-11 th)</span>
                </div>
            </div>
        </div>

        <!-- 5. Layanan Administrasi Surat -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Layanan Surat Pengantar</span>
                        <h3 class="font-weight-bold text-info mb-0 mt-1"><?= number_format($total_surat, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">Pengajuan</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light text-info">
                        <i class="fas fa-file-signature"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-xs text-muted">
                    <span><i class="fas fa-check-circle text-success mr-1"></i> Pengantar warga lintas <?= $total_rt ?> RT</span>
                    <span><i class="fas fa-envelope text-primary mr-1"></i> Administrasi Aktif</span>
                </div>
            </div>
        </div>

        <!-- 6. Sarana & Partisipasi Lingkungan -->
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card rw-kpi-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold text-xs tracking-wider">Kesehatan & Aset Bersama</span>
                        <h3 class="font-weight-bold text-purple mb-0 mt-1" style="color: #6f42c1;"><?= number_format($total_kesehatan, 0, ',', '.') ?> <span class="text-xs text-muted font-weight-normal">Pemeriksaan Posyandu</span></h3>
                    </div>
                    <div class="rw-kpi-icon bg-light" style="color: #6f42c1;">
                        <i class="fas fa-clinic-medical"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top text-xs text-muted">
                    <span><i class="fas fa-boxes text-secondary mr-1"></i> <strong><?= $total_inventaris ?></strong> Aset Inventaris</span>
                    <span><i class="fas fa-user-check text-success mr-1"></i> <strong><?= $total_presensi ?></strong> Presensi Kehadiran</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Navigation Tabs -->
    <div class="card card-primary card-outline card-outline-tabs border-0 shadow-sm mb-4">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs rw-nav-tabs" id="rekapRwTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-matriks-link" data-toggle="pill" href="#tab-matriks" role="tab" aria-controls="tab-matriks" aria-selected="true">
                        <i class="fas fa-th-list mr-1"></i> Matriks & Perbandingan Antar-RT
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-demografi-link" data-toggle="pill" href="#tab-demografi" role="tab" aria-controls="tab-demografi" aria-selected="false">
                        <i class="fas fa-chart-pie mr-1"></i> Demografi & Sosial Kependudukan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-layanan-link" data-toggle="pill" href="#tab-layanan" role="tab" aria-controls="tab-layanan" aria-selected="false">
                        <i class="fas fa-hands-helping mr-1"></i> Layanan, Kesehatan & Tata Kelola
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="rekapRwTabsContent">
                
                <!-- TAB 1: MATRIKS KOMPARASI ANTAR-RT -->
                <div class="tab-pane fade show active" id="tab-matriks" role="tabpanel" aria-labelledby="tab-matriks-link">
                    <!-- Filter Toolbar -->
                    <div class="filter-section p-3 bg-light rounded mb-3 border d-flex flex-wrap align-items-center justify-content-between">
                        <div class="d-flex align-items-center flex-wrap mb-2 mb-md-0">
                            <span class="font-weight-bold text-xs text-muted mr-3 text-uppercase"><i class="fas fa-filter mr-1"></i> Filter Cepat:</span>
                            <div class="btn-group btn-group-toggle btn-group-sm mr-2 mb-1" data-toggle="buttons">
                                <label class="btn btn-outline-secondary active btn-rt-filter" data-rt-target="all">
                                    <input type="radio" name="rt_filter" checked> Semua RT
                                </label>
                                <?php foreach ($rekap as $rt): ?>
                                    <label class="btn btn-outline-secondary btn-rt-filter" data-rt-target="<?= esc($rt->nama) ?>">
                                        <input type="radio" name="rt_filter"> <?= esc($rt->nama) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-info mr-2 p-2" id="filter-status-indicator">
                                <i class="fas fa-check mr-1"></i> Menampilkan Seluruh RT
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-danger" id="btn-reset-matrix-filter" style="display: none;">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Comparison Matrix Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover table-striped datatable" id="table-rekap-rt" style="width: 100%;">
                            <thead class="thead-light">
                                <tr>
                                    <th width="3%" class="text-center">No.</th>
                                    <th width="15%">Rukun Tetangga (RT)</th>
                                    <th width="14%">Ketua RT & Kontak</th>
                                    <th width="8%" class="text-center">Jumlah KK</th>
                                    <th width="9%" class="text-center">Total Jiwa</th>
                                    <th width="16%">Komposisi Gender</th>
                                    <th width="7%" class="text-center" title="Balita 0-5 tahun">Balita</th>
                                    <th width="7%" class="text-center" title="Lansia 60+ tahun">Lansia</th>
                                    <th width="7%" class="text-center" title="Rumah/Alamat Fisik Terdaftar">Rumah</th>
                                    <th width="6%" class="text-center" title="Layanan Surat">Surat</th>
                                    <th width="8%" class="text-center no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekap as $i => $rt): ?>
                                <?php 
                                    $cat = $rtCat[$rt->id_rt] ?? []; 
                                    $wargaCount = (int) $rt->jml_warga;
                                    $lCount = (int) $rt->jml_l;
                                    $pCount = (int) $rt->jml_p;
                                    $rtPctL = $wargaCount > 0 ? round(($lCount / $wargaCount) * 100) : 0;
                                    $rtPctP = 100 - $rtPctL;
                                ?>
                                <tr
                                    <?php foreach ($cat as $key => $count): ?>
                                    data-<?= $key ?>="<?= $count ?>"
                                    <?php endforeach ?>
                                    data-rt-name="<?= esc($rt->nama) ?>"
                                >
                                    <td class="text-center align-middle font-weight-bold"><?= $i + 1 ?></td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-primary font-size-14">
                                            <?= esc($rt->nama) ?>
                                        </div>
                                        <span class="text-xs text-muted"><?= esc($rt->slug) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if (! empty($rt->nama_ketua)): ?>
                                            <span class="font-weight-bold text-dark d-block"><?= esc($rt->nama_ketua) ?></span>
                                            <?php if (! empty($rt->no_wa)): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rt->no_wa) ?>" target="_blank" class="badge badge-success text-xs">
                                                    <i class="fab fa-whatsapp mr-1"></i> WA
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted text-xs font-italic">Belum diinput</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-warning badge-pill font-weight-bold" style="font-size: 0.85rem;">
                                            <?= $rt->jml_kk ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-dark font-size-15">
                                        <?= number_format($wargaCount, 0, ',', '.') ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="progress-stacked-bar mb-1">
                                            <div class="bg-primary" style="width: <?= $rtPctL ?>%;" title="Laki-laki: <?= $lCount ?> (<?= $rtPctL ?>%)"></div>
                                            <div class="bg-danger" style="width: <?= $rtPctP ?>%;" title="Perempuan: <?= $pCount ?> (<?= $rtPctP ?>%)"></div>
                                        </div>
                                        <div class="d-flex justify-content-between text-xs text-muted">
                                            <span class="text-primary font-weight-bold"><i class="fas fa-male"></i> <?= $lCount ?></span>
                                            <span class="text-danger font-weight-bold"><i class="fas fa-female"></i> <?= $pCount ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-info badge-pill font-weight-bold"><?= $cat['age-balita'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-danger badge-pill font-weight-bold"><?= $cat['age-lansia'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-secondary badge-pill"><?= $rt->jml_alamat ?? 0 ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light border font-weight-bold"><?= $rt->jml_surat ?></span>
                                    </td>
                                    <td class="text-center align-middle no-print">
                                        <div class="btn-group">
                                            <a href="<?= base_url('admin/rekap/warga/' . $rt->id_rt) ?>" class="btn btn-sm btn-outline-primary" title="Buka Detail Warga <?= esc($rt->nama) ?>">
                                                <i class="far fa-eye"></i> Detail
                                            </a>
                                            <?php if (can_export()): ?>
                                                <a href="<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>" class="btn btn-sm btn-outline-success" title="Export Excel Data <?= esc($rt->nama) ?>">
                                                    <i class="fas fa-file-excel"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot class="thead-dark font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right">TOTAL KESELURUHAN (<?= esc($rwNamaDisplay) ?>):</td>
                                    <td class="text-center"><?= number_format($total_kk, 0, ',', '.') ?> KK</td>
                                    <td class="text-center"><?= number_format($total_warga, 0, ',', '.') ?> Jiwa</td>
                                    <td class="text-center"><?= $total_l ?> L / <?= $total_p ?> P</td>
                                    <td class="text-center"><?= $umur_balita ?></td>
                                    <td class="text-center"><?= $umur_lansia ?></td>
                                    <td class="text-center"><?= $total_alamat ?></td>
                                    <td class="text-center"><?= $total_surat ?></td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Visual Comparison Charts Between RTs -->
                    <div class="row pt-3">
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 shadow-none border">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-chart-bar text-primary mr-1"></i> Perbandingan Jumlah Warga & KK per RT
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 280px; position: relative;">
                                        <canvas id="chartRtComparison"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 shadow-none border">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-venus-mars text-danger mr-1"></i> Komposisi Gender (Laki-laki vs Perempuan) per RT
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 280px; position: relative;">
                                        <canvas id="chartRtGender"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: DEMOGRAFI & SOSIAL KEPENDUDUKAN -->
                <div class="tab-pane fade" id="tab-demografi" role="tabpanel" aria-labelledby="tab-demografi-link">
                    <div class="row">
                        <!-- 1. Distribusi Kelompok Usia -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-birthday-cake text-success mr-1"></i> Distribusi Kelompok Usia RW
                                    </h5>
                                    <span class="badge badge-light border text-xs"><?= $total_warga ?> Jiwa</span>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                            <div style="height: 220px; position: relative;">
                                                <canvas id="chartUmurDoughnut"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <ul class="list-group list-group-flush text-xs">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-info mr-1"></i> Balita (0-5 th)</span>
                                                    <span class="font-weight-bold"><?= $umur_balita ?> jiwa (<?= $total_warga > 0 ? round(($umur_balita/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-success mr-1"></i> Anak-anak (6-11 th)</span>
                                                    <span class="font-weight-bold"><?= $umur_anak ?> jiwa (<?= $total_warga > 0 ? round(($umur_anak/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-warning mr-1"></i> Remaja (12-25 th)</span>
                                                    <span class="font-weight-bold"><?= $umur_remaja ?> jiwa (<?= $total_warga > 0 ? round(($umur_remaja/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-primary mr-1"></i> Dewasa/Produktif (26-59 th)</span>
                                                    <span class="font-weight-bold"><?= $umur_dewasa ?> jiwa (<?= $total_warga > 0 ? round(($umur_dewasa/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-danger mr-1"></i> Lansia (60+ th)</span>
                                                    <span class="font-weight-bold"><?= $umur_lansia ?> jiwa (<?= $total_warga > 0 ? round(($umur_lansia/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Tingkat Pendidikan -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-graduation-cap text-warning mr-1"></i> Jenjang Pendidikan Warga RW
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 240px; position: relative;">
                                        <canvas id="chartPendidikanBar"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Top Pekerjaan Warga -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-user-tie text-secondary mr-1"></i> Profesi & Mata Pencaharian Terbanyak
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 260px; position: relative;">
                                        <canvas id="chartPekerjaanHorizontal"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Status Perkawinan & Kependudukan -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-ring text-info mr-1"></i> Status Perkawinan Warga RW
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-sm-6 mb-3 mb-sm-0">
                                            <div style="height: 220px; position: relative;">
                                                <canvas id="chartKawinDoughnut"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <ul class="list-group list-group-flush text-xs">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-primary mr-1"></i> Belum Kawin</span>
                                                    <span class="font-weight-bold"><?= $kawin_belum ?> (<?= $total_warga > 0 ? round(($kawin_belum/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-success mr-1"></i> Kawin</span>
                                                    <span class="font-weight-bold"><?= $kawin_kawin ?> (<?= $total_warga > 0 ? round(($kawin_kawin/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-warning mr-1"></i> Cerai Hidup</span>
                                                    <span class="font-weight-bold"><?= $kawin_cerai_hidup ?> (<?= $total_warga > 0 ? round(($kawin_cerai_hidup/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <span><i class="fas fa-circle text-danger mr-1"></i> Cerai Mati</span>
                                                    <span class="font-weight-bold"><?= $kawin_cerai_mati ?> (<?= $total_warga > 0 ? round(($kawin_cerai_mati/$total_warga)*100, 1) : 0 ?>%)</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Bank Data Golongan Darah RW (Emergency Preparedness) -->
                        <div class="col-12 mb-4">
                            <div class="card border shadow-none">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-tint text-danger mr-1"></i> Bank Data Golongan Darah RW (Kesiapsiagaan Donor Darah & Darurat)
                                    </h5>
                                    <span class="badge badge-danger">Data Siaga Medis RW</span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php 
                                        $bloodColors = [
                                            'A' => '#e63946',
                                            'B' => '#457b9d',
                                            'AB' => '#8338ec',
                                            'O' => '#f77f00',
                                            'Belum Terdata' => '#6c757d'
                                        ];
                                        foreach ($darah_counts as $gol => $cnt): 
                                            $pct = $total_warga > 0 ? round(($cnt / $total_warga) * 100, 1) : 0;
                                        ?>
                                        <div class="col">
                                            <div class="blood-chip">
                                                <div class="text-xs text-muted mb-1">Golongan</div>
                                                <h3 class="font-weight-bold mb-1" style="color: <?= $bloodColors[$gol] ?>;">
                                                    <i class="fas fa-tint mr-1" style="font-size: 1.1rem;"></i><?= esc($gol) ?>
                                                </h3>
                                                <div class="font-weight-bold text-dark font-size-15"><?= $cnt ?> <span class="text-xs font-weight-normal text-muted">Jiwa</span></div>
                                                <span class="text-xs text-muted"><?= $pct ?>%</span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Sebaran Agama & Sanitasi / Sumber Air -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-praying-hands text-primary mr-1"></i> Komposisi Keagamaan Warga
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush text-xs">
                                        <?php foreach ($agama_counts as $ag => $cnt): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                            <span class="font-weight-bold"><?= esc($ag) ?></span>
                                            <span class="badge badge-light border badge-pill"><?= $cnt ?> jiwa (<?= $total_warga > 0 ? round(($cnt/$total_warga)*100, 1) : 0 ?>%)</span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-faucet text-info mr-1"></i> Sanitasi & Sumber Air Bersih
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush text-xs">
                                        <?php if (! empty($air_counts)): ?>
                                            <?php foreach ($air_counts as $air => $cnt): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                                <span class="font-weight-bold"><?= esc($air) ?></span>
                                                <span class="badge badge-light border badge-pill"><?= $cnt ?> warga terdata</span>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted text-center py-4">
                                                Belum ada data sumber air terinput pada kartu keluarga.
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: LAYANAN, KESEHATAN & TATA KELOLA RW -->
                <div class="tab-pane fade" id="tab-layanan" role="tabpanel" aria-labelledby="tab-layanan-link">
                    <div class="row">
                        <!-- Rekap Surat Pengantar RT -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-file-alt text-info mr-1"></i> Rekapitulasi Layanan Surat Pengantar
                                    </h5>
                                    <span class="badge badge-info"><?= $total_surat ?> Total Pengajuan</span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (! empty($suratSummary)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0 text-xs">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Jenis Surat / Maksud</th>
                                                        <th class="text-center" width="25%">Jumlah Pengajuan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($suratSummary as $s): ?>
                                                    <tr>
                                                        <td class="font-weight-bold"><?= esc($s->maksut) ?></td>
                                                        <td class="text-center">
                                                            <span class="badge badge-primary badge-pill"><?= $s->total ?> kali</span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2 text-muted"></i>
                                            <p class="mb-0 text-xs">Belum ada data pengajuan surat pengantar yang tercatat di lingkungan RT-RW.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Rekap Kegiatan Posyandu & Kesehatan Lansia -->
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border shadow-none">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-heartbeat text-danger mr-1"></i> Kegiatan Posyandu & Posbindu Terkini
                                    </h5>
                                    <span class="badge badge-danger"><?= $total_kesehatan ?> Pemeriksaan</span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (! empty($kesehatanKegiatan)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0 text-xs">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Nama Kegiatan</th>
                                                        <th>Tanggal</th>
                                                        <th>Tingkat</th>
                                                        <th class="text-center">Peserta</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($kesehatanKegiatan as $keg): ?>
                                                    <tr>
                                                        <td class="font-weight-bold"><?= esc($keg->nama_kegiatan) ?></td>
                                                        <td><?= date('d M Y', strtotime($keg->tanggal_kegiatan)) ?></td>
                                                        <td>
                                                            <?php if (! empty($keg->id_rw)): ?>
                                                                <span class="badge badge-purple" style="background-color: #6f42c1; color: #fff;">Gabungan RW</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary"><?= esc($keg->nama_rt ?? 'RT') ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-success badge-pill"><?= $keg->total_peserta ?? 0 ?> Lansia</span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="fas fa-clinic-medical fa-2x mb-2 text-muted"></i>
                                            <p class="mb-0 text-xs">Belum ada kegiatan Posyandu / pemeriksaan kesehatan yang tercatat.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Inventaris & Fasilitas Bersama -->
                        <div class="col-12 mb-4">
                            <div class="card border shadow-none">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="card-title font-weight-bold mb-0 text-sm">
                                        <i class="fas fa-boxes text-secondary mr-1"></i> Aset & Inventaris Sarana Prasarana Lingkungan
                                    </h5>
                                    <span class="badge badge-secondary"><?= $total_inventaris ?> Unit Terdata</span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (! empty($inventarisList)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0 text-xs">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th width="5%" class="text-center">No.</th>
                                                        <th>Nama Barang / Aset</th>
                                                        <th>Lokasi / Kepemilikan RT</th>
                                                        <th class="text-center" width="15%">Jumlah / Stok</th>
                                                        <th>Tanggal Pencatatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($inventarisList as $idx => $inv): ?>
                                                    <tr>
                                                        <td class="text-center"><?= $idx + 1 ?></td>
                                                        <td class="font-weight-bold"><?= esc($inv->nama_barang) ?></td>
                                                        <td><span class="badge badge-light border"><?= esc($inv->nama_rt ?? 'RT') ?></span></td>
                                                        <td class="text-center"><span class="badge badge-primary badge-pill"><?= $inv->stok ?> Unit</span></td>
                                                        <td><?= ! empty($inv->created_at) ? date('d M Y', strtotime($inv->created_at)) : '-' ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="fas fa-box-open fa-2x mb-2 text-muted"></i>
                                            <p class="mb-0 text-xs">Belum ada inventaris atau aset bersama yang terdaftar.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Formal Print Signature Footer (Only visible when printing) -->
    <div class="print-only mt-5 pt-4">
        <div class="row">
            <div class="col-6">
                <p class="mb-1 text-xs">Catatan / Keterangan RW:</p>
                <div style="border: 1px dashed #666; height: 80px; width: 90%; border-radius: 4px;"></div>
            </div>
            <div class="col-6 text-right">
                <p class="mb-1 text-xs">Sleman, <?= date('d F Y') ?></p>
                <p class="font-weight-bold text-xs mb-5">Ketua Rukun Warga (<?= esc($rwNamaDisplay) ?>)</p>
                <div style="margin-top: 60px;">
                    <p class="font-weight-bold text-xs mb-0">( ............................................................ )</p>
                    <span class="text-muted" style="font-size: 8pt;">NIP / Tanda Tangan & Stempel Resmi</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js 4.4.0 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // -------------------------------------------------------------
    // 1. DATASETS FOR CHARTS (FROM PHP)
    // -------------------------------------------------------------
    var rtLabels = <?= json_encode(array_map(static fn($r) => $r->nama, $rekap)) ?>;
    var rtWargaData = <?= json_encode(array_map(static fn($r) => (int)$r->jml_warga, $rekap)) ?>;
    var rtKkData = <?= json_encode(array_map(static fn($r) => (int)$r->jml_kk, $rekap)) ?>;
    var rtLData = <?= json_encode(array_map(static fn($r) => (int)$r->jml_l, $rekap)) ?>;
    var rtPData = <?= json_encode(array_map(static fn($r) => (int)$r->jml_p, $rekap)) ?>;

    // Age Groups
    var umurLabels = ['Balita (0-5)', 'Anak (6-11)', 'Remaja (12-25)', 'Dewasa (26-59)', 'Lansia (60+)'];
    var umurData = [<?= $umur_balita ?>, <?= $umur_anak ?>, <?= $umur_remaja ?>, <?= $umur_dewasa ?>, <?= $umur_lansia ?>];

    // Education
    var pendLabels = ['Belum Sekolah', 'SD', 'SMP', 'SMA/SMK', 'Perguruan Tinggi'];
    var pendData = [<?= $pend_belum ?>, <?= $pend_sd ?>, <?= $pend_smp ?>, <?= $pend_sma ?>, <?= $pend_kuliah ?>];

    // Marital Status
    var kawinLabels = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
    var kawinData = [<?= $kawin_belum ?>, <?= $kawin_kawin ?>, <?= $kawin_cerai_hidup ?>, <?= $kawin_cerai_mati ?>];

    // Top Occupations (Top 7 + Others)
    <?php
    $topPekerjaanKeys = array_slice(array_keys($pekerjaan_counts), 0, 7);
    $topPekerjaanVals = array_slice(array_values($pekerjaan_counts), 0, 7);
    ?>
    var pekLabels = <?= json_encode($topPekerjaanKeys) ?>;
    var pekData = <?= json_encode($topPekerjaanVals) ?>;

    // -------------------------------------------------------------
    // 2. INITIALIZE CHARTS
    // -------------------------------------------------------------
    // Chart A: RT Population & KK Comparison
    var ctxRtComp = document.getElementById('chartRtComparison');
    if (ctxRtComp) {
        new Chart(ctxRtComp, {
            type: 'bar',
            data: {
                labels: rtLabels,
                datasets: [
                    {
                        label: 'Total Warga (Jiwa)',
                        data: rtWargaData,
                        backgroundColor: 'rgba(0, 123, 255, 0.85)',
                        borderColor: '#007bff',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Kepala Keluarga (KK)',
                        data: rtKkData,
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Chart B: Gender Composition per RT
    var ctxRtGender = document.getElementById('chartRtGender');
    if (ctxRtGender) {
        new Chart(ctxRtGender, {
            type: 'bar',
            data: {
                labels: rtLabels,
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: rtLData,
                        backgroundColor: 'rgba(0, 123, 255, 0.85)',
                        borderRadius: 4
                    },
                    {
                        label: 'Perempuan',
                        data: rtPData,
                        backgroundColor: 'rgba(220, 53, 69, 0.85)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f0f0f0' } }
                }
            }
        });
    }

    // Chart C: Age Distribution Doughnut
    var ctxUmur = document.getElementById('chartUmurDoughnut');
    if (ctxUmur) {
        new Chart(ctxUmur, {
            type: 'doughnut',
            data: {
                labels: umurLabels,
                datasets: [{
                    data: umurData,
                    backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#007bff', '#dc3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });
    }

    // Chart D: Education Bar Chart
    var ctxPend = document.getElementById('chartPendidikanBar');
    if (ctxPend) {
        new Chart(ctxPend, {
            type: 'bar',
            data: {
                labels: pendLabels,
                datasets: [{
                    label: 'Jumlah Warga',
                    data: pendData,
                    backgroundColor: [
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(0, 123, 255, 0.8)'
                    ],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Chart E: Top Occupations Horizontal Bar
    var ctxPek = document.getElementById('chartPekerjaanHorizontal');
    if (ctxPek) {
        new Chart(ctxPek, {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: pekLabels,
                datasets: [{
                    label: 'Jumlah Warga',
                    data: pekData,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // Chart F: Marital Status Doughnut
    var ctxKawin = document.getElementById('chartKawinDoughnut');
    if (ctxKawin) {
        new Chart(ctxKawin, {
            type: 'doughnut',
            data: {
                labels: kawinLabels,
                datasets: [{
                    data: kawinData,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });
    }

    // Resize charts on Bootstrap tab switch so they display perfectly
    $('a[data-toggle="pill"]').on('shown.bs.tab', function() {
        window.dispatchEvent(new Event('resize'));
    });

    // -------------------------------------------------------------
    // 3. TABLE FILTERING & SEARCH
    // -------------------------------------------------------------
    var dataTable = $('#table-rekap-rt').DataTable();

    // Quick filter per RT button pills
    $('.btn-rt-filter').on('click', function() {
        var targetRt = $(this).data('rt-target');
        if (targetRt === 'all') {
            dataTable.column(1).search('').draw();
            $('#filter-status-indicator').html('<i class="fas fa-check mr-1"></i> Menampilkan Seluruh RT');
            $('#btn-reset-matrix-filter').hide();
        } else {
            dataTable.column(1).search(targetRt).draw();
            $('#filter-status-indicator').html('<i class="fas fa-filter mr-1"></i> Difilter: ' + targetRt);
            $('#btn-reset-matrix-filter').show();
        }
    });

    $('#btn-reset-matrix-filter').on('click', function() {
        dataTable.column(1).search('').draw();
        $('.btn-rt-filter').removeClass('active');
        $('.btn-rt-filter[data-rt-target="all"]').addClass('active');
        $('#filter-status-indicator').html('<i class="fas fa-check mr-1"></i> Menampilkan Seluruh RT');
        $(this).hide();
    });

    // -------------------------------------------------------------
    // 4. CLIENT-SIDE EXPORT CSV FOR RW MATRIX
    // -------------------------------------------------------------
    $('#btn-export-rekap-rw').on('click', function() {
        var csv = [];
        var rows = document.querySelectorAll("#table-rekap-rt tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            for (var j = 0; j < cols.length - 1; j++) { // exclude last action column
                var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""').trim();
                row.push('"' + text + '"');
            }
            csv.push(row.join(","));
        }

        var csvFile = new Blob(["\uFEFF" + csv.join("\n")], { type: "text/csv;charset=utf-8;" });
        var downloadLink = document.createElement("a");
        downloadLink.download = "Rekap_RW_Matriks_" + new Date().toISOString().slice(0,10) + ".csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });
});
</script>
