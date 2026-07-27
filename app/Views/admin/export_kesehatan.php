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
            /* Fixed print layout: A4 landscape, minimal margins so the 13-column
               table never gets clipped left/right when printed from Excel. */
            @page Section1 {
                size: 29.7cm 21cm;
                mso-page-orientation: landscape;
                margin: .5cm .4cm .5cm .4cm;
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
            .keterangan-judul { font-weight: bold; font-size: 11px; }
        </style>
    </head>
    <body>
        <?php if (empty($groups)) : ?>
            <h2>Belum ada data kesehatan yang tercatat untuk kegiatan ini.</h2>
        <?php else : ?>
        <div class="Section1">
            <table cellspacing="0">
                <colgroup>
                    <col style="width:4.05%">
                    <col style="width:19.54%">
                    <col style="width:11.44%">
                    <col style="width:7.49%">
                    <col style="width:15.29%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:5.16%">
                    <col style="width:6.07%">
                </colgroup>
                <tr><td colspan="13" class="judul">REKAPAN PELAKSANAAN POSBINDU / POSYANDU LANSIA</td></tr>
                <tr><td colspan="13" class="subjudul"><?= esc(mb_strtoupper($scopeLabel)) ?> KALURAHAN <?= esc(mb_strtoupper($kalurahan)) ?></td></tr>
                <tr><td colspan="13">&nbsp;</td></tr>

                <tr>
                    <td class="info-label">NAMA KEGIATAN</td><td colspan="4" class="info-value"><?= esc($kegiatan->nama_kegiatan) ?></td>
                </tr>
                <tr>
                    <td class="info-label">RW / RT</td><td colspan="4" class="info-value"><?= esc($scopeLabel) ?></td>
                </tr>
                <tr>
                    <td class="info-label">TANGGAL PELAKSANAAN</td><td colspan="4" class="info-value"><?= esc(tanggal_indo($kegiatan->tanggal_kegiatan)) ?></td>
                </tr>
                <tr>
                    <td class="info-label">KALURAHAN</td><td colspan="4" class="info-value"><?= esc($kalurahan) ?></td>
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
                    <th>BB</th>
                    <th>TB</th>
                    <th>LP</th>
                    <th>TD</th>
                    <th>GDS</th>
                    <th>AU</th>
                    <th>KOL</th>
                </tr>

                <?php $no = 0; ?>
                <?php foreach ($groups as $group) : ?>
                    <?php foreach ($group['items'] as $p) : ?>
                        <?php $no++; $c = $catatan[(int) $p->id_warga] ?? null; ?>
                        <tr>
                            <td class="center"><?= $no ?></td>
                            <td><?= esc(kesehatan_nama_text($p->nama_warga, $p->jenis_kelamin ?? null)) ?></td>
                            <td class="center" style="mso-number-format:'\@';"><?= esc($p->nik) ?></td>
                            <td class="center"><?= esc(tanggal($p->tanggal_lahir)) ?></td>
                            <td><?= esc(kesehatan_alamat_text($p->alamat ? $p->alamat : null)) ?></td>
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
        </div>
        <?php endif; ?>
    </body>
</html>
