<div class="container-fluid">
	<div class="row mb-3">
		<div class="col">
			<a href="<?= base_url('admin/presensi/add') ?>" class="btn btn-primary">
				<i class="fas fa-plus mr-1"></i> Tambah Acara
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
								<th>Nama Acara</th>
								<th>Cakupan</th>
								<th>Tanggal</th>
								<th>Tempat</th>
								<th>Kehadiran</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($acaras)): ?>
								<tr>
									<td colspan="7" class="text-center text-muted py-4">Belum ada acara presensi.</td>
								</tr>
							<?php else: ?>
								<?php foreach ($acaras as $i => $acara): ?>
									<tr>
										<td><?= $i + 1 ?></td>
										<td><?= esc($acara->nama_acara) ?></td>
										<td>
											<?php if ($acara->id_rw !== null): ?>
												<span class="badge badge-primary"><?= esc($acara->nama_rw ?? '-') ?></span>
											<?php else: ?>
												<span class="badge badge-secondary"><?= esc($acara->nama_rt ?? '-') ?></span>
											<?php endif; ?>
										</td>
										<td><?= tanggal($acara->tanggal_acara) ?></td>
										<td><?= esc($acara->tempat ?: '-') ?></td>
										<td class="text-nowrap">
											<span class="badge badge-success"><?= (int) $acara->jumlah_hadir ?> hadir</span>
											<span class="badge badge-danger"><?= (int) $acara->jumlah_tidak ?> tidak</span>
										</td>
										<td>
											<a href="<?= base_url('admin/presensi/acara/' . $acara->id_acara) ?>">
												<i class="fas fa-clipboard-check"></i> Presensi
											</a>
											<?php if ($acara->id_rw === null || current_rw_id() !== null || auth()->user()->inGroup('superadmin')): ?>
												&nbsp;|&nbsp;
												<a href="<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/edit') ?>">
													<i class="far fa-edit"></i> Ubah
												</a>
											<?php endif; ?>
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
