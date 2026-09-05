<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/papan-informasi/store') ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Judul</label>
									<input type="text" name="judul" class="form-control" placeholder="Contoh: Tata Tertib Keamanan Lingkungan" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Isi</label>
									<textarea class="form-control summernote" name="isi" required=""></textarea>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Lampiran <span class="text-muted">Link Google Drive, jika ada</span></label>
									<input type="text" name="lampiran" class="form-control">
								</div>
							</div>
						</div>

					</div>
					<!-- /.card-body -->

					<div class="card-footer">
						<a href="<?php echo base_url('admin/papan-informasi') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>
