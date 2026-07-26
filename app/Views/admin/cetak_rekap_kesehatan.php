<?php
$filenameScope = $currentRt !== null ? ($rtOptions[$currentRt] ?? 'RT') : 'Gabungan';
$exportTitle    = 'Rekap_Kesehatan_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $kegiatan->nama_kegiatan) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $filenameScope) . '_' . date('dmY-His');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($exportTitle) ?></title>
    <style>
        @page { size: A4 landscape; margin: 1cm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 11px; }
        h1 { font-size: 16px; margin: 0; text-align: center; }
        h2 { font-size: 12px; margin: 2px 0 14px; text-align: center; font-weight: bold; color: #333; }
        .info { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .info table { border-collapse: collapse; }
        .info td { padding: 1px 4px; font-size: 11px; vertical-align: top; }
        .info td.label { font-weight: bold; white-space: nowrap; }
        table.rekap { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.rekap th, table.rekap td { border: 1px solid #333; padding: 3px 5px; }
        table.rekap th { background: #d9d9d9; font-size: 10px; text-align: center; }
        table.rekap td { font-size: 10px; }
        table.rekap td.center { text-align: center; }
        .legend { display: flex; justify-content: space-between; margin-top: 6px; font-size: 10px; }
        .legend .col { width: 48%; }
        .legend p { margin: 2px 0; }
        .tanda-tangan { display: flex; justify-content: space-between; margin-top: 34px; font-size: 11px; }
        .tanda-tangan .kolom { width: 45%; }
        .tanda-tangan .garis { margin-top: 46px; }
        .no-print { text-align: center; margin-bottom: 14px; }
        @media print { .no-print { display: none; } tr { page-break-inside: avoid; } }
        .empty { text-align: center; color: #777; padding: 30px 0; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>

    <h1>REKAPAN PELAKSANAAN POSBINDU / POSYANDU LANSIA</h1>
    <h2><?= esc(mb_strtoupper($scopeLabel)) ?> KALURAHAN <?= esc(mb_strtoupper($kalurahan)) ?></h2>

    <?php if (empty($groups)) : ?>
        <p class="empty">Belum ada data kesehatan yang tercatat untuk kegiatan ini.</p>
    <?php else : ?>
        <div class="info">
            <table>
                <tr><td class="label">NAMA KEGIATAN</td><td>:</td><td><?= esc($kegiatan->nama_kegiatan) ?></td></tr>
                <tr><td class="label">RW / RT</td><td>:</td><td><?= esc($scopeLabel) ?></td></tr>
                <tr><td class="label">TANGGAL PELAKSANAAN</td><td>:</td><td><?= esc(tanggal_indo($kegiatan->tanggal_kegiatan)) ?></td></tr>
                <tr><td class="label">KALURAHAN</td><td>:</td><td><?= esc($kalurahan) ?></td></tr>
            </table>
        </div>

        <table class="rekap">
            <thead>
                <tr>
                    <th rowspan="2">NO</th>
                    <th rowspan="2">NAMA</th>
                    <th rowspan="2">NIK</th>
                    <th rowspan="2">TANGGAL LAHIR</th>
                    <th rowspan="2">ALAMAT</th>
                    <th colspan="7">HASIL PEMERIKSAAN</th>
                    <th rowspan="2">KETERANGAN</th>
                </tr>
                <tr>
                    <th>BB</th>
                    <th>TB</th>
                    <th>LP</th>
                    <th>TD</th>
                    <th>GDS</th>
                    <th>AU</th>
                    <th>KOL</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 0; ?>
                <?php foreach ($groups as $group) : ?>
                    <?php foreach ($group['items'] as $p) : ?>
                        <?php $no++; $c = $catatan[(int) $p->id_warga] ?? null; ?>
                        <tr>
                            <td class="center"><?= $no ?></td>
                            <td><?= esc(kesehatan_nama_text($p->nama_warga, $p->jenis_kelamin ?? null)) ?></td>
                            <td class="center"><?= esc($p->nik) ?></td>
                            <td class="center"><?= esc(tanggal($p->tanggal_lahir)) ?></td>
                            <td><?= esc(kesehatan_alamat_text($p->alamat_lengkap)) ?></td>
                            <td class="center"><?= esc(kesehatan_num_text($c->berat_badan ?? null)) ?></td>
                            <td class="center"><?= esc(kesehatan_num_text($c->tinggi_badan ?? null)) ?></td>
                            <td class="center"><?= esc(kesehatan_num_text($c->lingkar_perut ?? null)) ?></td>
                            <td class="center"><?= esc(kesehatan_td_text($c)) ?></td>
                            <td class="center"><?= esc(kesehatan_gds_text($c)) ?></td>
                            <td class="center"><?= esc(kesehatan_num_text($c->asam_urat ?? null)) ?></td>
                            <td class="center"><?= esc(kesehatan_num_text($c->kolesterol ?? null)) ?></td>
                            <td><?= esc($c->catatan ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="legend">
            <div class="col">
                <p><strong>Keterangan:</strong></p>
                <p>BB = Berat Badan (kg)</p>
                <p>TB = Tinggi Badan (cm)</p>
                <p>LP = Lingkar Perut (cm)</p>
                <p>TD = Tekanan Darah, sistol/diastol (mmHg)</p>
            </div>
            <div class="col">
                <p>&nbsp;</p>
                <p>GDS = Gula Darah; (P) = puasa, (S) = sewaktu (mg/dL)</p>
                <p>AU = Asam Urat (mg/dL)<?= $auDiperiksa ? '' : ' - tidak diperiksa pada kegiatan ini' ?></p>
                <p>KOL = Kolesterol (mg/dL)<?= $kolDiperiksa ? '' : ' - tidak diperiksa pada kegiatan ini' ?></p>
            </div>
        </div>

        <div class="tanda-tangan">
            <div class="kolom">
                Kader Posyandu,
                <div class="garis">( ___________________________ )</div>
            </div>
            <div class="kolom">
                <?= esc($signLabel) ?>
                <div class="garis">( ___________________________ )</div>
            </div>
        </div>
    <?php endif; ?>

    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
