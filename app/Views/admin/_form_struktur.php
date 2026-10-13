<?php
/**
 * Shared form body for tambah/ubah struktur.
 * Expects: $level ('rt'|'rw'), $warga (list), $atasan (list), optional $item.
 */
$item = $item ?? null;
$val  = static fn (string $field, $default = '') => old($field, $item->$field ?? $default);
?>

<input type="hidden" name="level" value="<?= esc($level) ?>">

<div class="row">
    <div class="col-lg-8 col-md-12">
        <div class="form-group">
            <label for="id_warga" class="font-weight-bold">
                <i class="fas fa-user text-primary mr-1"></i> Warga <span class="text-danger">*</span>
            </label>
            <select class="form-control select2 w-100" name="id_warga" id="id_warga" required data-placeholder="-- Cari dan pilih warga --">
                <option value="">-- Cari dan pilih warga --</option>
                <?php foreach ($warga as $w): ?>
                    <option value="<?= $w->id_warga ?>" <?= (string) $val('id_warga') === (string) $w->id_warga ? 'selected' : '' ?>>
                        <?= esc($w->nama_warga) ?><?= $level === 'rw' && !empty($w->nama_rt) ? ' (' . esc($w->nama_rt) . ')' : '' ?><?= !empty($w->alamat) ? ' - ' . esc($w->alamat) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">
                <?= $level === 'rw'
                    ? 'Pengurus RW dapat dipilih dari warga semua RT dalam RW ini.'
                    : 'Pengurus RT hanya dapat dipilih dari warga RT ini.' ?>
                Satu warga boleh memegang lebih dari satu jabatan.
            </small>
        </div>

        <div class="form-group">
            <label for="jabatan" class="font-weight-bold">
                <i class="fas fa-id-badge text-primary mr-1"></i> Jabatan <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control form-control-lg" name="jabatan" id="jabatan" value="<?= esc($val('jabatan')) ?>" maxlength="100" placeholder="Contoh: Ketua <?= strtoupper($level) ?>, Sekretaris, Bendahara, Seksi Keamanan" required>
        </div>

        <div class="form-group">
            <label for="id_atasan" class="font-weight-bold">
                <i class="fas fa-level-up-alt text-primary mr-1"></i> Atasan <small class="text-muted font-weight-normal">(Opsional)</small>
            </label>
            <select class="form-control select2 w-100" name="id_atasan" id="id_atasan" data-placeholder="-- Tanpa atasan (posisi teratas) --">
                <option value="">-- Tanpa atasan (posisi teratas) --</option>
                <?php foreach ($atasan as $a): ?>
                    <option value="<?= $a->id_struktur ?>" <?= (string) $val('id_atasan') === (string) $a->id_struktur ? 'selected' : '' ?>>
                        <?= esc($a->jabatan) ?> - <?= esc($a->nama_warga ?? '-') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($level === 'rt'): ?>
                <small class="form-text text-muted">Pengurus RT teratas otomatis tergantung di bawah posisi teratas RW pada bagan.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="urutan" class="font-weight-bold">
                <i class="fas fa-sort-numeric-down text-primary mr-1"></i> Urutan <small class="text-muted font-weight-normal">(Opsional)</small>
            </label>
            <input type="number" class="form-control" name="urutan" id="urutan" value="<?= esc($val('urutan', 0)) ?>" min="0" max="9999" style="max-width: 160px;">
            <small class="form-text text-muted">Angka kecil tampil lebih kiri di bagan, di antara jabatan yang sama atasannya.</small>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('#id_warga, #id_atasan').select2({
                theme: 'bootstrap4',
                allowClear: true,
                width: '100%'
            });
        }
    });
</script>
