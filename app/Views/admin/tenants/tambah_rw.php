<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-secondary">
                <?php echo form_open('admin/tenants/store-rw') ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama RW</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: RW 06" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_aktif" class="form-control">
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
