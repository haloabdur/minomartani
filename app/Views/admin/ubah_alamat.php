<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/alamat/update/'.$alamat->id_alamat) ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col-md-9">
								<div class="form-group">
									<label>Jalan</label>
									<input type="text" name="alamat" class="form-control" placeholder="Alamat" value="<?php echo $alamat->alamat ?>" required>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label for="">QR Code</label>
									<br>
									<a href="<?php echo base_url('admin/alamat/generate_qrcode/'. $alamat->id_alamat) ?>" class="btn btn-success text-white">Re/Generate QR Code</a>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col">
								<?php
									if ($alamat->qrcode) {
										echo '<div id="qrcode-render" data-url="' . esc(base_url($slug . '/detail/' . $alamat->qrcode)) . '"></div>';
									} else {
										echo "<span class='text-danger font-weight-bold'>Belum ada QR Code</span>";
									}
								?>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label>PIN Layanan <small class="text-muted">(dipakai warga di alamat ini untuk mengajukan Surat Keterangan lewat form Layanan publik)</small></label>
									<input type="text" name="kode_rumah" class="form-control" placeholder="Belum diatur" value="<?php echo esc($alamat->kode_rumah ?? '') ?>">
									<?php if (empty($alamat->kode_rumah)): ?>
										<small class="text-danger">PIN belum diatur — warga di alamat ini belum bisa mengajukan surat lewat form Layanan publik.</small>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
					<!-- /.card-body -->

					<div class="card-footer">
						<a href="<?php echo base_url('admin/alamat') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>

<script src="<?php echo base_url('public/plugins/qrcodejs/qrcode.min.js') ?>"></script>
<script>
	var qrEl = document.getElementById('qrcode-render');
	if (qrEl) {
		new QRCode(qrEl, { text: qrEl.dataset.url, width: 160, height: 160 });
	}
</script>