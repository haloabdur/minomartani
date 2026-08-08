<?php
	$totalHadir = 0;
	foreach ($riwayat as $r) {
		if ($r->status === 'hadir') {
			$totalHadir++;
		}
	}
?>
<div class="container-fluid">
	<div class="row mb-3">
		<div class="col">
			<div class="card card-outline card-primary">
				<div class="card-body d-flex justify-content-between align-items-center">
					<div>
						<h4 class="mb-0"><?= esc($warga->nama_warga) ?></h4>
						<span class="text-muted small">
							Hadir <?= $totalHadir ?> dari <?= count($riwayat) ?> acara yang tercatat
						</span>
					</div>
					<a href="javascript:history.back()" class="btn btn-sm btn-light">Kembali</a>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Presensi</h3></div>
				<div class="card-body p-0">
					<?php if (empty($riwayat)): ?>
						<div class="text-muted text-center py-4">Belum ada riwayat presensi untuk warga ini.</div>
					<?php else: ?>
						<div class="table-responsive">
						<table class="table table-bordered table-striped mb-0">
							<thead>
								<tr>
									<th>Acara</th>
									<th>Tanggal</th>
									<th>Tempat</th>
									<th>Status</th>
									<th>Jam</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($riwayat as $r): ?>
									<tr>
										<td><?= esc($r->nama_acara) ?></td>
										<td><?= tanggal($r->tanggal_acara) ?></td>
										<td><?= esc($r->tempat ?: '-') ?></td>
										<td>
											<?php if ($r->status === 'hadir'): ?>
												<span class="badge badge-success">Hadir</span>
											<?php else: ?>
												<span class="badge badge-danger">Tidak hadir</span>
											<?php endif; ?>
										</td>
										<td><?= !empty($r->waktu) ? date('H:i', strtotime($r->waktu)) : '-' ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
