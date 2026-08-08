<?php
	/**
	 * Counts for the recap cards. A resident with no presensi_kehadiran row
	 * is "belum dipresensi", which is deliberately distinct from an
	 * explicit "tidak hadir" mark.
	 */
	$hitung = static function (array $daftar) use ($kehadiran) {
		$hadir = 0;
		$tidak = 0;
		foreach ($daftar as $p) {
			$row = $kehadiran[$p->id_warga] ?? null;
			if ($row === null) {
				continue;
			}
			if ($row->status === 'hadir') {
				$hadir++;
			} else {
				$tidak++;
			}
		}

		return ['total' => count($daftar), 'hadir' => $hadir, 'tidak' => $tidak, 'belum' => count($daftar) - $hadir - $tidak];
	};

	$stat = $hitung($peserta);

	// $readOnly only ever means "plain RT admin viewing a joint RW acara"
	// (see Presensi::isReadOnlyForCaller()) - $peserta above is already
	// narrowed to just this RT, so pesertaRw (the full joint roster) is
	// used to show the RW-wide recap alongside the RT-specific one.
	if ($readOnly && isset($pesertaRw)) {
		$statRw        = $hitung($pesertaRw);
		$namaRtSendiri = current_rt()->nama ?? 'RT ini';
	}

	// Options for the "Filter RT" dropdown: unique RTs actually present
	// among the roster (only meaningful in the multi-RT/RW view).
	$rtOptions = [];
	foreach ($peserta as $p) {
		if (!isset($rtOptions[$p->id_rt])) {
			$rtOptions[$p->id_rt] = $p->nama_rt ?? ('RT ' . $p->id_rt);
		}
	}
	asort($rtOptions);
?>
<div class="container-fluid">
	<div class="row mb-3">
		<div class="col-12">
			<div class="card card-outline card-primary mb-0">
				<div class="card-body d-flex flex-wrap justify-content-between align-items-start">
					<div>
						<h4 class="mb-1">
							<?= esc($acara->nama_acara) ?>
							<?php if ($readOnly): ?>
								<span class="badge badge-secondary" title="Acara gabungan RW - RT hanya bisa melihat &amp; mencetak">
									<i class="fas fa-lock"></i> Baca saja (Acara RW)
								</span>
							<?php endif; ?>
						</h4>
						<span class="text-muted"><?= tanggal($acara->tanggal_acara) ?></span>
						<?php if (!empty($acara->tempat)): ?>
							<span class="text-muted">&middot; <i class="fas fa-map-marker-alt"></i> <?= esc($acara->tempat) ?></span>
						<?php endif; ?>
						<?php if (!empty($acara->catatan)): ?>
							<div class="text-muted small mt-1"><?= esc($acara->catatan) ?></div>
						<?php endif; ?>
					</div>
					<div style="align-items:center; margin-left: auto">
						<?php
							$urlExportExcel = base_url('admin/presensi/acara/' . $acara->id_acara . '/export');
							$urlExportPdf   = base_url('admin/presensi/acara/' . $acara->id_acara . '/export/pdf');
						?>
						<?php if (can_export()): ?>
						<div class="dropdown d-inline-block">
							<button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-toggle="dropdown">
								<i class="fas fa-file-download"></i> Export Presensi
							</button>
							<div class="dropdown-menu dropdown-menu-right" style="min-width:260px">
								<h6 class="dropdown-header"><?= $multiRt ? 'Gabungan (semua RT)' : 'Daftar Hadir' ?></h6>
								<div class="dropdown-item d-flex justify-content-between align-items-center">
									<span><?= $multiRt ? 'Daftar Hadir Gabungan' : esc($acara->nama_acara) ?></span>
									<span>
										<a href="<?= $urlExportExcel ?>" class="text-success mr-2" title="Download Excel"><i class="fas fa-file-excel"></i></a>
										<a href="<?= $urlExportPdf ?>" target="_blank" class="text-danger" title="Cetak/simpan PDF"><i class="fas fa-file-pdf"></i></a>
									</span>
								</div>
								<?php if ($multiRt): ?>
									<div class="dropdown-divider"></div>
									<h6 class="dropdown-header">Per RT</h6>
									<?php foreach ($rtOptions as $idRt => $namaRt): ?>
										<div class="dropdown-item d-flex justify-content-between align-items-center">
											<span><?= esc($namaRt) ?></span>
											<span>
												<a href="<?= $urlExportExcel . '?rt=' . $idRt ?>" class="text-success mr-2" title="Excel - <?= esc($namaRt) ?>"><i class="fas fa-file-excel"></i></a>
												<a href="<?= $urlExportPdf . '?rt=' . $idRt ?>" target="_blank" class="text-danger" title="PDF - <?= esc($namaRt) ?>"><i class="fas fa-file-pdf"></i></a>
											</span>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
						<?php endif; ?>
						<?php if (!$readOnly): ?>
							<a href="<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
								<i class="far fa-edit"></i> Ubah Acara
							</a>
						<?php endif; ?>
						<a href="<?= base_url('admin/presensi') ?>" class="btn btn-sm btn-light">Kembali</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-3">
		<?php if (isset($statRw)): ?>
			<div class="col-auto d-flex mb-2">
				<div class="card card-outline card-secondary mb-0">
					<div class="card-body d-flex align-items-center py-2 px-3">
						<div class="text-muted small font-weight-bold mr-3">
							<i class="fas fa-users mr-1"></i> RW<br>(Gabungan)
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold"><?= $statRw['total'] ?></div>
							<div class="text-muted small">Total Warga</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-success"><?= $statRw['hadir'] ?></div>
							<div class="text-muted small">Hadir</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-danger"><?= $statRw['tidak'] ?></div>
							<div class="text-muted small">Tidak Hadir</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-secondary"><?= $statRw['belum'] ?></div>
							<div class="text-muted small">Belum Dipresensi</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="col-auto d-flex mb-2">
			<div class="card card-outline card-info mb-0">
				<div class="card-body d-flex align-items-center py-2 px-3">
					<?php if (isset($statRw)): ?>
						<div class="text-muted small font-weight-bold mr-3">
							<i class="fas fa-map-marker-alt mr-1"></i> <?= esc($namaRtSendiri) ?>
						</div>
					<?php endif; ?>
					<div class="text-center px-3">
						<div class="h4 mb-0 font-weight-bold" id="statTotal"><?= $stat['total'] ?></div>
						<div class="text-muted small">Total Warga</div>
					</div>
					<div class="text-center px-3 border-left">
						<div class="h4 mb-0 font-weight-bold text-success" id="statHadir"><?= $stat['hadir'] ?></div>
						<div class="text-muted small">Hadir</div>
					</div>
					<div class="text-center px-3 border-left">
						<div class="h4 mb-0 font-weight-bold text-danger" id="statTidak"><?= $stat['tidak'] ?></div>
						<div class="text-muted small">Tidak Hadir</div>
					</div>
					<div class="text-center px-3 border-left">
						<div class="h4 mb-0 font-weight-bold text-secondary" id="statBelum"><?= $stat['belum'] ?></div>
						<div class="text-muted small">Belum Dipresensi</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php if (!$readOnly): ?>
	<div class="row mb-3">
		<div class="col-12">
			<div class="card card-outline card-success mb-0">
				<div class="card-body d-flex flex-wrap align-items-center">
					<i class="fas fa-id-card fa-2x text-success mr-3 mb-2"></i>
					<div class="mr-3 mb-2" style="min-width:240px;flex:1 1 240px">
						<label for="inputScanRfid" class="mb-1 font-weight-bold">Scan e-KTP</label>
						<input type="text" id="inputScanRfid" class="form-control" placeholder="Tempelkan e-KTP di scanner..." autocomplete="off">
					</div>
					<div id="scanRfidStatus" class="mr-3 mb-2 text-muted text-nowrap"></div>
					<div class="ml-md-auto mb-2 d-flex flex-wrap">
						<button type="button" class="btn btn-outline-secondary mb-1" data-toggle="modal" data-target="#modalTambahWargaBaru">
							<i class="fas fa-user-plus mr-1"></i> Warga Belum Terdaftar
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="row mb-3">
						<?php if ($multiRt): ?>
						<div class="col-md-3 col-6 mb-2 mb-md-0">
							<select id="filterRt" class="form-control form-control-sm">
								<option value="">Semua RT</option>
								<?php foreach ($rtOptions as $idRt => $namaRt): ?>
									<option value="<?= $idRt ?>"><?= esc($namaRt) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
						<div class="col-md-3 col-6">
							<select id="filterStatus" class="form-control form-control-sm">
								<option value="">Semua Status</option>
								<option value="hadir">Hadir</option>
								<option value="tidak">Tidak Hadir</option>
								<option value="belum">Belum Dipresensi</option>
							</select>
						</div>
					</div>
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable" id="tabelPresensi">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Nama</th>
								<th>NIK</th>
								<?php if ($multiRt): ?><th>RT</th><?php endif; ?>
								<th>Usia</th>
								<th>Status</th>
								<th width="1">Presensi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($peserta)): ?>
								<tr>
									<td colspan="<?= $multiRt ? 7 : 6 ?>" class="text-center text-muted py-4">
										Belum ada warga terdaftar di scope ini.
									</td>
								</tr>
							<?php else: ?>
								<?php foreach ($peserta as $i => $p): ?>
									<?php
										$row       = $kehadiran[$p->id_warga] ?? null;
										$statusKey = $row === null ? 'belum' : $row->status;
										$usia      = (new DateTime($p->tanggal_lahir))->diff(new DateTime())->y;
									?>
									<tr data-id-rt="<?= (int) $p->id_rt ?>" data-status="<?= esc($statusKey) ?>" id="barisWarga<?= (int) $p->id_warga ?>">
										<td><?= $i + 1 ?></td>
										<td>
											<?= esc($p->nama_warga) ?>
											<div class="small">
												<a href="<?= base_url('admin/presensi/warga/' . $p->id_warga) ?>" class="text-muted">
													<i class="fas fa-history"></i> Riwayat presensi
												</a>
											</div>
										</td>
										<td><?= esc($p->nik) ?></td>
										<?php if ($multiRt): ?><td><?= esc($p->nama_rt ?? '-') ?></td><?php endif; ?>
										<td><?= $usia ?> th</td>
										<td class="sel-status" data-id-warga="<?= (int) $p->id_warga ?>">
											<?php if ($statusKey === 'hadir'): ?>
												<span class="badge badge-success">Hadir</span>
											<?php elseif ($statusKey === 'tidak'): ?>
												<span class="badge badge-danger">Tidak hadir</span>
											<?php else: ?>
												<span class="badge badge-secondary">Belum dipresensi</span>
											<?php endif; ?>
											<?php if ($row !== null && !empty($row->waktu)): ?>
												<div class="text-muted small sel-waktu"><?= date('H:i', strtotime($row->waktu)) ?></div>
											<?php else: ?>
												<div class="text-muted small sel-waktu"></div>
											<?php endif; ?>
										</td>
										<td class="text-nowrap">
											<?php if (!$readOnly): ?>
												<div class="btn-group btn-group-sm" role="group">
													<button type="button" class="btn btn-tandai btn-<?= $statusKey === 'hadir' ? '' : 'outline-' ?>success"
														data-id-warga="<?= (int) $p->id_warga ?>" data-status="hadir">
														<i class="fas fa-check"></i> Hadir
													</button>
													<button type="button" class="btn btn-tandai btn-<?= $statusKey === 'tidak' ? '' : 'outline-' ?>danger"
														data-id-warga="<?= (int) $p->id_warga ?>" data-status="tidak">
														<i class="fas fa-times"></i> Tidak
													</button>
												</div>
												<a href="<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/hapus/' . ($row->id_presensi ?? 0)) ?>"
													class="btn btn-sm btn-link text-muted sel-batal"
													style="<?= $row === null ? 'display:none' : '' ?>"
													onclick="return confirm('Batalkan presensi warga ini?')" title="Batalkan presensi">
													<i class="fas fa-undo"></i>
												</a>
											<?php else: ?>
												<span class="text-muted small">&mdash;</span>
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

<?php if (!$readOnly): ?>
<div class="modal fade" id="modalTambahWargaBaru" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Tambah Warga Belum Terdaftar</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<?php echo form_open('admin/presensi/acara/' . $acara->id_acara . '/tambah-warga-baru') ?>
				<div class="modal-body">
					<p class="text-muted small">Untuk warga yang hadir tapi belum ada di data warga. Warga ini langsung ditandai hadir; data lengkapnya (NIK, alamat, dll) bisa dilengkapi belakangan lewat menu Warga.</p>
					<div class="form-group">
						<label>Nama Lengkap</label>
						<input type="text" name="nama_warga" class="form-control" required autocomplete="off">
					</div>
					<div class="form-row">
						<div class="col-6 form-group">
							<label>Perkiraan Usia (tahun)</label>
							<input type="number" name="usia" min="0" max="120" class="form-control" required>
						</div>
						<div class="col-6 form-group">
							<label>Jenis Kelamin</label>
							<select name="jenis_kelamin" class="form-control">
								<option value="">-</option>
								<option value="L">Laki-laki</option>
								<option value="P">Perempuan</option>
							</select>
						</div>
					</div>
					<?php if ($multiRt): ?>
						<div class="form-group">
							<label>RT</label>
							<select name="id_rt" class="form-control" required>
								<?php foreach ($rtPilihan as $rt): ?>
									<option value="<?= $rt->id_rt ?>"><?= esc($rt->nama) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary">Tambahkan &amp; Tandai Hadir</button>
				</div>
			<?php echo form_close() ?>
		</div>
	</div>
</div>

<div class="modal fade" id="modalDaftarRfid" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Kartu e-KTP Belum Dikenali</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				<p class="text-muted">Kartu yang baru ditempel belum terhubung ke data warga manapun. Cari dan pilih warga pemilik kartu ini untuk mendaftarkannya (sekali saja) &mdash; scan berikutnya akan langsung otomatis menandai hadir.</p>
				<div class="table-responsive">
				<table class="table table-bordered table-striped" id="tabelDaftarRfid">
					<thead>
						<tr>
							<th width="1">No.</th>
							<th>Nama</th>
							<th>NIK</th>
							<?php if ($multiRt): ?><th>RT</th><?php endif; ?>
							<th>Usia</th>
							<th width="1">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($peserta as $i => $w): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($w->nama_warga) ?></td>
								<td><?= esc($w->nik) ?></td>
								<?php if ($multiRt): ?><td><?= esc($w->nama_rt ?? '-') ?></td><?php endif; ?>
								<td><?= (new DateTime($w->tanggal_lahir))->diff(new DateTime())->y ?> th</td>
								<td>
									<button type="button" class="btn btn-sm btn-primary btn-daftarkan-rfid" data-id-warga="<?= $w->id_warga ?>" data-nama="<?= esc($w->nama_warga) ?>">
										<i class="fas fa-link mr-1"></i> Daftarkan
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php echo form_open('admin/presensi/acara/' . $acara->id_acara . '/daftar-rfid', ['id' => 'formDaftarRfid', 'class' => 'd-none']) ?>
	<input type="hidden" name="kode_rfid" id="daftarRfidKode">
	<input type="hidden" name="id_warga" id="daftarRfidIdWarga">
<?php echo form_close() ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
	// --- Filter RT / Status on the presensi table ---
	// Registered once, scoped to #tabelPresensi only (via settings.nTable.id)
	// so it doesn't affect the other DataTable on this page (the daftar
	// RFID modal).
	jQuery.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
		if (settings.nTable.id !== 'tabelPresensi') {
			return true;
		}

		var row          = jQuery(settings.aoData[dataIndex].nTr);
		var filterRt     = jQuery('#filterRt').length ? jQuery('#filterRt').val() : '';
		var filterStatus = jQuery('#filterStatus').val() || '';

		if (filterRt !== '' && String(row.data('idRt')) !== String(filterRt)) {
			return false;
		}

		if (filterStatus !== '' && row.data('status') !== filterStatus) {
			return false;
		}

		return true;
	});

	jQuery(document).on('change', '#filterRt, #filterStatus', function () {
		jQuery('#tabelPresensi').DataTable().draw();
	});

<?php if (!$readOnly): ?>
	var urlTandai = '<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/tandai') ?>';
	var urlHapus  = '<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/hapus') ?>';
	var urlScan   = '<?= base_url('admin/presensi/acara/' . $acara->id_acara . '/scan-rfid') ?>';
	// Config\Security::$regenerate is on, so every accepted POST invalidates
	// the previous token - each response hands back a fresh one to use for
	// the next tap.
	var csrfName  = '<?= csrf_token() ?>';
	var csrfHash  = '<?= csrf_hash() ?>';

	function hitungUlangStatistik() {
		var hadir = 0, tidak = 0, belum = 0;
		jQuery('#tabelPresensi tbody tr[data-status]').each(function () {
			var s = jQuery(this).data('status');
			if (s === 'hadir') { hadir++; } else if (s === 'tidak') { tidak++; } else { belum++; }
		});
		jQuery('#statHadir').text(hadir);
		jQuery('#statTidak').text(tidak);
		jQuery('#statBelum').text(belum);
	}

	function terapkanStatus(idWarga, status, waktu, idPresensi) {
		var baris = jQuery('#barisWarga' + idWarga);
		if (!baris.length) {
			return;
		}

		baris.attr('data-status', status).data('status', status);

		var badge = status === 'hadir'
			? '<span class="badge badge-success">Hadir</span>'
			: '<span class="badge badge-danger">Tidak hadir</span>';
		var jam = waktu ? String(waktu).substring(11, 16) : '';
		baris.find('.sel-status').html(badge + '<div class="text-muted small sel-waktu">' + jam + '</div>');

		baris.find('.btn-tandai').each(function () {
			var aktif = this.dataset.status === status;
			var warna = this.dataset.status === 'hadir' ? 'success' : 'danger';
			jQuery(this)
				.toggleClass('btn-' + warna, aktif)
				.toggleClass('btn-outline-' + warna, !aktif);
		});

		baris.find('.sel-batal').attr('href', urlHapus + '/' + idPresensi).show();

		hitungUlangStatistik();
	}

	function tandai(idWarga, status, onDone) {
		var body = new URLSearchParams();
		body.append('id_warga', idWarga);
		body.append('status', status);
		body.append(csrfName, csrfHash);

		return fetch(urlTandai, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (res) {
				if (res.csrfHash) {
					csrfHash = res.csrfHash;
				}
				if (res.status === 'ok') {
					terapkanStatus(res.idWarga, res.kehadiran, res.waktu, res.idPresensi);
				} else {
					alert(res.message || 'Gagal menyimpan presensi.');
				}
				if (onDone) { onDone(res); }
			})
			.catch(function () {
				alert('Gagal terhubung ke server. Coba lagi.');
				if (onDone) { onDone(null); }
			});
	}

	jQuery(document).on('click', '.btn-tandai', function () {
		var btn = this;
		btn.disabled = true;
		tandai(btn.dataset.idWarga, btn.dataset.status, function () {
			btn.disabled = false;
		});
	});

	// --- Scan e-KTP (RFID) ---
	var scanInput  = document.getElementById('inputScanRfid');
	var scanStatus = document.getElementById('scanRfidStatus');
	var daftarRfidInitialized = false;

	if (scanInput) {
		scanInput.focus();

		scanInput.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter') {
				return;
			}
			e.preventDefault();

			var kode = scanInput.value.trim();
			scanInput.value = '';
			if (kode === '') {
				return;
			}

			scanStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari warga...';

			fetch(urlScan + '?kode=' + encodeURIComponent(kode), {
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			})
				.then(function (res) { return res.json(); })
				.then(function (res) {
					if (res.status === 'found') {
						scanStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + res.warga.nama + ' hadir';
						terapkanStatus(res.warga.idWarga, res.presensi.kehadiran, res.presensi.waktu, res.presensi.idPresensi);
					} else if (res.status === 'not_found') {
						scanStatus.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Kartu belum dikenali';
						document.getElementById('daftarRfidKode').value = kode;
						jQuery('#modalDaftarRfid').modal('show');
					} else {
						scanStatus.innerHTML = '<i class="fas fa-times-circle text-danger"></i> ' + (res.message || 'Gagal memproses kartu');
					}
				})
				.catch(function () {
					scanStatus.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Gagal terhubung ke server';
				});
		});
	}

	jQuery('#modalDaftarRfid').on('shown.bs.modal', function () {
		if (!daftarRfidInitialized) {
			jQuery('#tabelDaftarRfid').DataTable({
				language: { search: '_INPUT_', searchPlaceholder: 'Cari nama/NIK...' },
			});
			daftarRfidInitialized = true;
		} else {
			jQuery('#tabelDaftarRfid').DataTable().columns.adjust();
		}
	});

	jQuery(document).on('click', '.btn-daftarkan-rfid', function () {
		if (!confirm('Daftarkan kartu ini untuk ' + this.dataset.nama + '?')) {
			return;
		}
		document.getElementById('daftarRfidIdWarga').value = this.dataset.idWarga;
		document.getElementById('formDaftarRfid').submit();
	});
<?php endif; ?>
});
</script>
