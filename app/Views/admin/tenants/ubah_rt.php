<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open('admin/tenants/update-rt/' . $rt->id_rt) ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama RT</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: RT 29" value="<?= esc($rt->nama) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Pilih RW</label>
                            <select name="id_rw" class="form-control" required>
                                <option value="">-- Pilih RW --</option>
                                <?php foreach ($rws as $rw): ?>
                                    <option value="<?= $rw->id_rw ?>" <?= (int)$rt->id_rw === (int)$rw->id_rw ? 'selected' : '' ?>><?= esc($rw->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_aktif" class="form-control">
                                <option value="1" <?= (int)$rt->is_aktif === 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= (int)$rt->is_aktif === 0 ? 'selected' : '' ?>>Non-aktif</option>
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
