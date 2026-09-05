<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/papan-informasi/add') ?>" class="btn btn-primary" >Tambah Papan Informasi</a></div>
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
								<th class="text-center">Judul</th>
								<th class="text-center">Isi</th>
								<th class="text-center">Status</th>
								<th class="text-center">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($papans as $i=>$papan) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td>
									<?php echo $papan->judul ?>
									<?php if (!empty($papan->lampiran)): ?>
										<br><a target="_blank" class="text-muted" href="<?php echo $papan->lampiran ?>"><img src="<?php echo base_url('public/img/pdf.svg') ?>" width="16" > &nbsp; Lampiran.pdf</a>
									<?php endif ?>
								</td>
								<td width="450"><?php echo substr(strip_tags($papan->isi), 0, 175) ?>...</td>
								<td class="text-center"><?php echo $papan->is_status ? "<span class='badge badge-success'>Publish</span>" : "<span class='badge badge-secondary'>Draft</span>" ?></td>
								<td class="text-center" width="1">
									<a class="text-success mr-1" href="<?php echo base_url('admin/papan-informasi/edit/'.$papan->id_papan) ?>">
										<i class="far fa-edit"></i>
									</a>
									<a class="text-danger" href="<?php echo base_url('admin/papan-informasi/delete/'.$papan->id_papan) ?>" onclick="return confirm('Apakah anda yakin ingin menghapus data ini?')">
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
