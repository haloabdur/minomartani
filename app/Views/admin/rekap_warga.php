<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?= base_url('admin/rekap') ?>" class="btn btn-light"><i class="fa fa-arrow-left"></i> Kembali ke Rekap</a></div>
	</div>
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Nama</th>
								<th>NIK</th>
								<th>No. KK</th>
								<th>Alamat</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wargas as $i => $warga): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($warga->nama_warga) ?></td>
								<td><?= esc($warga->nik) ?></td>
								<td><?= esc($warga->no_kk) ?></td>
								<td><?= esc($warga->alamat) ?></td>
								<td><?= esc($warga->status_keluarga) ?></td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
