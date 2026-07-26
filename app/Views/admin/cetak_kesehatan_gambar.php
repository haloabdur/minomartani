<?php
$abnormal = array_filter(kesehatan_evaluate($catatan, $warga->jenis_kelamin ?? null), static fn ($f) => $f['level'] !== 'normal');

// Suggested filename for the downloaded PNG: nama_rt_timestamp, same pattern as cetak_kesehatan.php's PDF filename.
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
        body { font-family: Arial, Helvetica, sans-serif; color: #222; background: #e9ecef; margin: 0; padding: 20px; }
        .toolbar { max-width: 480px; margin: 0 auto 16px; text-align: center; }
        .toolbar button { font-size: 14px; padding: 8px 16px; margin: 0 4px; cursor: pointer; }
        .toolbar p { font-size: 12px; color: #555; margin: 8px 0 0; }
        #kartu {
            width: 480px; margin: 0 auto; background: #fff; box-sizing: border-box;
            padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        #hasil-wrap { max-width: 480px; margin: 16px auto 0; text-align: center; }
        #hasil-wrap img { max-width: 100%; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
        #hasil-wrap p { font-size: 12px; color: #555; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 17px; margin: 0 0 4px; }
        .header h2 { font-size: 13px; margin: 0; color: #555; font-weight: normal; }
        table.biodata { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.biodata td { padding: 4px 0; font-size: 13px; }
        table.biodata td:first-child { font-weight: bold; width: 40%; }
        table.hasil { width: 100%; border-collapse: collapse; }
        table.hasil th, table.hasil td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 13px; }
        table.hasil th { background: #f0f0f0; width: 45%; }
        .flag-danger { color: #a00; font-weight: bold; }
        .flag-warning { color: #a66a00; font-weight: bold; }
        .evaluasi { margin-top: 12px; font-size: 12px; }
        .evaluasi ul { margin: 4px 0 0; padding-left: 18px; }
        .footer { margin-top: 16px; font-size: 11px; color: #888; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="toolbar" id="toolbar">
        <button type="button" id="btn-download">Download Gambar</button>
        <button type="button" id="btn-copy">Copy Gambar</button>
        <p id="toolbar-status">Menyiapkan gambar...</p>
    </div>

    <div id="kartu">
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

        <table class="hasil">
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
            <div class="evaluasi">
                <strong>Catatan Evaluasi:</strong>
                <ul>
                    <?php foreach ($abnormal as $f) : ?>
                        <li class="flag-<?= $f['level'] ?>"><?= esc($f['label']) ?>: <?= esc($f['note']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="footer">
            Dicetak <?= tanggal(date('Y-m-d')) ?> &middot; Bukan diagnosis medis, hanya catatan pemeriksaan posyandu.
        </div>
    </div>

    <div id="hasil-wrap" class="hidden">
        <img id="hasil-img" alt="Catatan kesehatan <?= esc($warga->nama_warga) ?>">
        <p>Tidak bisa copy otomatis? Tekan &amp; tahan gambar di atas untuk menyalin/mengirim langsung ke WhatsApp.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var kartu     = document.getElementById('kartu');
        var status    = document.getElementById('toolbar-status');
        var btnDl     = document.getElementById('btn-download');
        var btnCopy   = document.getElementById('btn-copy');
        var hasilWrap = document.getElementById('hasil-wrap');
        var hasilImg  = document.getElementById('hasil-img');
        var filename  = <?= json_encode($printFilename) ?> + '.png';
        var canvas    = null;

        btnDl.disabled = true;
        btnCopy.disabled = true;

        html2canvas(kartu, { scale: 2, backgroundColor: '#ffffff' }).then(function (c) {
            canvas = c;
            hasilImg.src = canvas.toDataURL('image/png');
            hasilWrap.classList.remove('hidden');
            btnDl.disabled = false;
            btnCopy.disabled = false;
            status.textContent = 'Gambar siap.';
        }).catch(function () {
            status.textContent = 'Gagal membuat gambar. Coba muat ulang halaman ini.';
        });

        btnDl.addEventListener('click', function () {
            if (!canvas) { return; }
            var link = document.createElement('a');
            link.download = filename;
            link.href = canvas.toDataURL('image/png');
            link.click();
        });

        btnCopy.addEventListener('click', function () {
            if (!canvas) { return; }
            canvas.toBlob(function (blob) {
                if (navigator.clipboard && window.ClipboardItem) {
                    navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]).then(function () {
                        status.textContent = 'Gambar berhasil disalin, silakan paste ke WhatsApp.';
                    }).catch(function () {
                        status.textContent = 'Copy otomatis gagal, tekan & tahan gambar di bawah untuk menyalin.';
                    });
                } else {
                    status.textContent = 'Browser ini tidak mendukung copy otomatis, tekan & tahan gambar di bawah untuk menyalin.';
                }
            }, 'image/png');
        });
    });
    </script>
</body>
</html>
