<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/update') ?>
					<div class="card-body">
						<div class="form-group">
							<label>Nama Kegiatan</label>
							<input type="text" name="nama_kegiatan" class="form-control" value="<?= esc($kegiatan->nama_kegiatan) ?>" required>
						</div>

						<div class="form-group">
							<label>Tanggal Kegiatan</label>
							<input type="date" name="tanggal_kegiatan" class="form-control" value="<?= esc($kegiatan->tanggal_kegiatan) ?>" required>
						</div>

						<div class="form-group">
							<label>Catatan (opsional)</label>
							<textarea name="catatan" class="form-control" rows="3"><?= esc($kegiatan->catatan) ?></textarea>
						</div>
					</div>
					<div class="card-footer">
						<a href="<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan) ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				<?php echo form_close() ?>
			</div>
		</div>
	</div>
</div>
