<div class="container-fluid">
	<!-- <div class="row mb-3">
		<div class="col"><a href="<?php // echo base_url('admin/alamat/add') ?>" class="btn btn-primary" >Tambah Alamat</a></div>
	</div> -->

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
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
											echo '<a target="_blank" href=""><img width="120" src="' . base_url('public/qrcode/'.$alamat->qrcode.'.png') . '"></a>';
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
				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>
	</div>
	<!-- /.row -->
</div><!-- /.container-fluid -->