<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/warga/add') ?>" class="btn btn-primary">Tambah Warga</a></div>
		<div class="col text-right"><a href="<?php echo base_url('admin/warga/export') ?>" class="btn btn-success">Export Data</a></div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-bordered table-striped datatable">
							<thead>
								<tr>
									<th width="1">ID.</th>
									<th>Nama</th>
									<th>Usia</th>
									<th>Alamat RT29 / KK</th>
									<th class="text-center">Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($wargas as $i => $warga) {
								?>
									<tr>
										<td>
											<?php echo ($i + 1) ?>
										</td>
										<td>
											<?php echo $warga->nama_warga ?> <br>
											<span class="text-muted small">KK: <?php echo $warga->no_kk ?> | </span>
											<span class="text-muted small">NIK: <?php echo $warga->nik ?></span><br>
											<span class="text-muted small">Status: <?php echo $warga->status_keluarga ?></span><br>
											<span class="text-muted small">Air: <?php echo $warga->sumber_air ?? '-' ?></span>
										</td>
										<td>
											<?php
											$tanggal_lahir = new DateTime($warga->tanggal_lahir);
											$today = new DateTime();
											$age = $today->diff($tanggal_lahir)->y;
											echo $age . ' tahun' . ($age >= 60 ? '<label class="ml-1 badge badge-pills badge-danger">Lansia</label>' : '');
											?>
										</td>
										<td>
											<?php echo 'Jl. ' . $warga->alamat; ?><br>
											<span class="text-muted small"><?php echo ucwords($warga->alamat_lengkap) ?></span>
										</td>
										<td class="text-center">
											<?php
											echo $warga->status_warga == 1 ? '<label class="badge badge-pills badge-success">Aktif</label>' : '<label class="badge badge-pills badge-danger">Non  Aktif</label>';
											echo '<label class="ml-1 badge badge-pills badge-' . $warga->label_penduduk . '">' . $warga->status_penduduk . '</label>';
											if ($warga->is_hidup == 0) {
												echo '<label class="ml-1 badge badge-pills badge-danger">Meninggal</label>';
											};
											?>
										</td>
										<td>
											<a class="mr-2" href="<?php echo base_url('admin/warga/view/' . $warga->id_warga) ?>">
												<i class="far fa-eye"></i>
											</a>
											<a class="text-success mr-1" href="<?php echo base_url('admin/warga/edit/' . $warga->id_warga) ?>">
												<i class="far fa-edit"></i>
											</a>
											<?php
											if ($warga->no_hp) {
												echo '<a class="text-danger" href="https://wa.me/62' . $warga->no_hp . '"><i class="fa fa-phone"></i></a>';
											}
											?>
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