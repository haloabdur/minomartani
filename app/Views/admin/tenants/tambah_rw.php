<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-secondary">
                <?php echo form_open('admin/tenants/store-rw') ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama">Nama RW <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: RW 06" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="subdomain">Subdomain <span class="text-danger">*</span></label>
                            <input type="text" id="subdomain" name="subdomain" class="form-control" placeholder="Contoh: rw06" required pattern="[a-z0-9-]+">
                            <small class="form-text text-muted">Contoh: rw06 &rarr; rw06.minomartani.com</small>
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
