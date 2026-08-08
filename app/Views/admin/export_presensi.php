<?php

$filenameScope = $currentRt !== null ? ($rtOptions[$currentRt] ?? 'RT') : 'Gabungan';

header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Daftar_Hadir_" . preg_replace('/[^A-Za-z0-9_-]+/', '_', $acara->nama_acara) . "_" . preg_replace('/[^A-Za-z0-9_-]+/', '_', $filenameScope) . "_" . date('dmY-His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
ob_end_clean();

/** Column count, kept in one place because every header row spans it. */
$kolom = $multiRt ? 8 : 7;
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= esc($acara->nama_acara) ?></title>
        <style>
            /* Fixed print layout: A4 portrait, minimal margins. */
            @page Section1 {
                size: 21cm 29.7cm;
                mso-page-orientation: portrait;
                margin: .6cm .5cm .6cm .5cm;
                mso-header-margin: .2cm;
                mso-footer-margin: .2cm;
            }
            div.Section1 { page: Section1; }
            table { border-collapse: collapse; table-layout: fixed; }
            th, td { border: 1px solid #000; padding: 3px 5px; font-family: Arial, sans-serif; font-size: 11px; overflow-wrap: break-word; }
            th { font-weight: bold; text-align: center; }
            .judul { font-size: 16px; font-weight: bold; text-align: center; }
            .subjudul { font-size: 12px; font-weight: bold; text-align: center; }
            .info-label { font-weight: bold; font-size: 11px; }
            .info-value { font-size: 11px; }
            .center { text-align: center; }
            .grup { font-weight: bold; background: #eee; }
        </style>
    </head>
    <body>
        <?php if (empty($groups)) : ?>
            <h2>Belum ada warga terdaftar untuk acara ini.</h2>
        <?php else : ?>
        <div class="Section1">
            <table cellspacing="0">
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
                <tr><td colspan="<?= $kolom ?>" class="judul">DAFTAR HADIR</td></tr>
                <tr><td colspan="<?= $kolom ?>" class="subjudul"><?= esc(mb_strtoupper($scopeLabel)) ?> KALURAHAN <?= esc(mb_strtoupper($kalurahan)) ?></td></tr>
                <tr><td colspan="<?= $kolom ?>">&nbsp;</td></tr>

                <tr><td class="info-label">NAMA ACARA</td><td colspan="4" class="info-value"><?= esc($acara->nama_acara) ?></td></tr>
                <tr><td class="info-label">RW / RT</td><td colspan="4" class="info-value"><?= esc($scopeLabel) ?></td></tr>
                <tr><td class="info-label">TANGGAL</td><td colspan="4" class="info-value"><?= esc(tanggal_indo($acara->tanggal_acara)) ?></td></tr>
                <tr><td class="info-label">TEMPAT</td><td colspan="4" class="info-value"><?= esc($acara->tempat ?: '-') ?></td></tr>
                <tr><td class="info-label">KALURAHAN</td><td colspan="4" class="info-value"><?= esc($kalurahan) ?></td></tr>
                <tr>
                    <td class="info-label">REKAP</td>
                    <td colspan="4" class="info-value">
                        Hadir <?= (int) $totalHadir ?> &middot; Tidak hadir <?= (int) $totalTidak ?> &middot; Belum dipresensi <?= (int) $totalBelum ?> &middot; Total <?= (int) $totalPeserta ?>
                    </td>
                </tr>
                <tr><td colspan="<?= $kolom ?>">&nbsp;</td></tr>

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
                            <td class="center" style="mso-number-format:'\@';"><?= esc($p->nik) ?></td>
                            <?php if ($multiRt) : ?><td class="center"><?= esc($p->nama_rt ?? '-') ?></td><?php endif; ?>
                            <td><?= esc($p->alamat ?: '-') ?></td>
                            <td class="center"><?= esc($status) ?></td>
                            <td class="center"><?= $row !== null && ! empty($row->waktu) ? esc(date('H:i', strtotime($row->waktu))) : '-' ?></td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>

            <br>
            <table cellspacing="0" style="border:none;">
                <tr>
                    <td style="border:none;">Notulen/Petugas Presensi,</td>
                    <td style="border:none;"><?= esc($signLabel) ?></td>
                </tr>
                <tr><td colspan="2" style="border:none;">&nbsp;</td></tr>
                <tr><td colspan="2" style="border:none;">&nbsp;</td></tr>
                <tr>
                    <td style="border:none;">( ___________________________ )</td>
                    <td style="border:none;">( ___________________________ )</td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
    </body>
</html>
