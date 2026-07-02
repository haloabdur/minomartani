<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open_multipart('admin/berita/store') ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Judul</label>
									<input type="text" name="judul" class="form-control" placeholder="Judul" required>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Kategori <span class="text-muted">(Pisahkan dengan Koma)</span></label>
									<input type="text" name="kategori" class="form-control" placeholder="ex: Covid, Berita, Bupati" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Deskripsi</label>
									<textarea class="form-control summernote" name="deskripsi" required=""></textarea>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col">
								<div class="form-group">
									<label>Sumber <span class="text-muted">Jika Ada</span></label>
									<input type="text" name="lampiran" class="form-control">
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Foto <span class="text-muted">Ukuran 1200 x 730</span></label>
									<input type="file" name="foto" class="form-control" required>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Lampiran <span class="text-muted">Link Google Drive</span></label>
									<input type="text" name="lampiran" class="form-control">
								</div>
							</div>
						</div>

					</div>
					<!-- /.card-body -->

					<div class="card-footer">
						<a href="<?php echo base_url('admin/berita') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>