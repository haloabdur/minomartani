<div class="container-fluid">
	<div class="row mb-3">
		<div class="col">
			<a href="<?= base_url('admin/kesehatan/add') ?>" class="btn btn-primary">
				<i class="fas fa-plus mr-1"></i> Tambah Kegiatan
			</a>
		</div>
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
								<th>Nama Kegiatan</th>
								<th>Tanggal</th>
								<th>Jumlah Peserta Tercatat</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($kegiatans)): ?>
								<tr>
									<td colspan="5" class="text-center text-muted py-4">Belum ada kegiatan kesehatan.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($kegiatans as $i => $kegiatan): ?>
									<tr>
										<td><?= $i + 1 ?></td>
										<td><?= esc($kegiatan->nama_kegiatan) ?></td>
										<td><?= tanggal($kegiatan->tanggal_kegiatan) ?></td>
										<td><span class="badge badge-info"><?= (int) $kegiatan->jumlah_peserta ?> orang</span></td>
										<td>
											<a href="<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan) ?>">
												<i class="fas fa-clipboard-list"></i> Catat Peserta
											</a>
											&nbsp;|&nbsp;
											<a href="<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/edit') ?>">
												<i class="far fa-edit"></i> Ubah
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
