<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open_multipart('admin/tenants/update-rt/' . $rt->id_rt) ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama">Nama RT <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: RT 29" value="<?= esc($rt->nama) ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="subdomain">Subdomain <span class="text-danger">*</span></label>
                            <input type="text" id="subdomain" name="subdomain" class="form-control" placeholder="Contoh: rt29" value="<?= esc($rt->subdomain ?? '') ?>" required pattern="[a-z0-9-]+">
                            <small class="form-text text-muted">Contoh: rt29 &rarr; rt29.minomartani.com</small>
                        </div>
                        <div class="form-group">
                            <label for="id_rw">Pilih RW <span class="text-danger">*</span></label>
                            <select id="id_rw" name="id_rw" class="form-control" required>
                                <option value="">-- Pilih RW --</option>
                                <?php foreach ($rws as $rw): ?>
                                    <option value="<?= $rw->id_rw ?>" <?= (int)$rt->id_rw === (int)$rw->id_rw ? 'selected' : '' ?>><?= esc($rw->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="is_aktif">Status</label>
                            <select id="is_aktif" name="is_aktif" class="form-control">
                                <option value="1" <?= (int)$rt->is_aktif === 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= (int)$rt->is_aktif === 0 ? 'selected' : '' ?>>Non-aktif</option>
                            </select>
                        </div>
                        <hr>
                        <p class="text-muted mb-2">Profil untuk landing page publik (opsional):</p>
                        <div class="form-group">
                            <label for="alamat">Alamat / Lokasi Singkat</label>
                            <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Contoh: Ngaglik, Sleman, Daerah Istimewa Yogyakarta" value="<?= esc($rt->alamat ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi / Profil RT</label>
                            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" placeholder="Cerita singkat tentang wilayah RT ini"><?= esc($rt->deskripsi ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="no_wa">Nomor WhatsApp Kontak</label>
                            <input type="text" id="no_wa" name="no_wa" class="form-control" placeholder="Contoh: 6281234567890" value="<?= esc($rt->no_wa ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="foto_hero">Foto Hero (landing page)</label>
                            <?php if (! empty($rt->foto_hero)): ?>
                                <div class="mb-2">
                                    <img src="<?= base_url('public/rt/' . $rt->foto_hero) ?>" alt="Foto hero" style="max-width: 200px" class="img-thumbnail d-block">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="foto_hero" name="foto_hero" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="<?= base_url('admin/tenants') ?>" class="btn btn-light">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
