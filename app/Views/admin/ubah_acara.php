<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/presensi/acara/' . $acara->id_acara . '/update') ?>
					<div class="card-body">
						<div class="form-group">
							<label>Nama Acara</label>
							<input type="text" name="nama_acara" class="form-control" value="<?= esc($acara->nama_acara) ?>" required>
						</div>

						<div class="form-group">
							<label>Tanggal Acara</label>
							<input type="date" name="tanggal_acara" class="form-control" value="<?= esc($acara->tanggal_acara) ?>" required>
						</div>

						<div class="form-group">
							<label>Tempat (opsional)</label>
							<input type="text" name="tempat" class="form-control" value="<?= esc($acara->tempat ?? '') ?>">
						</div>

						<div class="form-group">
							<label>Catatan (opsional)</label>
							<textarea name="catatan" class="form-control" rows="3"><?= esc($acara->catatan) ?></textarea>
						</div>
					</div>
					<div class="card-footer">
						<a href="<?= base_url('admin/presensi/acara/' . $acara->id_acara) ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				<?php echo form_close() ?>
			</div>
		</div>
	</div>
</div>
