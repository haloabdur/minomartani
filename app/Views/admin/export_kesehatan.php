<?php

$filenameScope = $currentRt !== null ? ($rtOptions[$currentRt] ?? 'RT') : 'Gabungan';

header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Kesehatan_" . preg_replace('/[^A-Za-z0-9_-]+/', '_', $kegiatan->nama_kegiatan) . "_" . preg_replace('/[^A-Za-z0-9_-]+/', '_', $filenameScope) . "_" . date('dmY-His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
ob_end_clean();
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= esc($kegiatan->nama_kegiatan) ?></title>
        <style>
            table { border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px 6px; font-family: Arial, sans-serif; font-size: 11px; }
            th { background: #d9d9d9; font-weight: bold; text-align: center; }
            .judul { font-size: 16px; font-weight: bold; text-align: center; }
            .subjudul { font-size: 12px; font-weight: bold; text-align: center; }
            .info-label { font-weight: bold; font-size: 11px; }
            .info-value { font-size: 11px; }
            .rt-header { background: #f2f2f2; font-weight: bold; font-style: italic; text-align: left; }
            .center { text-align: center; }
            .keterangan-judul { font-weight: bold; font-size: 11px; }
        </style>
    </head>
    <body>
        <?php if (empty($groups)) : ?>
            <h2>Belum ada data kesehatan yang tercatat untuk kegiatan ini.</h2>
        <?php else : ?>
            <table cellspacing="0">
                <tr><td colspan="13" class="judul">REKAPAN PELAKSANAAN POSBINDU / POSYANDU LANSIA</td></tr>
                <tr><td colspan="13" class="subjudul"><?= esc(mb_strtoupper($scopeLabel)) ?> KALURAHAN <?= esc(mb_strtoupper($kalurahan)) ?></td></tr>
                <tr><td colspan="13">&nbsp;</td></tr>

                <tr>
                    <td class="info-label">NAMA KEGIATAN</td><td colspan="4" class="info-value"><?= esc($kegiatan->nama_kegiatan) ?></td>
                    <td class="info-label">TANGGAL PELAKSANAAN</td><td colspan="6" class="info-value"><?= esc(tanggal_indo($kegiatan->tanggal_kegiatan)) ?></td>
                </tr>
                <tr>
                    <td class="info-label">RW / RT</td><td colspan="4" class="info-value"><?= esc($scopeLabel) ?></td>
                    <td class="info-label">JUMLAH PESERTA</td><td colspan="6" class="info-value"><?= (int) $totalPeserta ?> orang</td>
                </tr>
                <tr>
                    <td class="info-label">KATEGORI</td><td colspan="4" class="info-value"><?= esc(ucfirst($kegiatan->kategori)) ?></td>
                    <td class="info-label">DICETAK TANGGAL</td><td colspan="6" class="info-value"><?= esc(tanggal_indo(date('Y-m-d'))) ?></td>
                </tr>
                <tr>
                    <td class="info-label">KALURAHAN</td><td colspan="4" class="info-value"><?= esc($kalurahan) ?></td>
                    <td colspan="7">&nbsp;</td>
                </tr>
                <tr><td colspan="13">&nbsp;</td></tr>

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
                    <th>BB (kg)</th>
                    <th>TB (cm)</th>
                    <th>LP (cm)</th>
                    <th>TD (mmHg)</th>
                    <th>GDS (mg/dL)</th>
                    <th>AU (mg/dL)</th>
                    <th>KOL (mg/dL)</th>
                </tr>

                <?php foreach ($groups as $group) : ?>
                    <?php if ($multiRt) : ?>
                        <tr>
                            <td colspan="13" class="rt-header"><?= esc($group['rt']->nama) ?> &mdash; <?= count($group['items']) ?> peserta</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($group['items'] as $i => $p) : ?>
                        <?php $c = $catatan[(int) $p->id_warga] ?? null; ?>
                        <tr>
                            <td class="center"><?= $i + 1 ?></td>
                            <td><?= esc($p->nama_warga) ?></td>
                            <td class="center" style="mso-number-format:'\@';"><?= esc($p->nik) ?></td>
                            <td class="center"><?= esc(tanggal($p->tanggal_lahir)) ?></td>
                            <td><?= esc($p->alamat_lengkap ?: '-') ?></td>
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
            </table>

            <br>
            <table cellspacing="0" style="border:none;">
                <tr><td colspan="2" style="border:none;" class="keterangan-judul">Keterangan:</td></tr>
                <tr>
                    <td style="border:none;">BB = Berat Badan (kg)</td>
                    <td style="border:none;">GDS = Gula Darah; (P) = puasa, (S) = sewaktu (mg/dL)</td>
                </tr>
                <tr>
                    <td style="border:none;">TB = Tinggi Badan (cm)</td>
                    <td style="border:none;">AU = Asam Urat (mg/dL)<?= $auDiperiksa ? '' : ' - tidak diperiksa pada kegiatan ini' ?></td>
                </tr>
                <tr>
                    <td style="border:none;">LP = Lingkar Perut (cm)</td>
                    <td style="border:none;">KOL = Kolesterol (mg/dL)<?= $kolDiperiksa ? '' : ' - tidak diperiksa pada kegiatan ini' ?></td>
                </tr>
                <tr>
                    <td style="border:none;">TD = Tekanan Darah, sistol/diastol (mmHg)</td>
                    <td style="border:none;">&nbsp;</td>
                </tr>
                <tr><td colspan="2" style="border:none;">&nbsp;</td></tr>
                <tr>
                    <td style="border:none;">Kader Posyandu,</td>
                    <td style="border:none;"><?= esc($signLabel) ?></td>
                </tr>
                <tr><td colspan="2" style="border:none;">&nbsp;</td></tr>
                <tr><td colspan="2" style="border:none;">&nbsp;</td></tr>
                <tr>
                    <td style="border:none;">( ___________________________ )</td>
                    <td style="border:none;">( ___________________________ )</td>
                </tr>
            </table>
        <?php endif; ?>
    </body>
</html>
