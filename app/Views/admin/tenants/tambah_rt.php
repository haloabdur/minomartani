<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open('admin/tenants/store-rt') ?>
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
