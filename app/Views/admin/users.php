<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/users/add') ?>" class="btn btn-primary">Tambah User</a></div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Username</th>
								<th>Email</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($users as $i => $user) {
							?>
								<tr>
									<td><?php echo $i + 1 ?></td>
									<td><?php echo $user->username ?></td>
									<td><?php echo $user->email ?></td>
									<td><?php echo !$user->isBanned() ? '<label class="badge badge-pills badge-success">Aktif</label>' : '<label class="badge badge-pills badge-danger">Non  Aktif</label>' ?></td>
									<td>
										<a href="<?php echo base_url('admin/users/edit/' . $user->id) ?>">
											<i class="far fa-edit"></i>
										</a>

										<?php if (!$user->isBanned()): ?>
											<a onclick="return confirm('Apakah Anda yakin akan men-nonaktifkan data ini?')" href="<?php echo base_url('admin/users/delete/' . $user->id) ?>">
												<i class="fas fa-power-off text-danger"></i>
											</a>
										<?php endif ?>
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