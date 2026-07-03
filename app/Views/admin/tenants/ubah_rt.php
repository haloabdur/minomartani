<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-primary">
                <?php echo form_open('admin/tenants/update-rt/' . $rt->id_rt) ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama">Nama RT <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: RT 29" value="<?= esc($rt->nama) ?>" required autofocus>
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
