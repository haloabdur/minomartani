<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/kesehatan/store') ?>
					<div class="card-body">
						<div class="form-group">
							<label>Nama Kegiatan</label>
							<input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Posyandu Lansia Juli 2026" required>
						</div>

						<div class="form-group">
							<label>Tanggal Kegiatan</label>
							<input type="date" name="tanggal_kegiatan" class="form-control" value="<?= date('Y-m-d') ?>" required>
						</div>

						<div class="form-group">
							<label>Catatan (opsional)</label>
							<textarea name="catatan" class="form-control" rows="3"></textarea>
						</div>
					</div>
					<div class="card-footer">
						<a href="<?= base_url('admin/kesehatan') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan &amp; Catat Peserta</button>
					</div>
				<?php echo form_close() ?>
			</div>
		</div>
	</div>
</div>
