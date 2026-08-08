<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/presensi/store') ?>
					<div class="card-body">
						<div class="form-group">
							<label>Nama Acara</label>
							<input type="text" name="nama_acara" class="form-control" placeholder="Contoh: Rapat Rutin RT Agustus 2026" required>
						</div>

						<div class="form-group">
							<label>Tanggal Acara</label>
							<input type="date" name="tanggal_acara" class="form-control" value="<?= date('Y-m-d') ?>" required>
						</div>

						<div class="form-group">
							<label>Tempat (opsional)</label>
							<input type="text" name="tempat" class="form-control" placeholder="Contoh: Balai RT">
						</div>

						<div class="form-group">
							<label>Catatan (opsional)</label>
							<textarea name="catatan" class="form-control" rows="3"></textarea>
						</div>
					</div>
					<div class="card-footer">
						<a href="<?= base_url('admin/presensi') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan &amp; Mulai Presensi</button>
					</div>
				<?php echo form_close() ?>
			</div>
		</div>
	</div>
</div>
