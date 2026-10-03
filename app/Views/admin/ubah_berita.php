<div class="container-fluid">
	<div class="row justify-content-center">
		<div class="col-lg-10 col-12">
			<div class="card card-outline card-primary shadow-sm">
				<div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
					<h3 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-edit text-primary mr-2"></i> Edit Berita: <span class="text-muted"><?= esc($berita->judul) ?></span>
					</h3>
					<div class="card-tools">
						<a href="<?= base_url('berita/' . $berita->slug) ?>" target="_blank" class="btn btn-outline-info btn-sm mr-1 shadow-sm">
							<i class="fas fa-external-link-alt mr-1"></i> Lihat di Web
						</a>
						<a href="<?= base_url('admin/berita') ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
							<i class="fas fa-arrow-left mr-1"></i> Kembali
						</a>
					</div>
				</div>

				<!-- form start -->
				<?= form_open_multipart('admin/berita/update/' . $berita->id_berita) ?>
				<div class="card-body">

					<!-- Seksi 1: Informasi Utama -->
					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label class="font-weight-bold">Judul Berita <span class="text-danger">*</span></label>
								<input type="text" name="judul" class="form-control form-control-lg" placeholder="Judul Berita" value="<?= esc($berita->judul) ?>" required>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
								<input type="text" name="kategori" class="form-control form-control-lg" value="<?= esc($berita->kategori) ?>" placeholder="ex: Covid, Berita, Bupati" required>
								<small class="form-text text-muted">Pisahkan dengan koma jika lebih dari satu.</small>
							</div>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">Tanggal &amp; Waktu Terbit</label>
								<input type="datetime-local" name="created_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($berita->created_time)) ?>">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">Status Publikasi</label>
								<select class="form-control" name="is_status">
									<option value="1" <?= $berita->is_status ? 'selected' : '' ?>>Publikasikan (Publish)</option>
									<option value="0" <?= !$berita->is_status ? 'selected' : '' ?>>Draf (Draft)</option>
								</select>
								<small class="form-text text-muted">Pilih apakah berita dapat dilihat langsung oleh publik.</small>
							</div>
						</div>
					</div>

					<!-- Seksi 2: Isi Konten -->
					<div class="form-group mt-3">
						<label class="font-weight-bold">Isi Konten Berita <span class="text-danger">*</span></label>
						<textarea class="form-control summernote" name="deskripsi" rows="10" required><?= $berita->deskripsi ?></textarea>
					</div>

					<!-- Seksi 3: Foto & Galeri Bento -->
					<div class="card bg-light border mt-4">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
								<label class="font-weight-bold mb-0">
									<i class="fas fa-images text-primary mr-1"></i> Galeri Foto Bento Saat Ini
								</label>
								<small class="text-muted">Min 1, maks <?= $maxFoto ?> foto. Pilih salah satu radio sebagai <strong>Cover Utama</strong>.</small>
							</div>

							<!-- Existing Photos Grid -->
							<div class="row mt-3">
								<?php foreach ($fotos as $f): ?>
									<div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
										<div class="card h-100 shadow-sm border <?= $f->is_cover ? 'border-primary' : '' ?>" style="border-radius: 8px; overflow: hidden;">
											<div style="height: 110px; overflow: hidden; position: relative; background: #e9ecef;">
												<img src="<?= foto_url($f->foto) ?>" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;" onclick="showFotoPreview(this.src)" title="Klik untuk perbesar">
												<?php if ($f->is_cover): ?>
													<span class="badge badge-primary position-absolute" style="top: 4px; left: 4px;">
														<i class="fas fa-star mr-1"></i>Cover
													</span>
												<?php endif; ?>
											</div>
											<div class="card-footer p-2 bg-white text-center">
												<div class="custom-control custom-radio custom-control-inline mb-1">
													<input type="radio" id="cover-<?= $f->id_berita_foto ?>" name="cover" class="custom-control-input" value="existing:<?= $f->id_berita_foto ?>" <?= $f->is_cover ? 'checked' : '' ?>>
													<label class="custom-control-label small font-weight-bold" for="cover-<?= $f->id_berita_foto ?>">Cover</label>
												</div>
												<div class="custom-control custom-checkbox custom-control-inline">
													<input type="checkbox" id="del-<?= $f->id_berita_foto ?>" name="delete_foto[]" class="custom-control-input" value="<?= $f->id_berita_foto ?>">
													<label class="custom-control-label small text-danger" for="del-<?= $f->id_berita_foto ?>">Hapus</label>
												</div>
											</div>
										</div>
									</div>
								<?php endforeach ?>
							</div>

							<!-- Upload New Photos -->
							<div class="form-group mt-3 pt-3 border-top">
								<label class="font-weight-bold">
									<i class="fas fa-plus-circle text-success mr-1"></i> Tambah Foto Baru <small class="text-muted">(Total foto tidak boleh melebihi <?= $maxFoto ?>)</small>
								</label>
								<div class="custom-file">
									<input type="file" name="foto[]" id="foto-input-edit" class="custom-file-input" accept="image/*" multiple onchange="handleNewFileSelect(this)">
									<label class="custom-file-label" for="foto-input-edit">Pilih foto baru...</label>
								</div>
								<div id="preview-new-gallery" class="row mt-3" style="display: none;"></div>
							</div>
						</div>
					</div>

					<!-- Seksi 4: Tautan & Lampiran -->
					<div class="row mt-4">
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">
									<i class="fas fa-link text-muted mr-1"></i> Tautan Sumber Berita <small class="text-muted">(Opsional)</small>
								</label>
								<input type="url" name="sumber" class="form-control" placeholder="https://example.com/artikel-asli" value="<?= esc($berita->sumber ?? '') ?>">
								<small class="form-text text-muted">Link sumber asli berita jika mengutip dari media lain.</small>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">
									<i class="fas fa-paperclip text-muted mr-1"></i> Lampiran Berkas / Google Drive <small class="text-muted">(Opsional)</small>
								</label>
								<input type="text" name="lampiran" class="form-control" placeholder="https://drive.google.com/file/d/..." value="<?= esc($berita->lampiran ?? '') ?>">
								<small class="form-text text-muted">Link dokumen pendukung untuk warga.</small>
							</div>
						</div>
					</div>

				</div>
				<!-- /.card-body -->

				<div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
					<a href="<?= base_url('admin/berita') ?>" class="btn btn-default px-4">
						<i class="fas fa-times mr-1"></i> Batal
					</a>
					<button type="submit" class="btn btn-primary px-5 font-weight-bold shadow-sm">
						<i class="fas fa-save mr-1"></i> Simpan Perubahan
					</button>
				</div>
				<?= form_close() ?>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>

<!-- Modal Foto Preview -->
<div class="modal fade" id="modal-foto-preview" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content bg-transparent border-0">
			<button type="button" class="close text-white mb-2" data-dismiss="modal" aria-label="Close" style="text-shadow: none;">
				<span aria-hidden="true">&times;</span>
			</button>
			<img id="modal-foto-preview-img" src="" class="img-fluid rounded shadow-lg" alt="Preview">
		</div>
	</div>
</div>

<script>
function showFotoPreview(src) {
	var modalImg = document.getElementById('modal-foto-preview-img');
	modalImg.src = src;
	$('#modal-foto-preview').modal('show');
}

function handleNewFileSelect(input) {
	var files = input.files;
	var label = input.nextElementSibling;
	var container = document.getElementById('preview-new-gallery');

	if (files.length === 0) {
		label.innerText = 'Pilih foto baru...';
		container.style.display = 'none';
		container.innerHTML = '';
		return;
	}

	label.innerText = files.length + ' foto baru dipilih';
	container.innerHTML = '';
	container.style.display = 'flex';

	Array.from(files).forEach(function(file, idx) {
		var reader = new FileReader();
		reader.onload = function(e) {
			var col = document.createElement('div');
			col.className = 'col-6 col-sm-4 col-md-3 col-lg-2 mb-3 text-center';
			col.innerHTML = `
				<div class="card h-100 shadow-sm border border-success">
					<div style="height: 100px; overflow: hidden; position: relative;">
						<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
						<span class="badge badge-success position-absolute" style="top: 4px; left: 4px;">Baru</span>
					</div>
					<div class="card-footer p-1 bg-white small text-truncate" title="${file.name}">
						${file.name}
					</div>
				</div>
			`;
			container.appendChild(col);
		};
		reader.readAsDataURL(file);
	});
}
</script>
