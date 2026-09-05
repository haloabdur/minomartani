<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/papan-informasi/update/'.$papan->id_papan) ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Judul</label>
									<input type="text" name="judul" class="form-control" placeholder="Judul" value="<?php echo $papan->judul ?>" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Isi</label>
									<textarea class="form-control summernote" name="isi" required=""><?php echo $papan->isi ?></textarea>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Lampiran <span class="text-muted">Link Google Drive, jika ada</span></label>
									<input type="text" name="lampiran" class="form-control" value="<?php echo $papan->lampiran ?>" >
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col">
								<div class="form-group">
									<label>Status</label>
									<select class="form-control" name="is_status">
										<option <?php echo $papan->is_status ? "selected" : "" ?> value="1">Publish</option>
										<option <?php echo !$papan->is_status ? "selected" : "" ?> value="0">Draft</option>
									</select>
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
