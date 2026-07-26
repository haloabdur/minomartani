<?php
$abnormal = array_filter(kesehatan_evaluate($catatan, $warga->jenis_kelamin ?? null), static fn ($f) => $f['level'] !== 'normal');

// Suggested filename for the browser's "Save as PDF" dialog: nama_rt_timestamp.
// Browsers derive the default save filename from document.title, sanitized to
// remove characters that aren't valid in a filename.
$printFilename = preg_replace('/_+/', '_', preg_replace('/[^A-Za-z0-9]+/', '_', $warga->nama_warga . '_' . $namaRt)) . '_' . date('YmdHis');
$printFilename = trim($printFilename, '_');

$rows = [
    'Tekanan Darah'  => ($catatan && $catatan->tensi_sistol !== null && $catatan->tensi_diastol !== null) ? $catatan->tensi_sistol . '/' . $catatan->tensi_diastol . ' mmHg' : '-',
    'Berat Badan'    => ($catatan && $catatan->berat_badan !== null) ? $catatan->berat_badan . ' kg' : '-',
    'Tinggi Badan'   => ($catatan && $catatan->tinggi_badan !== null) ? $catatan->tinggi_badan . ' cm' : '-',
    'Lingkar Perut'  => ($catatan && $catatan->lingkar_perut !== null) ? $catatan->lingkar_perut . ' cm' : '-',
    'Gula Darah'     => ($catatan && $catatan->gula_darah !== null) ? $catatan->gula_darah . ' mg/dL' . ($catatan->gula_darah_ket ? ' (' . $catatan->gula_darah_ket . ')' : '') : '-',
    'Kolesterol'     => ($catatan && $catatan->kolesterol !== null) ? $catatan->kolesterol . ' mg/dL' : '-',
    'Asam Urat'      => ($catatan && $catatan->asam_urat !== null) ? $catatan->asam_urat . ' mg/dL' : '-',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($printFilename) ?></title>
    <style>
        @page { size: A4; margin: 2cm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 14px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 0 0 20px; color: #555; font-weight: normal; }
        .header { border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #999; padding: 8px 10px; text-align: left; }
        th { background: #f0f0f0; width: 35%; }
        .biodata td:first-child { font-weight: bold; width: 35%; }
        .flag-danger { color: #a00; font-weight: bold; }
        .flag-warning { color: #a66a00; font-weight: bold; }
        .catatan-box { border: 1px solid #999; padding: 10px; min-height: 40px; }
        .footer { margin-top: 40px; font-size: 12px; color: #777; }
        .no-print { text-align: center; margin-bottom: 16px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>

    <div class="header">
        <h1>Catatan Kesehatan Warga</h1>
        <h2><?= esc($kegiatan->nama_kegiatan) ?> &mdash; <?= tanggal($kegiatan->tanggal_kegiatan) ?></h2>
    </div>

    <table class="biodata">
        <tr><td>Nama</td><td><?= esc($warga->nama_warga) ?></td></tr>
        <tr><td>Usia</td><td><?= $usia ?> tahun</td></tr>
        <tr><td>Jenis Kelamin</td><td><?= $warga->jenis_kelamin === 'P' ? 'Perempuan' : ($warga->jenis_kelamin === 'L' ? 'Laki-laki' : '-') ?></td></tr>
        <tr><td>RT</td><td><?= esc($namaRt) ?></td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Pemeriksaan</th><th>Hasil</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $label => $value) : ?>
                <tr><th><?= esc($label) ?></th><td><?= esc($value) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($abnormal)) : ?>
        <p><strong>Catatan Evaluasi:</strong></p>
        <ul>
            <?php foreach ($abnormal as $f) : ?>
                <li class="flag-<?= $f['level'] ?>"><?= esc($f['label']) ?>: <?= esc($f['note']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><strong>Catatan Tambahan:</strong></p>
    <div class="catatan-box"><?= esc($catatan->catatan ?? '-') ?></div>

    <div class="footer">
        Dicetak pada <?= tanggal(date('Y-m-d')) ?> &middot; Dokumen ini bersifat catatan pemeriksaan posyandu, bukan diagnosis medis.
    </div>

    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
