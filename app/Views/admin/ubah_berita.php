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

							<div class="col">
								<div class="form-group">
									<label>Tanggal Dibuat</label>
									<input type="datetime-local" name="created_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($berita->created_time)) ?>">
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
							<div class="col-12">
								<label>Gambar <span class="text-muted">min 1, maks <?= $maxFoto ?>. Pilih cover, atau centang hapus (sisa gambar tidak boleh kurang dari 1).</span></label>
								<div class="row">
									<?php foreach ($fotos as $f): ?>
										<div class="col-auto text-center mb-3">
											<img class="rounded d-block mb-1" src="<?= foto_url($f->foto) ?>" width="90">
											<div class="form-check form-check-inline">
												<input class="form-check-input" type="radio" name="cover" id="cover-<?= $f->id_berita_foto ?>" value="existing:<?= $f->id_berita_foto ?>" <?= $f->is_cover ? 'checked' : '' ?>>
												<label class="form-check-label small" for="cover-<?= $f->id_berita_foto ?>">Cover</label>
											</div>
											<div class="form-check form-check-inline">
												<input class="form-check-input" type="checkbox" name="delete_foto[]" id="del-<?= $f->id_berita_foto ?>" value="<?= $f->id_berita_foto ?>">
												<label class="form-check-label small text-danger" for="del-<?= $f->id_berita_foto ?>">Hapus</label>
											</div>
										</div>
									<?php endforeach ?>
								</div>
								<div class="form-group mt-2">
									<label>Tambah gambar baru <span class="text-muted">Total maks <?= $maxFoto ?> gambar</span></label>
									<input type="file" name="foto[]" class="form-control" accept="image/*" multiple>
								</div>
							</div>
						</div>

						<div class="row mt-3">
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
