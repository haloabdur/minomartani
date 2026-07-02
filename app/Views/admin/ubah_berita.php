<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open_multipart('admin/berita/update/'.$berita->id_berita) ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Judul</label>
									<input type="text" name="judul" class="form-control" placeholder="Judul" value="<?php echo $berita->judul ?>" required>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Kategori <span class="text-muted">(Pisahkan dengan Koma)</span></label>
									<input type="text" name="kategori" class="form-control" value="<?php echo $berita->kategori ?>" placeholder="ex: Covid, Berita, Bupati" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Deskripsi</label>
									<textarea class="form-control summernote" name="deskripsi" required=""><?php echo $berita->deskripsi ?></textarea>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col">
								<div class="form-group">
									<label>Sumber <span class="text-muted">Jika Ada</span></label>
									<input type="text" name="lampiran" class="form-control" value="<?php echo $berita->sumber ?>" >
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="row">
									<div class="col">
										<?php if (!empty($berita->foto)): ?>
											<div class="form-group">
												<label>Foto saat ini</label> <br>
												<img class="pr-2 rounded" src="<?php echo base_url('public/berita/'.$berita->foto) ?>" width="70" >
												<small class="text-muted"><?php echo $berita->foto ?></small>
												<input type="hidden" name="foto_old" value="<?php echo $berita->foto ?>">
											</div>
										<?php endif ?>
									</div>

									<div class="col">
										<div class="form-group">
											<label>Ganti Foto? <span class="text-muted">Ukuran 1200 x 730</span></label>
											<input type="file" name="foto" class="form-control">
										</div>
									</div>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Lampiran <span class="text-muted">Link Google Drive</span></label>
									<input type="text" name="lampiran" class="form-control" value="<?php echo $berita->lampiran ?>" >
								</div>
							</div>
						</div>
                      
                      <div class="row">
                      	<div class="col">
                        	<div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="is_status">
                                  <option <?php echo $berita->is_status ? "selected" : "" ?> value="1">Publish</option>
                                  <option <?php echo !$berita->is_status ? "selected" : "" ?> value="0">Draft</option>
                              	</select>
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