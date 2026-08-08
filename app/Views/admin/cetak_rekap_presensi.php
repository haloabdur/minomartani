<?php
$filenameScope = $currentRt !== null ? ($rtOptions[$currentRt] ?? 'RT') : 'Gabungan';
$exportTitle   = 'Daftar_Hadir_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $acara->nama_acara) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $filenameScope) . '_' . date('dmY-His');
$kolom         = $multiRt ? 8 : 7;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($exportTitle) ?></title>
    <style>
        @page { size: A4 portrait; margin: 0.7cm 0.6cm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 11px; }
        h1 { font-size: 16px; margin: 0; text-align: center; }
        h2 { font-size: 12px; margin: 2px 0 14px; text-align: center; font-weight: bold; color: #333; }
        .info { margin-bottom: 12px; }
        .info table { border-collapse: collapse; }
        .info td { padding: 1px 4px; font-size: 11px; vertical-align: top; }
        .info td.label { font-weight: bold; white-space: nowrap; }
        table.rekap { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 14px; }
        table.rekap th, table.rekap td { border: 1px solid #000; padding: 3px 5px; overflow-wrap: break-word; }
        table.rekap th { font-size: 10px; text-align: center; }
        table.rekap td { font-size: 10px; }
        table.rekap td.center { text-align: center; }
        table.rekap td.grup { font-weight: bold; background: #eee; }
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

    <h1>DAFTAR HADIR</h1>
    <h2><?= esc(mb_strtoupper($scopeLabel)) ?> KALURAHAN <?= esc(mb_strtoupper($kalurahan)) ?></h2>

    <?php if (empty($groups)) : ?>
        <p class="empty">Belum ada warga terdaftar untuk acara ini.</p>
    <?php else : ?>
        <div class="info">
            <table>
                <tr><td class="label">NAMA ACARA</td><td>:</td><td><?= esc($acara->nama_acara) ?></td></tr>
                <tr><td class="label">RW / RT</td><td>:</td><td><?= esc($scopeLabel) ?></td></tr>
                <tr><td class="label">TANGGAL</td><td>:</td><td><?= esc(tanggal_indo($acara->tanggal_acara)) ?></td></tr>
                <tr><td class="label">TEMPAT</td><td>:</td><td><?= esc($acara->tempat ?: '-') ?></td></tr>
                <tr><td class="label">KALURAHAN</td><td>:</td><td><?= esc($kalurahan) ?></td></tr>
                <tr>
                    <td class="label">REKAP</td><td>:</td>
                    <td>Hadir <?= (int) $totalHadir ?> &middot; Tidak hadir <?= (int) $totalTidak ?> &middot; Belum dipresensi <?= (int) $totalBelum ?> &middot; Total <?= (int) $totalPeserta ?></td>
                </tr>
            </table>
        </div>

        <table class="rekap">
            <colgroup>
                <col style="width:5%">
                <col style="width:26%">
                <col style="width:16%">
                <?php if ($multiRt) : ?><col style="width:8%"><?php endif; ?>
                <col style="width:15%">
                <col style="width:11%">
                <col style="width:9%">
                <col style="width:<?= $multiRt ? '10' : '18' ?>%">
            </colgroup>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>NAMA</th>
                    <th>NIK</th>
                    <?php if ($multiRt) : ?><th>RT</th><?php endif; ?>
                    <th>ALAMAT</th>
                    <th>STATUS</th>
                    <th>JAM</th>
                    <th>TANDA TANGAN</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 0; ?>
                <?php foreach ($groups as $group) : ?>
                    <?php if ($multiRt) : ?>
                        <tr><td colspan="<?= $kolom ?>" class="grup"><?= esc($group['rt']->nama) ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($group['items'] as $p) : ?>
                        <?php
                            $no++;
                            $row    = $kehadiran[(int) $p->id_warga] ?? null;
                            $status = $row === null ? 'Belum dipresensi' : ($row->status === 'hadir' ? 'Hadir' : 'Tidak hadir');
                        ?>
                        <tr>
                            <td class="center"><?= $no ?></td>
                            <td><?= esc($p->nama_warga) ?></td>
                            <td class="center"><?= esc($p->nik) ?></td>
                            <?php if ($multiRt) : ?><td class="center"><?= esc($p->nama_rt ?? '-') ?></td><?php endif; ?>
                            <td><?= esc($p->alamat ?: '-') ?></td>
                            <td class="center"><?= esc($status) ?></td>
                            <td class="center"><?= $row !== null && ! empty($row->waktu) ? esc(date('H:i', strtotime($row->waktu))) : '-' ?></td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tanda-tangan">
            <div class="kolom">
                Notulen/Petugas Presensi,
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
