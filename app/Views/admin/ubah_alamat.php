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