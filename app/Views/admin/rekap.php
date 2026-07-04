<?php
$total_rt    = count($rekap);
$total_warga = 0;
$total_kk    = 0;
$total_l     = 0;
$total_p     = 0;
$total_surat = 0;

foreach ($rekap as $rt) {
	$total_warga += (int) $rt->jml_warga;
	$total_kk    += (int) $rt->jml_kk;
	$total_l     += (int) $rt->jml_l;
	$total_p     += (int) $rt->jml_p;
	$total_surat += (int) $rt->jml_surat;
}

// Accumulation across every warga in every RT this account can see -
// gender is already covered by $total_l/$total_p above.
$umur_balita = 0; // 0-5
$umur_anak = 0;   // 6-11
$umur_remaja = 0; // 12-25
$umur_dewasa = 0; // 26-59
$umur_lansia = 0; // 60+

$pend_belum = 0;
$pend_sd = 0;
$pend_smp = 0;
$pend_sma = 0;
$pend_kuliah = 0;

// Per-RT breakdown, keyed by id_rt, used to drive the clickable filters
// against the RT summary table below.
$rtCat = [];
foreach ($rekap as $rt) {
	$rtCat[$rt->id_rt] = [
		'age-balita' => 0, 'age-anak' => 0, 'age-remaja' => 0, 'age-dewasa' => 0, 'age-lansia' => 0,
		'edu-belum' => 0, 'edu-sd' => 0, 'edu-smp' => 0, 'edu-sma' => 0, 'edu-kuliah' => 0,
	];
}

foreach ($wargas as $w) {
	$tgl_lahir = new DateTime($w->tanggal_lahir);
	$today = new DateTime();
	$age = $today->diff($tgl_lahir)->y;

	if ($age <= 5) {
		$umur_balita++;
		$ageKey = 'age-balita';
	} else if ($age <= 11) {
		$umur_anak++;
		$ageKey = 'age-anak';
	} else if ($age <= 25) {
		$umur_remaja++;
		$ageKey = 'age-remaja';
	} else if ($age <= 59) {
		$umur_dewasa++;
		$ageKey = 'age-dewasa';
	} else {
		$umur_lansia++;
		$ageKey = 'age-lansia';
	}

	$p = strtoupper(trim($w->pendidikan));
	if ($p == '-' || empty($p) || $p == 'BELUM SEKOLAH' || $p == 'TIDAK SEKOLAH') {
		$pend_belum++;
		$eduKey = 'edu-belum';
	} else if ($p == 'SD') {
		$pend_sd++;
		$eduKey = 'edu-sd';
	} else if ($p == 'SMP') {
		$pend_smp++;
		$eduKey = 'edu-smp';
	} else if ($p == 'SMA' || $p == 'SMK') {
		$pend_sma++;
		$eduKey = 'edu-sma';
	} else {
		$pend_kuliah++;
		$eduKey = 'edu-kuliah';
	}

	if (isset($rtCat[$w->id_rt])) {
		$rtCat[$w->id_rt][$ageKey]++;
		$rtCat[$w->id_rt][$eduKey]++;
	}
}
?>
<div class="container-fluid">
	<!-- mini statistik semua RT -->
	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card card-outline card-primary h-100 mb-0">
				<div class="card-header py-2">
					<h3 class="card-title font-weight-bold text-xs"><i class="fas fa-users mr-1"></i> Total Warga</h3>
				</div>
				<div class="card-body p-3">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<span class="display-4 font-weight-bold text-primary" style="font-size: 2.2rem; line-height: 1;"><?= $total_warga ?></span>
						<span class="text-muted small">Orang / <?= $total_rt ?> RT</span>
					</div>
					<div class="row pt-2 border-top">
						<div class="col-4 border-right py-0">
							<span class="d-block text-center">
								<h6 class="font-weight-bold text-warning mb-0"><i class="fas fa-home mr-1"></i><?= $total_kk ?></h6>
								<span class="text-muted text-xs">KK</span>
							</span>
						</div>
						<div class="col-4 border-right py-0">
							<span class="d-block text-center">
								<h6 class="font-weight-bold text-info mb-0"><i class="fas fa-male mr-1"></i><?= $total_l ?></h6>
								<span class="text-muted text-xs">Laki-Laki</span>
							</span>
						</div>
						<div class="col-4 py-0">
							<span class="d-block text-center">
								<h6 class="font-weight-bold text-danger mb-0"><i class="fas fa-female mr-1"></i><?= $total_p ?></h6>
								<span class="text-muted text-xs">Perempuan</span>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-4">
			<div class="card card-outline card-success h-100 mb-0">
				<div class="card-header py-2">
					<h3 class="card-title font-weight-bold text-xs"><i class="fas fa-birthday-cake mr-1"></i> Kelompok Umur</h3>
				</div>
				<div class="card-body p-0">
					<div class="list-group list-group-unbordered list-group-flush" style="font-size: 0.85rem;">
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="age-balita">
							<span>Balita (0-5 th)</span>
							<span class="badge badge-info badge-pill"><?= $umur_balita ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="age-anak">
							<span>Anak-Anak (6-11 th)</span>
							<span class="badge badge-success badge-pill"><?= $umur_anak ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="age-remaja">
							<span>Remaja (12-25 th)</span>
							<span class="badge badge-warning badge-pill"><?= $umur_remaja ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="age-dewasa">
							<span>Dewasa (26-59 th)</span>
							<span class="badge badge-primary badge-pill"><?= $umur_dewasa ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="age-lansia">
							<span>Lansia (60+ th)</span>
							<span class="badge badge-danger badge-pill"><?= $umur_lansia ?></span>
						</a>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-3">
			<div class="card card-outline card-warning h-100 mb-0">
				<div class="card-header py-2">
					<h3 class="card-title font-weight-bold text-xs"><i class="fas fa-graduation-cap mr-1"></i> Pendidikan</h3>
				</div>
				<div class="card-body p-0">
					<div class="list-group list-group-unbordered list-group-flush" style="font-size: 0.85rem;">
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="edu-belum">
							<span>Belum Sekolah</span>
							<span class="badge badge-secondary badge-pill"><?= $pend_belum ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="edu-sd">
							<span>SD</span>
							<span class="badge badge-info badge-pill"><?= $pend_sd ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="edu-smp">
							<span>SMP</span>
							<span class="badge badge-success badge-pill"><?= $pend_smp ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="edu-sma">
							<span>SMA/SMK</span>
							<span class="badge badge-warning badge-pill"><?= $pend_sma ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-key="edu-kuliah">
							<span>Perguruan Tinggi</span>
							<span class="badge badge-primary badge-pill"><?= $pend_kuliah ?></span>
						</a>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-2">
			<div class="card card-outline card-danger h-100 mb-0 d-flex flex-column justify-content-between">
				<div class="card-header py-2">
					<h3 class="card-title font-weight-bold text-xs"><i class="fas fa-filter mr-1"></i> Filter Aktif</h3>
				</div>
				<div class="card-body p-3 d-flex flex-column justify-content-between" style="min-height: 135px;">
					<div id="active-filter-info">
						<p class="text-xs text-muted mb-1">Menampilkan:</p>
						<h6 class="font-weight-bold text-sm mb-2" id="filter-display-text">Semua RT</h6>
					</div>
					<button type="button" class="btn btn-block btn-outline-danger btn-xs mt-auto" id="btn-reset-filter" style="display: none;">
						<i class="fas fa-sync mr-1"></i> Reset Filter
					</button>
				</div>
			</div>
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
								<th>RT</th>
								<th>Jumlah Warga</th>
								<th>Jumlah KK</th>
								<th>Laki-laki</th>
								<th>Perempuan</th>
								<th>Surat</th>
								<th>Detail</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rekap as $i => $rt): ?>
							<?php $cat = $rtCat[$rt->id_rt]; ?>
							<tr
								<?php foreach ($cat as $key => $count): ?>
								data-<?= $key ?>="<?= $count ?>"
								<?php endforeach ?>
							>
								<td><?= $i + 1 ?></td>
								<td><?= esc($rt->nama) ?></td>
								<td><?= $rt->jml_warga ?></td>
								<td><?= $rt->jml_kk ?></td>
								<td><?= $rt->jml_l ?></td>
								<td><?= $rt->jml_p ?></td>
								<td><?= $rt->jml_surat ?></td>
								<td>
									<a href="<?= base_url('admin/rekap/warga/' . $rt->id_rt) ?>">
										<i class="far fa-eye"></i> Lihat Warga
									</a>
								</td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		let activeFilterKey = null;

		// Only shows RTs that have at least one warga in the selected
		// age-group / pendidikan category (counts are accumulated across
		// all warga in all visible RTs, not per-row values).
		jQuery.fn.dataTable.ext.search.push(
			function(settings, data, dataIndex) {
				if (!activeFilterKey) {
					return true;
				}

				var row = settings.aoData[dataIndex].nTr;
				var count = parseInt(jQuery(row).data(activeFilterKey), 10) || 0;

				return count > 0;
			}
		);

		jQuery(document).on('click', '.filter-trigger', function(e) {
			e.preventDefault();
			var key = jQuery(this).data('filter-key');
			var label = jQuery(this).find('span').first().text().trim();

			activeFilterKey = key;

			jQuery('#filter-display-text').text(label);
			jQuery('#btn-reset-filter').show();

			jQuery('.filter-trigger').removeClass('active bg-light border-primary');
			jQuery(this).addClass('active bg-light border-primary');

			jQuery('.datatable').DataTable().draw();
		});

		jQuery(document).on('click', '#btn-reset-filter', function() {
			activeFilterKey = null;

			jQuery('#filter-display-text').text('Semua RT');
			jQuery(this).hide();

			jQuery('.filter-trigger').removeClass('active bg-light border-primary');

			jQuery('.datatable').DataTable().draw();
		});
	});
</script>
