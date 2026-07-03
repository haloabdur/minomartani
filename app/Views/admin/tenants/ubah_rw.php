<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-secondary">
                <?php echo form_open('admin/tenants/update-rw/' . $rw->id_rw) ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nama RW</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: RW 06" value="<?= esc($rw->nama) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_aktif" class="form-control">
                                <option value="1" <?= (int)$rw->is_aktif === 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= (int)$rw->is_aktif === 0 ? 'selected' : '' ?>>Non-aktif</option>
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
