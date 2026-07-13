<div class="container-fluid">
	<div class="row mb-3">
		<div class="col">
			<div class="card card-outline card-primary">
				<div class="card-body d-flex justify-content-between align-items-center">
					<h4 class="mb-0"><?= esc($warga->nama_warga) ?></h4>
					<a href="javascript:history.back()" class="btn btn-sm btn-light">Kembali</a>
				</div>
			</div>
		</div>
	</div>

	<?php if (empty($riwayat)): ?>
		<div class="row">
			<div class="col">
				<div class="card"><div class="card-body text-muted text-center py-4">Belum ada riwayat kesehatan untuk warga ini.</div></div>
			</div>
		</div>
	<?php else: ?>
		<div class="row">
			<div class="col-md-6">
				<div class="card card-outline card-secondary">
					<div class="card-header"><h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>Tekanan Darah (mmHg)</h3></div>
					<div class="card-body"><canvas id="chartTensi" height="110"></canvas></div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card card-outline card-secondary">
					<div class="card-header"><h3 class="card-title"><i class="fas fa-weight mr-2"></i>Berat &amp; Lingkar Perut</h3></div>
					<div class="card-body"><canvas id="chartBerat" height="110"></canvas></div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card card-outline card-secondary">
					<div class="card-header"><h3 class="card-title"><i class="fas fa-tint mr-2"></i>Gula Darah (mg/dL)</h3></div>
					<div class="card-body"><canvas id="chartGula" height="110"></canvas></div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card card-outline card-secondary">
					<div class="card-header"><h3 class="card-title"><i class="fas fa-vial mr-2"></i>Kolesterol &amp; Asam Urat (mg/dL)</h3></div>
					<div class="card-body"><canvas id="chartLain" height="110"></canvas></div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-2"></i>Riwayat Lengkap</h3></div>
					<div class="card-body p-0">
						<div class="table-responsive">
						<table class="table table-bordered table-striped mb-0">
							<thead>
								<tr>
									<th>Kegiatan</th>
									<th>Tanggal</th>
									<th>Tensi</th>
									<th>BB</th>
									<th>TB</th>
									<th>Lingkar Perut</th>
									<th>Gula Darah</th>
									<th>Kolesterol</th>
									<th>Asam Urat</th>
									<th>Catatan</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach (array_reverse($riwayat) as $r): ?>
									<tr>
										<td><?= esc($r->nama_kegiatan) ?></td>
										<td><?= tanggal($r->tanggal_kegiatan) ?></td>
										<td><?= ($r->tensi_sistol !== null && $r->tensi_diastol !== null) ? esc($r->tensi_sistol . '/' . $r->tensi_diastol) : '-' ?></td>
										<td><?= $r->berat_badan !== null ? esc($r->berat_badan) . ' kg' : '-' ?></td>
										<td><?= $r->tinggi_badan !== null ? esc($r->tinggi_badan) . ' cm' : '-' ?></td>
										<td><?= $r->lingkar_perut !== null ? esc($r->lingkar_perut) . ' cm' : '-' ?></td>
										<td><?= $r->gula_darah !== null ? esc($r->gula_darah) . ' (' . esc($r->gula_darah_ket ?? '-') . ')' : '-' ?></td>
										<td><?= $r->kolesterol !== null ? esc($r->kolesterol) : '-' ?></td>
										<td><?= $r->asam_urat !== null ? esc($r->asam_urat) : '-' ?></td>
										<td><?= esc($r->catatan ?? '-') ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php if (!empty($riwayat)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var riwayat = <?= json_encode(array_map(static fn ($r) => [
		'tanggal'        => date('d/m/Y', strtotime($r->tanggal_kegiatan)),
		'tensi_sistol'   => $r->tensi_sistol,
		'tensi_diastol'  => $r->tensi_diastol,
		'berat_badan'    => $r->berat_badan,
		'lingkar_perut'  => $r->lingkar_perut,
		'gula_darah'     => $r->gula_darah,
		'kolesterol'     => $r->kolesterol,
		'asam_urat'      => $r->asam_urat,
	], $riwayat)) ?>;

	var labels = riwayat.map(function (r) { return r.tanggal; });

	function toNumbers(key) {
		return riwayat.map(function (r) {
			var v = r[key];
			return v === null ? null : parseFloat(v);
		});
	}

	new Chart(document.getElementById('chartTensi'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [
				{ label: 'Sistol', data: toNumbers('tensi_sistol'), borderColor: '#dc3545', spanGaps: true },
				{ label: 'Diastol', data: toNumbers('tensi_diastol'), borderColor: '#17a2b8', spanGaps: true }
			]
		},
		options: { responsive: true, scales: { y: { beginAtZero: false } } }
	});

	new Chart(document.getElementById('chartBerat'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [
				{ label: 'Berat Badan (kg)', data: toNumbers('berat_badan'), borderColor: '#28a745', spanGaps: true },
				{ label: 'Lingkar Perut (cm)', data: toNumbers('lingkar_perut'), borderColor: '#ffc107', spanGaps: true }
			]
		},
		options: { responsive: true, scales: { y: { beginAtZero: false } } }
	});

	new Chart(document.getElementById('chartGula'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [
				{ label: 'Gula Darah', data: toNumbers('gula_darah'), borderColor: '#6610f2', spanGaps: true }
			]
		},
		options: { responsive: true, scales: { y: { beginAtZero: true } } }
	});

	new Chart(document.getElementById('chartLain'), {
		type: 'line',
		data: {
			labels: labels,
			datasets: [
				{ label: 'Kolesterol', data: toNumbers('kolesterol'), borderColor: '#fd7e14', spanGaps: true },
				{ label: 'Asam Urat', data: toNumbers('asam_urat'), borderColor: '#20c997', spanGaps: true }
			]
		},
		options: { responsive: true, scales: { y: { beginAtZero: true } } }
	});
});
</script>
<?php endif; ?>
