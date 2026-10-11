<?php
use App\Models\ActivityLogModel;

$fieldLabels = [
	'nama_warga'         => 'Nama Lengkap',
	'no_kk'              => 'No. KK',
	'nik'                => 'NIK',
	'alamat_lengkap'     => 'Alamat Lengkap',
	'jenis_kelamin'      => 'Jenis Kelamin',
	'tempat_lahir'       => 'Tempat Lahir',
	'tanggal_lahir'      => 'Tanggal Lahir',
	'gol_darah'          => 'Golongan Darah',
	'agama'              => 'Agama',
	'pendidikan'         => 'Pendidikan',
	'id_pekerjaan'       => 'Pekerjaan',
	'status_kawin'       => 'Status Perkawinan',
	'tanggal_kawin'      => 'Tanggal Kawin',
	'id_status_keluarga' => 'Hubungan Keluarga',
	'ayah'               => 'Nama Ayah',
	'ibu'                => 'Nama Ibu',
	'no_hp'              => 'No. HP / WA',
	'email'              => 'Email',
	'status_warga'       => 'Status Warga',
	'id_status_penduduk' => 'Status Domisili',
	'is_hidup'           => 'Status Kehidupan',
	'sumber_air'         => 'Sumber Air',
	'kode_rfid'          => 'Kode RFID',
];

$ignoredFields = ['id_alamat', 'id_rt', 'id_warga'];

$statusKeluargaMap = [
	1 => 'Kepala Keluarga',
	2 => 'Istri',
	3 => 'Anak',
	4 => 'Menantu',
	5 => 'Cucu',
	6 => 'Family Lain',
	7 => 'Orang Tua',
];

$statusPendudukMap = [
	1 => 'Menetap',
	2 => 'Mengontrak',
	3 => 'Sementara',
];

$fmtValue = static function ($v, string $field = '') use ($statusKeluargaMap, $statusPendudukMap): string {
	if ($v === null || $v === '' || $v === '-') {
		return '<span class="text-muted font-italic">—</span>';
	}

	if ($field === 'is_hidup') {
		return ((int) $v === 1)
			? '<span class="badge badge-success px-2 py-1"><i class="fas fa-heart mr-1"></i>Hidup</span>'
			: '<span class="badge badge-danger px-2 py-1"><i class="fas fa-cross mr-1"></i>Meninggal</span>';
	}

	if ($field === 'status_warga') {
		return ((int) $v === 1)
			? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Aktif</span>'
			: '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-times-circle mr-1"></i>Nonaktif / Pindah</span>';
	}

	if ($field === 'jenis_kelamin') {
		if ($v === 'L') {
			return '<i class="fas fa-mars text-info mr-1"></i>Laki-Laki';
		}
		if ($v === 'P') {
			return '<i class="fas fa-venus text-danger mr-1"></i>Perempuan';
		}
	}

	if ($field === 'id_status_keluarga' && isset($statusKeluargaMap[(int) $v])) {
		return esc($statusKeluargaMap[(int) $v]);
	}

	if ($field === 'id_status_penduduk' && isset($statusPendudukMap[(int) $v])) {
		return esc($statusPendudukMap[(int) $v]);
	}

	if (in_array($field, ['tanggal_lahir', 'tanggal_kawin'], true) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v)) {
		return esc(date('d/m/Y', strtotime((string) $v)));
	}

	return esc((string) $v);
};
?>

<style>
	.activity-table thead th {
		background-color: #f8fafc;
		color: #475569;
		font-size: 0.78rem;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		font-weight: 700;
		border-top: none;
		vertical-align: middle;
	}
	.activity-table td {
		vertical-align: middle;
		font-size: 0.88rem;
	}
	.diff-item {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		padding: 3px 0;
		border-bottom: 1px dashed #f1f5f9;
		font-size: 0.85rem;
	}
	.diff-item:last-child {
		border-bottom: none;
	}
	.diff-field-badge {
		font-size: 0.75rem;
		background-color: #f1f5f9;
		color: #334155;
		border: 1px solid #e2e8f0;
		border-radius: 4px;
		padding: 2px 7px;
		margin-right: 6px;
		font-weight: 600;
		display: inline-block;
	}
	.diff-old {
		color: #94a3b8;
		margin-right: 4px;
	}
	.diff-arrow {
		color: #cbd5e1;
		margin: 0 4px;
		font-size: 0.75rem;
	}
	.diff-new {
		color: #0f172a;
		font-weight: 600;
	}
	.user-avatar-badge {
		width: 32px;
		height: 32px;
		line-height: 32px;
		border-radius: 50%;
		background-color: #e0f2fe;
		color: #0284c7;
		text-align: center;
		font-weight: bold;
		font-size: 0.85rem;
		flex-shrink: 0;
	}
	.pagination .page-item .page-link {
		color: #007bff;
		border-radius: 4px;
		margin: 0 2px;
	}
	.pagination .page-item.active .page-link {
		background-color: #007bff;
		border-color: #007bff;
		color: #fff;
	}
</style>

<div class="container-fluid">
	<!-- KPI Summary Widgets -->
	<div class="row mb-3">
		<div class="col-xl-3 col-sm-6 mb-2 mb-xl-0">
			<div class="info-box shadow-sm mb-0 h-100">
				<span class="info-box-icon bg-primary elevation-1"><i class="fas fa-history"></i></span>
				<div class="info-box-content">
					<span class="info-box-text text-muted text-xs text-uppercase font-weight-bold">Total Log RT</span>
					<span class="info-box-number text-lg font-weight-bold"><?= number_format($stats['total'] ?? 0) ?></span>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-sm-6 mb-2 mb-xl-0">
			<div class="info-box shadow-sm mb-0 h-100">
				<span class="info-box-icon bg-success elevation-1"><i class="fas fa-plus-circle"></i></span>
				<div class="info-box-content">
					<span class="info-box-text text-muted text-xs text-uppercase font-weight-bold">Tambah Data</span>
					<span class="info-box-number text-lg font-weight-bold text-success"><?= number_format($stats['total_create'] ?? 0) ?></span>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-sm-6 mb-2 mb-xl-0">
			<div class="info-box shadow-sm mb-0 h-100">
				<span class="info-box-icon bg-info elevation-1"><i class="fas fa-pencil-alt"></i></span>
				<div class="info-box-content">
					<span class="info-box-text text-muted text-xs text-uppercase font-weight-bold">Ubah Data</span>
					<span class="info-box-number text-lg font-weight-bold text-info"><?= number_format($stats['total_update'] ?? 0) ?></span>
				</div>
			</div>
		</div>
		<div class="col-xl-3 col-sm-6 mb-2 mb-xl-0">
			<div class="info-box shadow-sm mb-0 h-100">
				<span class="info-box-icon bg-danger elevation-1"><i class="fas fa-trash-alt"></i></span>
				<div class="info-box-content">
					<span class="info-box-text text-muted text-xs text-uppercase font-weight-bold">Hapus Data</span>
					<span class="info-box-number text-lg font-weight-bold text-danger"><?= number_format($stats['total_delete'] ?? 0) ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Filter Card -->
	<div class="card card-outline card-primary shadow-sm mb-4">
		<div class="card-header bg-white py-3">
			<h3 class="card-title font-weight-bold text-dark m-0">
				<i class="fas fa-filter text-primary mr-2"></i>Filter & Pencarian Log
			</h3>
		</div>
		<div class="card-body">
			<form method="get" action="<?= base_url('admin/aktivitas') ?>" class="mb-0">
				<?php if ($filters['record_id'] !== ''): ?>
					<input type="hidden" name="record_id" value="<?= esc($filters['record_id'], 'attr') ?>">
				<?php endif ?>

				<div class="row">
					<div class="form-group col-md-3">
						<label class="font-weight-bold small text-muted mb-1"><i class="fas fa-search mr-1"></i>Kata Kunci / Pencarian</label>
						<input type="text" name="q" class="form-control" placeholder="Cari nama warga, user..." value="<?= esc($filters['q'], 'attr') ?>">
					</div>
					<div class="form-group col-md-2">
						<label class="font-weight-bold small text-muted mb-1"><i class="fas fa-cubes mr-1"></i>Modul</label>
						<select name="module" class="form-control">
							<option value="">Semua Modul</option>
							<?php foreach (ActivityLogModel::MODULES as $key => $label): ?>
								<option value="<?= $key ?>" <?= $filters['module'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<div class="form-group col-md-2">
						<label class="font-weight-bold small text-muted mb-1"><i class="fas fa-tag mr-1"></i>Aksi</label>
						<select name="action" class="form-control">
							<option value="">Semua Aksi</option>
							<?php foreach (ActivityLogModel::ACTIONS as $key => $act): ?>
								<option value="<?= $key ?>" <?= $filters['action'] === $key ? 'selected' : '' ?>><?= esc($act[0]) ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<div class="form-group col-md-2">
						<label class="font-weight-bold small text-muted mb-1"><i class="fas fa-user mr-1"></i>User / Operator</label>
						<select name="user_name" class="form-control">
							<option value="">Semua User</option>
							<?php foreach ($userNames as $name): ?>
								<option value="<?= esc($name, 'attr') ?>" <?= $filters['user_name'] === $name ? 'selected' : '' ?>><?= esc($name) ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<div class="form-group col-md-3">
						<label class="font-weight-bold small text-muted mb-1"><i class="far fa-calendar-alt mr-1"></i>Periode Tanggal</label>
						<div class="input-group">
							<input type="date" name="dari" class="form-control form-control-sm" title="Dari Tanggal" value="<?= esc($filters['dari'], 'attr') ?>">
							<div class="input-group-prepend input-group-append">
								<span class="input-group-text px-1 text-muted small">s/d</span>
							</div>
							<input type="date" name="sampai" class="form-control form-control-sm" title="Sampai Tanggal" value="<?= esc($filters['sampai'], 'attr') ?>">
						</div>
					</div>
				</div>

				<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top">
					<div class="text-muted small">
						<?php
							$hasActiveFilter = ($filters['q'] !== '' || $filters['module'] !== '' || $filters['action'] !== '' || $filters['user_name'] !== '' || $filters['dari'] !== '' || $filters['sampai'] !== '' || $filters['record_id'] !== '');
						?>
						<?php if ($hasActiveFilter): ?>
							<span class="badge badge-info mr-1"><i class="fas fa-info-circle mr-1"></i>Filter Aktif</span>
							<?php if ($filters['q'] !== ''): ?>
								<span class="badge badge-light border mr-1">Pencarian: "<?= esc($filters['q']) ?>"</span>
							<?php endif ?>
							<?php if ($filters['module'] !== ''): ?>
								<span class="badge badge-light border mr-1">Modul: <?= esc(ActivityLogModel::MODULES[$filters['module']] ?? $filters['module']) ?></span>
							<?php endif ?>
							<?php if ($filters['action'] !== ''): ?>
								<span class="badge badge-light border mr-1">Aksi: <?= esc(ActivityLogModel::ACTIONS[$filters['action']][0] ?? $filters['action']) ?></span>
							<?php endif ?>
							<?php if ($filters['user_name'] !== ''): ?>
								<span class="badge badge-light border mr-1">User: <?= esc($filters['user_name']) ?></span>
							<?php endif ?>
							<?php if ($filters['dari'] !== '' || $filters['sampai'] !== ''): ?>
								<span class="badge badge-light border mr-1">Periode: <?= esc($filters['dari'] ?: '...') ?> &rarr; <?= esc($filters['sampai'] ?: '...') ?></span>
							<?php endif ?>
						<?php endif ?>
					</div>
					<div>
						<button type="submit" class="btn btn-primary px-3 mr-1">
							<i class="fas fa-filter mr-1"></i> Terapkan
						</button>
						<a href="<?= base_url('admin/aktivitas') ?>" class="btn btn-light border px-3">
							<i class="fas fa-undo mr-1"></i> Reset
						</a>
					</div>
				</div>
			</form>

			<?php if ($filters['record_id'] !== ''): ?>
				<div class="alert alert-info py-2 px-3 mt-3 mb-0 d-flex justify-content-between align-items-center">
					<div>
						<i class="fas fa-history mr-2"></i> Menampilkan riwayat khusus data:
						<strong><?= esc($targetLabel ?? ('ID ' . $filters['record_id'])) ?></strong>
					</div>
					<a href="<?= base_url('admin/aktivitas') ?>" class="btn btn-xs btn-outline-primary bg-white">
						<i class="fas fa-times mr-1"></i> Tampilkan Semua Data
					</a>
				</div>
			<?php endif ?>
		</div>
	</div>

	<!-- Activity Logs Table Card -->
	<div class="card card-outline card-secondary shadow-sm mb-4">
		<div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
			<h3 class="card-title font-weight-bold text-dark m-0">
				<i class="fas fa-list-ul text-primary mr-2"></i>Daftar Riwayat Aktivitas
			</h3>
			<div class="card-tools">
				<span class="badge badge-light border text-muted px-2 py-1">
					<i class="fas fa-stream mr-1"></i> <?= count($logs) ?> entri pada halaman ini
				</span>
			</div>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover table-striped activity-table mb-0">
					<thead>
						<tr>
							<th style="width: 150px;">Waktu</th>
							<th style="width: 160px;">User</th>
							<th style="width: 110px;">Modul</th>
							<th style="width: 180px;">Data</th>
							<th style="width: 100px;" class="text-center">Aksi</th>
							<th>Rincian Perubahan</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($logs)): ?>
							<tr>
								<td colspan="6" class="text-center py-5">
									<div class="py-4">
										<i class="fas fa-history fa-3x text-muted mb-3 d-block" style="opacity: 0.4;"></i>
										<h5 class="font-weight-bold text-dark">Tidak Ada Aktivitas Ditemukan</h5>
										<p class="text-muted small mb-3">Tidak ada catatan log aktivitas yang sesuai dengan filter yang dipilih.</p>
										<a href="<?= base_url('admin/aktivitas') ?>" class="btn btn-sm btn-outline-primary">
											<i class="fas fa-undo mr-1"></i> Reset Semua Filter
										</a>
									</div>
								</td>
							</tr>
						<?php endif ?>

						<?php foreach ($logs as $log): ?>
							<?php
								[$aksiLabel, $aksiColor, $aksiIcon] = ActivityLogModel::ACTIONS[$log['action']] ?? [$log['action'], 'secondary', 'fas fa-dot-circle'];
								$rawChanges = json_decode((string) $log['changes'], true) ?: [];

								// Saring field teknis internal yang redundan
								$changes = [];
								foreach ($rawChanges as $f => $pair) {
									if (! in_array($f, $ignoredFields, true)) {
										$changes[$f] = $pair;
									}
								}
								$modIcon = ActivityLogModel::MODULE_ICONS[$log['module']] ?? 'fas fa-cube';
							?>
							<tr>
								<!-- Waktu -->
								<td>
									<div class="font-weight-bold text-dark"><?= esc(date('d/m/Y', strtotime($log['created_at']))) ?></div>
									<small class="text-muted"><i class="far fa-clock mr-1"></i><?= esc(date('H:i:s', strtotime($log['created_at']))) ?></small>
								</td>

								<!-- User & IP -->
								<td>
									<div class="d-flex align-items-center">
										<div class="user-avatar-badge mr-2">
											<i class="fas fa-user"></i>
										</div>
										<div>
											<span class="font-weight-bold text-dark d-block"><?= esc($log['user_name']) ?></span>
											<?php if (! empty($log['ip_address'])): ?>
												<small class="text-muted d-block" title="IP Address">
													<i class="fas fa-laptop mr-1"></i><?= esc($log['ip_address']) ?>
												</small>
											<?php endif ?>
										</div>
									</div>
								</td>

								<!-- Modul -->
								<td>
									<span class="badge badge-light border text-dark px-2 py-1">
										<i class="<?= esc($modIcon) ?> text-primary mr-1"></i>
										<?= esc(ActivityLogModel::MODULES[$log['module']] ?? $log['module']) ?>
									</span>
								</td>

								<!-- Data -->
								<td>
									<?php if ($log['module'] === 'warga'): ?>
										<a href="<?= base_url('admin/warga/view/' . $log['record_id']) ?>" class="font-weight-bold text-primary d-inline-block" title="Lihat profil data warga">
											<?= esc($log['record_label'] ?: '#' . $log['record_id']) ?>
										</a>
										<div class="mt-1">
											<span class="badge badge-light border text-muted small">#<?= (int) $log['record_id'] ?></span>
											<a href="<?= base_url('admin/aktivitas?module=warga&record_id=' . $log['record_id']) ?>" class="badge badge-light border ml-1 text-primary" title="Filter semua riwayat data warga ini">
												<i class="fas fa-filter mr-1"></i>Riwayat
											</a>
										</div>
									<?php else: ?>
										<span class="font-weight-bold text-dark"><?= esc($log['record_label'] ?: '#' . $log['record_id']) ?></span>
										<div class="text-muted small">#<?= (int) $log['record_id'] ?></div>
									<?php endif ?>
								</td>

								<!-- Aksi -->
								<td class="text-center">
									<span class="badge badge-<?= esc($aksiColor) ?> px-2 py-1">
										<i class="<?= esc($aksiIcon) ?> mr-1"></i><?= esc($aksiLabel) ?>
									</span>
								</td>

								<!-- Perubahan -->
								<td>
									<?php if ($log['action'] === 'update'): ?>
										<?php if (empty($changes)): ?>
											<span class="text-muted small font-italic">Pembaruan data internal</span>
										<?php else: ?>
											<div class="diff-container">
												<?php foreach ($changes as $field => $pair): ?>
													<div class="diff-item">
														<span class="diff-field-badge"><?= esc($fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field))) ?></span>
														<span class="diff-old"><del><?= $fmtValue($pair[0], $field) ?></del></span>
														<i class="fas fa-arrow-right diff-arrow"></i>
														<span class="diff-new"><?= $fmtValue($pair[1], $field) ?></span>
													</div>
												<?php endforeach ?>
											</div>
										<?php endif ?>
									<?php elseif ($log['action'] === 'create'): ?>
										<button class="btn btn-xs btn-outline-success font-weight-bold px-2 py-1" type="button" data-toggle="collapse" data-target="#collapse-<?= $log['id'] ?>">
											<i class="fas fa-list mr-1"></i> Data Awal (<?= count($changes) ?> field) <i class="fas fa-chevron-down ml-1"></i>
										</button>
										<div class="collapse mt-2" id="collapse-<?= $log['id'] ?>">
											<div class="bg-light p-2 rounded border">
												<?php foreach ($changes as $field => $pair): ?>
													<div class="diff-item">
														<span class="diff-field-badge"><?= esc($fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field))) ?></span>
														<span class="diff-new"><?= $fmtValue($pair[1], $field) ?></span>
													</div>
												<?php endforeach ?>
											</div>
										</div>
									<?php elseif ($log['action'] === 'delete'): ?>
										<button class="btn btn-xs btn-outline-danger font-weight-bold px-2 py-1" type="button" data-toggle="collapse" data-target="#collapse-<?= $log['id'] ?>">
											<i class="fas fa-trash-alt mr-1"></i> Data Terhapus (<?= count($changes) ?> field) <i class="fas fa-chevron-down ml-1"></i>
										</button>
										<div class="collapse mt-2" id="collapse-<?= $log['id'] ?>">
											<div class="bg-light p-2 rounded border">
												<?php foreach ($changes as $field => $pair): ?>
													<div class="diff-item">
														<span class="diff-field-badge"><?= esc($fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field))) ?></span>
														<span class="diff-old"><?= $fmtValue($pair[0], $field) ?></span>
													</div>
												<?php endforeach ?>
											</div>
										</div>
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Card Footer: Pagination -->
		<div class="card-footer bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center py-3">
			<div class="text-muted small mb-2 mb-md-0">
				Menampilkan <strong><?= count($logs) ?></strong> baris data pada halaman ini
			</div>
			<div>
				<?= $pager->links('default', 'bootstrap_pagination') ?>
			</div>
		</div>
	</div>
</div>
