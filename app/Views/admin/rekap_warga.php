<?php
$total_warga = count($wargas);
$total_lk = 0;
$total_pr = 0;

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

foreach ($wargas as $w) {
	if ($w->jenis_kelamin == 'L') {
		$total_lk++;
	} else if ($w->jenis_kelamin == 'P') {
		$total_pr++;
	}

	$tgl_lahir = new DateTime($w->tanggal_lahir);
	$today = new DateTime();
	$age = $today->diff($tgl_lahir)->y;

	if ($age <= 5) {
		$umur_balita++;
	} else if ($age <= 11) {
		$umur_anak++;
	} else if ($age <= 25) {
		$umur_remaja++;
	} else if ($age <= 59) {
		$umur_dewasa++;
	} else {
		$umur_lansia++;
	}

	$p = strtoupper(trim($w->pendidikan));
	if ($p == '-' || empty($p) || $p == 'BELUM SEKOLAH' || $p == 'TIDAK SEKOLAH') {
		$pend_belum++;
	} else if ($p == 'SD') {
		$pend_sd++;
	} else if ($p == 'SMP') {
		$pend_smp++;
	} else if ($p == 'SMA' || $p == 'SMK') {
		$pend_sma++;
	} else {
		$pend_kuliah++;
	}
}
?>
<div class="container-fluid">
	<div class="row mb-3">
		<div class="col"><a href="<?= base_url('admin/rekap') ?>" class="btn btn-light"><i class="fa fa-arrow-left"></i> Kembali ke Rekap</a></div>
		<div class="col text-right">
			<a id="btn-export-warga" href="<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>" class="btn btn-success">Export Data</a>
			<button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-export-custom" title="Pilih kolom export">
				<i class="fas fa-cog"></i>
			</button>
		</div>
	</div>

	<!-- mini statistik -->
	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card card-outline card-primary h-100 mb-0">
				<div class="card-header py-2">
					<h3 class="card-title font-weight-bold text-xs"><i class="fas fa-users mr-1"></i> Total Warga</h3>
				</div>
				<div class="card-body p-3">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<span class="display-4 font-weight-bold text-primary" style="font-size: 2.2rem; line-height: 1;"><?php echo $total_warga; ?></span>
						<span class="text-muted small">Orang</span>
					</div>
					<div class="row pt-2 border-top">
						<div class="col-6 border-right py-0">
							<a href="javascript:void(0)" class="filter-trigger text-dark d-block text-center" data-filter-type="gender" data-filter-value="L">
								<h6 class="font-weight-bold text-info mb-0"><i class="fas fa-male mr-1"></i><?php echo $total_lk; ?></h6>
								<span class="text-muted text-xs">Laki-Laki</span>
							</a>
						</div>
						<div class="col-6 py-0">
							<a href="javascript:void(0)" class="filter-trigger text-dark d-block text-center" data-filter-type="gender" data-filter-value="P">
								<h6 class="font-weight-bold text-danger mb-0"><i class="fas fa-female mr-1"></i><?php echo $total_pr; ?></h6>
								<span class="text-muted text-xs">Perempuan</span>
							</a>
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
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="age-group" data-filter-value="balita">
							<span>Balita (0-5 th)</span>
							<span class="badge badge-info badge-pill"><?php echo $umur_balita; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="age-group" data-filter-value="anak">
							<span>Anak-Anak (6-11 th)</span>
							<span class="badge badge-success badge-pill"><?php echo $umur_anak; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="age-group" data-filter-value="remaja">
							<span>Remaja (12-25 th)</span>
							<span class="badge badge-warning badge-pill"><?php echo $umur_remaja; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="age-group" data-filter-value="dewasa">
							<span>Dewasa (26-59 th)</span>
							<span class="badge badge-primary badge-pill"><?php echo $umur_dewasa; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="age-group" data-filter-value="lansia">
							<span>Lansia (60+ th)</span>
							<span class="badge badge-danger badge-pill"><?php echo $umur_lansia; ?></span>
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
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="education" data-filter-value="BELUM_SEKOLAH">
							<span>Belum Sekolah</span>
							<span class="badge badge-secondary badge-pill"><?php echo $pend_belum; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="education" data-filter-value="SD">
							<span>SD</span>
							<span class="badge badge-info badge-pill"><?php echo $pend_sd; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="education" data-filter-value="SMP">
							<span>SMP</span>
							<span class="badge badge-success badge-pill"><?php echo $pend_smp; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="education" data-filter-value="SMA">
							<span>SMA/SMK</span>
							<span class="badge badge-warning badge-pill"><?php echo $pend_sma; ?></span>
						</a>
						<a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-3 filter-trigger" data-filter-type="education" data-filter-value="KULIAH">
							<span>Perguruan Tinggi</span>
							<span class="badge badge-primary badge-pill"><?php echo $pend_kuliah; ?></span>
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
						<h6 class="font-weight-bold text-sm mb-2" id="filter-display-text">Semua Warga</h6>
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
								<th>Nama</th>
								<th>NIK</th>
								<th>No. KK</th>
								<th>Alamat</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wargas as $i => $warga): ?>
							<?php
								$tanggal_lahir = new DateTime($warga->tanggal_lahir);
								$today = new DateTime();
								$age = $today->diff($tanggal_lahir)->y;

								$p = strtoupper(trim($warga->pendidikan));
								if ($p == '-' || empty($p) || $p == 'BELUM SEKOLAH' || $p == 'TIDAK SEKOLAH') {
									$edu = 'BELUM_SEKOLAH';
								} else if ($p == 'SD') {
									$edu = 'SD';
								} else if ($p == 'SMP') {
									$edu = 'SMP';
								} else if ($p == 'SMA' || $p == 'SMK') {
									$edu = 'SMA';
								} else {
									$edu = 'KULIAH';
								}
							?>
							<tr data-gender="<?= esc($warga->jenis_kelamin) ?>"
								data-age="<?= $age ?>"
								data-age-group="<?= ($age <= 5 ? 'balita' : ($age <= 11 ? 'anak' : ($age <= 25 ? 'remaja' : ($age <= 59 ? 'dewasa' : 'lansia')))) ?>"
								data-education="<?= $edu ?>">
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
</div>

<div class="modal fade" id="modal-export-custom" tabindex="-1" role="dialog" aria-labelledby="modalExportCustomLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalExportCustomLabel">Pilih Kolom Export</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="mb-2">
					<a href="javascript:void(0)" id="export-columns-select-all">Pilih Semua</a> /
					<a href="javascript:void(0)" id="export-columns-select-none">Kosongkan Semua</a>
				</div>
				<div class="row">
					<?php foreach (\App\Models\WargaModel::EXPORT_COLUMNS as $key => $label) : ?>
						<div class="col-md-4">
							<div class="form-check">
								<input class="form-check-input export-column-checkbox" type="checkbox" value="<?php echo esc($key) ?>" id="export-col-<?php echo esc($key) ?>" checked>
								<label class="form-check-label" for="export-col-<?php echo esc($key) ?>"><?php echo esc($label) ?></label>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<hr>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="export-include-deceased">
					<label class="form-check-label" for="export-include-deceased">Sertakan warga yang sudah meninggal</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
				<button type="button" class="btn btn-success" id="btn-export-custom-confirm">Export</button>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		let activeFilterType = null;
		let activeFilterValue = null;

		jQuery.fn.dataTable.ext.search.push(
			function(settings, data, dataIndex) {
				if (!activeFilterType) {
					return true;
				}

				var row = settings.aoData[dataIndex].nTr;
				var val = jQuery(row).data(activeFilterType);

				return val == activeFilterValue;
			}
		);

		jQuery(document).on('click', '.filter-trigger', function(e) {
			e.preventDefault();
			var type = jQuery(this).data('filter-type');
			var val = jQuery(this).data('filter-value');

			var label = jQuery(this).find('span').first().text().trim() || jQuery(this).text().trim();

			activeFilterType = type;
			activeFilterValue = val;

			jQuery('#filter-display-text').text(label);
			jQuery('#btn-reset-filter').show();

			jQuery('.filter-trigger').removeClass('active bg-light border-primary');
			jQuery(this).addClass('active bg-light border-primary');

			var exportUrl = "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>?type=" + encodeURIComponent(type) + "&value=" + encodeURIComponent(val);
			jQuery('#btn-export-warga').attr('href', exportUrl);

			jQuery('.datatable').DataTable().draw();
		});

		jQuery(document).on('click', '#btn-reset-filter', function() {
			activeFilterType = null;
			activeFilterValue = null;

			jQuery('#filter-display-text').text('Semua Warga');
			jQuery(this).hide();

			jQuery('.filter-trigger').removeClass('active bg-light border-primary');

			jQuery('#btn-export-warga').attr('href', "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>");

			jQuery('.datatable').DataTable().draw();
		});

		jQuery('#export-columns-select-all').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', true);
		});

		jQuery('#export-columns-select-none').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', false);
		});

		jQuery('#btn-export-custom-confirm').on('click', function() {
			var selected = jQuery('.export-column-checkbox:checked').map(function() {
				return this.value;
			}).get();

			var params = new URLSearchParams();
			if (activeFilterType) {
				params.set('type', activeFilterType);
				params.set('value', activeFilterValue);
			}
			params.set('columns', selected.join(','));
			params.set('include_deceased', jQuery('#export-include-deceased').is(':checked') ? '1' : '0');

			window.location.href = "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>?" + params.toString();
		});
	});
</script>
