<?php
/**
 * Shared form body for tambah/ubah kucing.
 * Expects: $pemilik (list), optional $item (existing kucing row).
 */
$item = $item ?? null;
$val  = static fn (string $field, $default = '') => old($field, $item->$field ?? $default);
?>
<div class="form-group">
    <label for="id_warga">Pemilik</label>
    <select class="form-control" name="id_warga" id="id_warga" required>
        <option value="">-- Pilih pemilik --</option>
        <?php foreach ($pemilik as $w): ?>
            <option value="<?= $w->id_warga ?>" <?= (string) $val('id_warga') === (string) $w->id_warga ? 'selected' : '' ?>>
                <?= esc($w->nama_warga) ?><?= !empty($w->alamat) ? ' - ' . esc($w->alamat) : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label for="nama">Nama Kucing</label>
    <input type="text" class="form-control" name="nama" id="nama" value="<?= esc($val('nama')) ?>" maxlength="100" placeholder="Nama Kucing" required>
</div>
<div class="form-group">
    <label for="warna_ciri">Warna / Ciri-ciri</label>
    <input type="text" class="form-control" name="warna_ciri" id="warna_ciri" value="<?= esc($val('warna_ciri')) ?>" maxlength="255" placeholder="Contoh: oren belang putih, ekor pendek">
</div>
<div class="form-group">
    <label for="jenis_kelamin">Jenis Kelamin</label>
    <select class="form-control" name="jenis_kelamin" id="jenis_kelamin">
        <option value="">-- Tidak diketahui --</option>
        <option value="jantan" <?= $val('jenis_kelamin') === 'jantan' ? 'selected' : '' ?>>Jantan</option>
        <option value="betina" <?= $val('jenis_kelamin') === 'betina' ? 'selected' : '' ?>>Betina</option>
    </select>
</div>
<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" name="steril" id="steril" value="1" <?= (int) $val('steril', 0) === 1 ? 'checked' : '' ?>>
        <label class="custom-control-label" for="steril">Sudah steril</label>
    </div>
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" name="vaksin" id="vaksin" value="1" <?= (int) $val('vaksin', 0) === 1 ? 'checked' : '' ?>>
        <label class="custom-control-label" for="vaksin">Sudah vaksin</label>
    </div>
</div>
<div class="form-group">
    <label for="foto">Foto</label>
    <br>
    <?php if (!empty($item->foto)): ?>
        <div id="current-image">
            <img src="<?= foto_url($item->foto, 'kucing') ?>" alt="<?= esc($item->nama) ?>" style="width: 100px; height: 100px; object-fit: cover;" class="mb-2">
            <p class="small text-muted">Foto saat ini</p>
        </div>
    <?php endif; ?>

    <input type="file" class="form-control" name="foto" id="foto" accept="image/*" onchange="previewImage(this)">
    <?php if (!empty($item->foto)): ?>
        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
    <?php endif; ?>

    <div class="mt-2" style="display: none;" id="preview-container">
        <img id="preview" src="" alt="Preview Foto" class="img-thumbnail" style="max-height: 200px;">
        <div id="file-info" class="mt-1 text-muted small"></div>
    </div>
</div>

<script>
    function previewImage(input) {
        var previewContainer = document.getElementById('preview-container');
        var preview = document.getElementById('preview');
        var fileInfo = document.getElementById('file-info');

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            var file = input.files[0];

            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
            }

            reader.readAsDataURL(file);

            var size = (file.size / 1024).toFixed(2);
            fileInfo.textContent = 'File: ' + file.name + ' (' + size + ' KB) - ' + file.type;
        } else {
            preview.src = "";
            previewContainer.style.display = 'none';
            fileInfo.textContent = "";
        }
    }
</script>
