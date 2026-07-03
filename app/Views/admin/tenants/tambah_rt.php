<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open('admin/tenants/store-rt') ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama RT</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: RT 29" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih RW</label>
                            <select name="id_rw" class="form-control" required>
                                <option value="">-- Pilih RW --</option>
                                <?php foreach ($rws as $rw): ?>
                                    <option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
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
