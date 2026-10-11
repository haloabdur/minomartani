<?php
/**
 * Shared form body for tambah/ubah kucing.
 * Expects: $pemilik (list), optional $item (existing kucing row).
 */
$item   = $item ?? null;
$val    = static fn (string $field, $default = '') => old($field, $item->$field ?? $default);
$isEdit = !empty($item && !empty($item->id_kucing));
$jkVal  = $val('jenis_kelamin', 'jantan');
if (empty($jkVal)) {
    $jkVal = 'jantan';
}
?>

<div class="row">
    <!-- Kolom Kiri: Informasi Utama & Status -->
    <div class="col-lg-7 col-md-12">
        <div class="form-group">
            <label for="id_warga" class="font-weight-bold">
                <i class="fas fa-user text-primary mr-1"></i> Pemilik Kucing <span class="text-danger">*</span>
            </label>
            <select class="form-control select2 w-100" name="id_warga" id="id_warga" required data-placeholder="-- Cari dan pilih nama warga pemilik --">
                <option value="">-- Cari dan pilih nama warga pemilik --</option>
                <?php foreach ($pemilik as $w): ?>
                    <option value="<?= $w->id_warga ?>" <?= (string) $val('id_warga') === (string) $w->id_warga ? 'selected' : '' ?>>
                        <?= esc($w->nama_warga) ?><?= !empty($w->alamat) ? ' - ' . esc($w->alamat) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Ketik nama atau alamat warga untuk mencari secara cepat.</small>
        </div>

        <div class="form-group">
            <label for="nama" class="font-weight-bold">
                <i class="fas fa-tag text-primary mr-1"></i> Nama Kucing <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control form-control-lg" name="nama" id="nama" value="<?= esc($val('nama')) ?>" maxlength="100" placeholder="Contoh: Muezza, Oyen, Milo" required>
        </div>

        <div class="form-group">
            <label class="d-block font-weight-bold">
                <i class="fas fa-venus-mars text-primary mr-1"></i> Jenis Kelamin <span class="text-danger">*</span>
            </label>
            <div class="d-flex align-items-center mt-2 p-2 bg-light rounded border">
                <div class="custom-control custom-radio custom-control-inline mr-4">
                    <input type="radio" id="jk_jantan" name="jenis_kelamin" class="custom-control-input" value="jantan" <?= ($jkVal === 'jantan') ? 'checked' : '' ?> required>
                    <label class="custom-control-label font-weight-normal text-primary" for="jk_jantan" style="cursor: pointer;">
                        <i class="fas fa-mars mr-1"></i> <strong>Jantan</strong>
                    </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="jk_betina" name="jenis_kelamin" class="custom-control-input" value="betina" <?= ($jkVal === 'betina') ? 'checked' : '' ?> required>
                    <label class="custom-control-label font-weight-normal text-danger" for="jk_betina" style="cursor: pointer;">
                        <i class="fas fa-venus mr-1"></i> <strong>Betina</strong>
                    </label>
                </div>
            </div>
            <small class="form-text text-muted">Pilih jenis kelamin kucing (default: Jantan).</small>
        </div>

        <div class="form-group">
            <label for="warna_ciri" class="font-weight-bold">
                <i class="fas fa-palette text-primary mr-1"></i> Warna / Ciri-ciri Fisik <small class="text-muted font-weight-normal">(Opsional)</small>
            </label>
            <input type="text" class="form-control" name="warna_ciri" id="warna_ciri" value="<?= esc($val('warna_ciri')) ?>" maxlength="255" placeholder="Contoh: Oren belang putih, ekor pendek, kalung merah lonceng">
            <small class="form-text text-muted">Ciri khusus yang memudahkan warga mengenali kucing terdaftar.</small>
        </div>

        <div class="card bg-light border p-3 mt-3 mb-3">
            <label class="font-weight-bold mb-2">
                <i class="fas fa-notes-medical text-info mr-1"></i> Status Kesehatan &amp; Medis
            </label>
            <div class="row">
                <div class="col-sm-6">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="steril" id="steril" value="1" <?= (int) $val('steril', 0) === 1 ? 'checked' : '' ?>>
                        <label class="custom-control-label font-weight-normal" for="steril" style="cursor: pointer;">
                            <strong class="text-dark">Sudah Steril</strong>
                            <span class="d-block text-muted small">Telah melalui sterilisasi / kebiri</span>
                        </label>
                    </div>
                </div>
                <div class="col-sm-6 mt-2 mt-sm-0">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="vaksin" id="vaksin" value="1" <?= (int) $val('vaksin', 0) === 1 ? 'checked' : '' ?>>
                        <label class="custom-control-label font-weight-normal" for="vaksin" style="cursor: pointer;">
                            <strong class="text-dark">Sudah Vaksin</strong>
                            <span class="d-block text-muted small">Telah menerima vaksin rabies/tahunan</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Foto Kucing & Preview -->
    <div class="col-lg-5 col-md-12">
        <div class="card shadow-none border bg-light h-100">
            <div class="card-body">
                <label class="font-weight-bold mb-2 d-block">
                    <i class="fas fa-camera text-primary mr-1"></i> Foto Kucing <?= $isEdit && !empty($item->foto) ? '<small class="text-muted font-weight-normal">(Opsional ganti)</small>' : '<span class="text-danger">*</span>' ?>
                </label>
                <p class="text-muted small mb-3">
                    Unggah foto kucing yang jelas. Foto akan otomatis dikompres ke format WebP (maks. 500KB) agar hemat ukuran penyimpanan dan memuat cepat.
                </p>

                <?php if ($isEdit && !empty($item->foto)): ?>
                    <div class="mb-3 text-center p-2 bg-white rounded border" id="current-image-box">
                        <span class="badge badge-secondary mb-2">Foto Saat Ini</span>
                        <div>
                            <img src="<?= foto_url($item->foto, 'kucing') ?>" alt="<?= esc($item->nama) ?>" class="img-thumbnail shadow-sm" style="max-height: 180px; object-fit: cover;">
                        </div>
                        <small class="text-muted d-block mt-1">Biarkan input di bawah kosong jika tidak ingin mengubah foto saat ini.</small>
                    </div>
                <?php endif; ?>

                <div class="custom-file mb-2">
                    <input type="file" class="custom-file-input" name="foto" id="foto" accept="image/*" onchange="previewImage(this)" <?= ($isEdit && !empty($item->foto)) ? '' : 'required' ?>>
                    <label class="custom-file-label text-truncate" for="foto" id="foto-label">Pilih file foto kucing...</label>
                </div>
                <small class="text-muted d-block mb-3">
                    <i class="fas fa-info-circle mr-1"></i> Format: JPG, PNG, atau WebP (Maksimal 5 MB).
                </small>

                <!-- Preview Box Container -->
                <div id="preview-container" class="mt-2 text-center p-2 bg-white rounded border" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="badge badge-primary"><i class="fas fa-eye mr-1"></i> Preview Foto Baru</span>
                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="clearPreview()" title="Hapus pilihan">
                            <i class="fas fa-times mr-1"></i> Batal
                        </button>
                    </div>
                    <div style="max-height: 220px; overflow: hidden;" class="rounded">
                        <img id="preview" src="" alt="Preview Foto" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
                    </div>
                    <div id="file-info" class="mt-2 text-muted small font-italic"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        var previewContainer = document.getElementById('preview-container');
        var preview = document.getElementById('preview');
        var fileInfo = document.getElementById('file-info');
        var label = document.getElementById('foto-label');

        if (input.files && input.files[0]) {
            var file = input.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
            };

            reader.readAsDataURL(file);

            var size = (file.size / 1024).toFixed(1);
            var sizeStr = size > 1024 ? (size / 1024).toFixed(2) + ' MB' : size + ' KB';
            fileInfo.innerHTML = '<i class="fas fa-file-image mr-1"></i> ' + file.name + ' (' + sizeStr + ')';
            if (label) {
                label.textContent = file.name;
            }
        } else {
            clearPreview();
        }
    }

    function clearPreview() {
        var input = document.getElementById('foto');
        var previewContainer = document.getElementById('preview-container');
        var preview = document.getElementById('preview');
        var fileInfo = document.getElementById('file-info');
        var label = document.getElementById('foto-label');

        if (input) {
            input.value = '';
        }
        if (preview) {
            preview.src = '';
        }
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
        if (fileInfo) {
            fileInfo.innerHTML = '';
        }
        if (label) {
            label.textContent = 'Pilih file foto kucing...';
        }
    }

    $(document).ready(function() {
        if ($.fn.select2) {
            $('#id_warga').select2({
                theme: 'bootstrap4',
                placeholder: '-- Cari dan pilih nama warga pemilik --',
                allowClear: true,
                width: '100%'
            });
        }
    });
</script>
