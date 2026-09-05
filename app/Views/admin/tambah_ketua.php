<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open_multipart('admin/ketua/store') ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Nama Ketua</label>
									<input type="text" name="nama_ketua" class="form-control" placeholder="Contoh: Budi Santoso" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Mulai Menjabat</label>
									<input type="text" name="mulai" class="form-control" placeholder="Contoh: 2020" required>
								</div>
							</div>
							<div class="col">
								<div class="form-group">
									<label>Selesai Menjabat <span class="text-muted">Isi "Sekarang" jika masih menjabat</span></label>
									<input type="text" name="selesai" class="form-control" placeholder="Contoh: Sekarang" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Foto</label>
									<input type="file" name="foto_ketua" class="form-control-file">
								</div>
							</div>
						</div>

					</div>
					<!-- /.card-body -->

					<div class="card-footer">
						<a href="<?php echo base_url('admin/ketua') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>
