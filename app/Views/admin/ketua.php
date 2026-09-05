<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/ketua/add') ?>" class="btn btn-primary" >Tambah Ketua RT</a></div>
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
								<th class="text-center">Foto</th>
								<th class="text-center">Nama Ketua</th>
								<th class="text-center">Mulai</th>
								<th class="text-center">Selesai</th>
								<th class="text-center">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($ketuas as $i=>$ketua) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td class="text-center" width="1">
									<?php if (!empty($ketua->foto_ketua)): ?>
										<img src="<?php echo base_url('public/ketua/'.$ketua->foto_ketua) ?>" width="50">
									<?php else: ?>
										<span class="text-muted">-</span>
									<?php endif ?>
								</td>
								<td><?php echo $ketua->nama_ketua ?></td>
								<td class="text-center"><?php echo $ketua->mulai ?></td>
								<td class="text-center"><?php echo $ketua->selesai ?></td>
								<td class="text-center" width="1">
									<a class="text-success mr-1" href="<?php echo base_url('admin/ketua/edit/'.$ketua->id_ketua) ?>">
										<i class="far fa-edit"></i>
									</a>
									<a class="text-danger" href="<?php echo base_url('admin/ketua/delete/'.$ketua->id_ketua) ?>" onclick="return confirm('Apakah anda yakin ingin menghapus data ini?')">
										<i class="far fa-trash-alt"></i>
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
