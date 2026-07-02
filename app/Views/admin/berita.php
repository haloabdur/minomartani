<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?php echo base_url('admin/berita/add') ?>" class="btn btn-primary" >Tambah Berita</a></div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th class="text-center">Gambar</th>
								<th class="text-center">Judul</th>
								<th class="text-center">Diskripsi</th>
								<th class="text-center">Kategori</th>
								<th class="text-center">Status</th>
								<th class="text-center">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($beritas as $i=>$berita) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td width="1"><img class="rounded" src="<?php echo base_url('public/berita/'.$berita->foto) ?>" width="56" ></td>
								<td>
									<?php echo $berita->judul ?>
									<?php if (!empty($berita->lampiran)): ?>
										<br><a target="_blank" class="text-muted" href="<?php echo $berita->lampiran ?>"><img src="<?php echo base_url('public/img/pdf.svg') ?>" width="16" > &nbsp; Lampiran.pdf</a>
									<?php endif ?>
								</td>
								<td width="450"><?php echo substr($berita->deskripsi, 0, 175) ?>...</td>
								<td width="100"><?php echo $berita->kategori ?></td>
								<td class="text-center"><?php echo $berita->is_status ? "<span class='badge badge-success'>Publish</span>" : "<span class='badge badge-secondary'>Draft</span>" ?></td>
								<td class="text-center" width="1">
									<a href="<?php echo base_url('admin/berita/edit/'.$berita->id_berita) ?>">
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