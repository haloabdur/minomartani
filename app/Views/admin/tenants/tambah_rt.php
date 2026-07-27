<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open_multipart('admin/tenants/store-rt') ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama">Nama RT <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: RT 29" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="subdomain">Subdomain <span class="text-danger">*</span></label>
                            <input type="text" id="subdomain" name="subdomain" class="form-control" placeholder="Contoh: rt29" required pattern="[a-z0-9-]+">
                            <small class="form-text text-muted">Contoh: rt29 &rarr; rt29.minomartani.com</small>
                        </div>
                        <div class="form-group">
                            <label for="id_rw">Pilih RW <span class="text-danger">*</span></label>
                            <select id="id_rw" name="id_rw" class="form-control" required>
                                <option value="">-- Pilih RW --</option>
                                <?php foreach ($rws as $rw): ?>
                                    <option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">RT baru akan tergabung dalam RW yang dipilih.</small>
                        </div>
                        <div class="form-group">
                            <label for="is_aktif">Status</label>
                            <select id="is_aktif" name="is_aktif" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Non-aktif</option>
                            </select>
                        </div>
                        <hr>
                        <p class="text-muted mb-2">Profil untuk landing page publik (opsional):</p>
                        <div class="form-group">
                            <label for="alamat">Alamat / Lokasi Singkat</label>
                            <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Contoh: Ngaglik, Sleman, Daerah Istimewa Yogyakarta">
                        </div>
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi / Profil RT</label>
                            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" placeholder="Cerita singkat tentang wilayah RT ini"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="no_wa">Nomor WhatsApp Kontak</label>
                            <input type="text" id="no_wa" name="no_wa" class="form-control" placeholder="Contoh: 6281234567890">
                        </div>
                        <div class="form-group">
                            <label for="foto_hero">Foto Hero (landing page)</label>
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
