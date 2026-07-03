<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="card card-secondary">
                <?php echo form_open('admin/tenants/update-rw/' . $rw->id_rw) ?>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="nama">Nama RW <span class="text-danger">*</span></label>
                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: RW 06" value="<?= esc($rw->nama) ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="is_aktif">Status</label>
                            <select id="is_aktif" name="is_aktif" class="form-control">
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
