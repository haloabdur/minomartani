<div class="container-fluid">
	<div class="row justify-content-center">
		<div class="col-lg-10 col-12">
			<div class="card card-outline card-primary shadow-sm">
				<div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
					<h3 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-newspaper text-primary mr-2"></i> Tambah Berita Baru
					</h3>
					<a href="<?= base_url('admin/berita') ?>" class="btn btn-outline-secondary btn-sm">
						<i class="fas fa-arrow-left mr-1"></i> Kembali ke Kelola Berita
					</a>
				</div>

				<!-- form start -->
				<?= form_open_multipart('admin/berita/store') ?>
				<div class="card-body">
					<!-- Seksi 1: Informasi Utama -->
					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label class="font-weight-bold">Judul Berita <span class="text-danger">*</span></label>
								<input type="text" name="judul" class="form-control form-control-lg" placeholder="Masukkan judul berita yang jelas dan menarik..." required autofocus>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
								<input type="text" name="kategori" id="input-kategori" class="form-control form-control-lg" placeholder="Contoh: Kegiatan, Pengumuman, Gotong Royong" required>
								<small class="form-text text-muted">Pisahkan dengan koma jika lebih dari satu kategori.</small>
							</div>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">Tanggal &amp; Waktu Terbit</label>
								<input type="datetime-local" name="created_time" class="form-control">
								<small class="form-text text-muted">Kosongkan jika ingin menggunakan waktu saat ini secara otomatis.</small>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">Status Publikasi</label>
								<select class="form-control" name="is_status">
									<option value="1" selected>Publikasikan Langsung (Publish)</option>
									<option value="0">Simpan sebagai Draf (Draft)</option>
								</select>
								<small class="form-text text-muted">Berita berstatus Draf tidak akan tampil di situs publik.</small>
							</div>
						</div>
					</div>

					<!-- Seksi 2: Konten Deskripsi -->
					<div class="form-group mt-3">
						<label class="font-weight-bold">Isi Konten Berita <span class="text-danger">*</span></label>
						<textarea class="form-control summernote" name="deskripsi" rows="10" required></textarea>
					</div>

					<!-- Seksi 3: Unggah Foto (Bento Gallery) -->
					<div class="card bg-light border mt-4">
						<div class="card-body">
							<label class="font-weight-bold mb-1">
								<i class="fas fa-images text-primary mr-1"></i> Foto / Galeri Bento <span class="text-danger">*</span>
							</label>
							<p class="text-muted small mb-3">
								Pilih <strong>1 hingga 5 foto</strong>. Foto pertama akan otomatis menjadi <strong>Cover Utama</strong> pada tampilan Bento di web.
							</p>

							<div class="custom-file mb-3">
								<input type="file" name="foto[]" id="foto-input" class="custom-file-input" accept="image/*" multiple required onchange="handleFileSelect(this)">
								<label class="custom-file-label" for="foto-input">Pilih 1 - 5 file gambar...</label>
							</div>

							<!-- Preview Container -->
							<div id="preview-gallery" class="row mt-3" style="display: none;"></div>
						</div>
					</div>

					<!-- Seksi 4: Sumber & Lampiran -->
					<div class="row mt-4">
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">
									<i class="fas fa-link text-muted mr-1"></i> Tautan Sumber Berita <small class="text-muted">(Opsional)</small>
								</label>
								<input type="url" name="sumber" class="form-control" placeholder="https://example.com/artikel-asli">
								<small class="form-text text-muted">Link sumber asli berita jika mengutip dari media luar.</small>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="font-weight-bold">
									<i class="fas fa-paperclip text-muted mr-1"></i> Lampiran Berkas / Google Drive <small class="text-muted">(Opsional)</small>
								</label>
								<input type="text" name="lampiran" class="form-control" placeholder="https://drive.google.com/file/d/...">
								<small class="form-text text-muted">Link unduh berkas atau dokumen pendukung untuk warga.</small>
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
						<i class="fas fa-save mr-1"></i> Simpan Berita
					</button>
				</div>
				<?= form_close() ?>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>

<script>
function handleFileSelect(input) {
	var files = input.files;
	var label = input.nextElementSibling;
	var container = document.getElementById('preview-gallery');

	if (files.length > 5) {
		alert('Maksimal hanya dapat memilih 5 gambar.');
		input.value = '';
		label.innerText = 'Pilih 1 - 5 file gambar...';
		container.style.display = 'none';
		container.innerHTML = '';
		return;
	}

	if (files.length === 0) {
		label.innerText = 'Pilih 1 - 5 file gambar...';
		container.style.display = 'none';
		container.innerHTML = '';
		return;
	}

	label.innerText = files.length + ' file dipilih';
	container.innerHTML = '';
	container.style.display = 'flex';

	Array.from(files).forEach(function(file, idx) {
		var reader = new FileReader();
		reader.onload = function(e) {
			var col = document.createElement('div');
			col.className = 'col-sm-6 col-md-4 col-lg-2 mb-3 text-center';
			col.innerHTML = `
				<div class="card h-100 shadow-sm border ${idx === 0 ? 'border-primary' : ''}">
					<div style="height: 110px; overflow: hidden; position: relative;">
						<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
						${idx === 0 ? '<span class="badge badge-primary position-absolute" style="top: 6px; left: 6px;"><i class="fas fa-star mr-1"></i>Cover</span>' : ''}
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