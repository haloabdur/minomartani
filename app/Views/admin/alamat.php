<div class="container-fluid">
	<!-- <div class="row mb-3">
		<div class="col"><a href="<?php // echo base_url('admin/alamat/add') ?>" class="btn btn-primary" >Tambah Alamat</a></div>
	</div> -->

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Alamat</th>
								<th>Qr Code</th>
								<th class="text-center">Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($alamats as $i=>$alamat) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td><?php echo $alamat->alamat ?></td>
								<td>
									<?php
										if ($alamat->qrcode) {
											echo '<div class="qrcode-render" data-url="' . esc(base_url($slug . '/detail/' . $alamat->qrcode)) . '"></div>';
										} else {
											echo '-';
										}
									?>
								</td>
								<td class="text-center"><?php echo $alamat->jumlah > 0 ? '<label class="badge badge-pills badge-success">Terisi</label>' : '<label class="badge badge-pills badge-danger">Kosong</label>' ?></td>
								<td>
									<a href="<?php echo base_url('admin/alamat/edit/'.$alamat->id_alamat) ?>">
										<i class="far fa-edit"></i>
									</a>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					</div>
				</div>
				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>
	</div>
	<!-- /.row -->
</div><!-- /.container-fluid -->

<script src="<?php echo base_url('public/plugins/qrcodejs/qrcode.min.js') ?>"></script>
<script>
	document.querySelectorAll('.qrcode-render').forEach(function (el) {
		new QRCode(el, { text: el.dataset.url, width: 120, height: 120 });
	});
</script>