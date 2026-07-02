<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/pekerjaan/add') ?>" class="btn btn-primary" >Tambah Pekerjaan</a></div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Pekerjaan</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($pekerjaans as $i=>$pekerjaan) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td><?php echo $pekerjaan->nama_pekerjaan ?></td>
								<td>
									<a href="<?php echo base_url('admin/pekerjaan/edit/'.$pekerjaan->id_pekerjaan) ?>">
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