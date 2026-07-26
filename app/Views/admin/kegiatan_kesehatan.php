<?php
	$totalPeserta = count($peserta);
	$sudahDicatat = 0;
	foreach ($peserta as $p) {
		if (kesehatan_has_data($catatan[$p->id_warga] ?? null)) {
			$sudahDicatat++;
		}
	}
	$belumDicatat = $totalPeserta - $sudahDicatat;

	// $readOnly only ever means "plain RT admin viewing a joint RW event"
	// (see Kesehatan::isReadOnlyForCaller()) - $peserta above is already
	// narrowed to just this RT, so pesertaRw (the full joint roster) is
	// used to show the RW-wide recap alongside the RT-specific one.
	if ($readOnly && isset($pesertaRw)) {
		$totalPesertaRw = count($pesertaRw);
		$sudahDicatatRw = 0;
		foreach ($pesertaRw as $p) {
			if (kesehatan_has_data($catatan[$p->id_warga] ?? null)) {
				$sudahDicatatRw++;
			}
		}
		$belumDicatatRw = $totalPesertaRw - $sudahDicatatRw;
		$namaRtSendiri  = current_rt()->nama ?? 'RT ini';
	}

	// Options for the "Filter RT" dropdown: unique RTs actually present
	// among today's participants (only meaningful in the multi-RT/RW view).
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
							<?= esc($kegiatan->nama_kegiatan) ?>
							<?php if ($readOnly): ?>
								<span class="badge badge-secondary" title="Kegiatan gabungan RW - RT hanya bisa melihat &amp; mencetak">
									<i class="fas fa-lock"></i> Baca saja (Kegiatan RW)
								</span>
							<?php endif; ?>
						</h4>
						<span class="text-muted"><?= tanggal($kegiatan->tanggal_kegiatan) ?></span>
						<?php if (!empty($kegiatan->catatan)): ?>
							<div class="text-muted small mt-1"><?= esc($kegiatan->catatan) ?></div>
						<?php endif; ?>
					</div>
					<div style="align-items:center; margin-left: auto">
						<?php
							$urlExportExcel = base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/export');
							$urlExportPdf   = base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/export/pdf');
						?>
						<div class="dropdown d-inline-block">
							<button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-toggle="dropdown">
								<i class="fas fa-file-download"></i> Export Rekap
							</button>
							<div class="dropdown-menu dropdown-menu-right" style="min-width:260px">
								<h6 class="dropdown-header"><?= $multiRt ? 'Gabungan (semua RT)' : 'Rekap' ?></h6>
								<div class="dropdown-item d-flex justify-content-between align-items-center">
									<span><?= $multiRt ? 'Rekap Gabungan' : esc($kegiatan->nama_kegiatan) ?></span>
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
						<?php if (!$readOnly): ?>
							<a href="<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
								<i class="far fa-edit"></i> Ubah Kegiatan
							</a>
						<?php endif; ?>
						<a href="<?= base_url('admin/kesehatan') ?>" class="btn btn-sm btn-light">Kembali</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-3">
		<?php if (isset($totalPesertaRw)): ?>
			<div class="col-auto d-flex">
				<div class="card card-outline card-secondary mb-0">
					<div class="card-body d-flex align-items-center py-2 px-3">
						<div class="text-muted small font-weight-bold mr-3">
							<i class="fas fa-users mr-1"></i> RW<br>(Gabungan)
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold"><?= $totalPesertaRw ?></div>
							<div class="text-muted small">Total Peserta</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-success"><?= $sudahDicatatRw ?></div>
							<div class="text-muted small">Sudah Dicatat</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-secondary"><?= $belumDicatatRw ?></div>
							<div class="text-muted small">Belum Dicatat</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-auto d-flex">
				<div class="card card-outline card-info mb-0">
					<div class="card-body d-flex align-items-center py-2 px-3">
						<div class="text-muted small font-weight-bold mr-3">
							<i class="fas fa-map-marker-alt mr-1"></i> <?= esc($namaRtSendiri) ?>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold"><?= $totalPeserta ?></div>
							<div class="text-muted small">Total Peserta</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-success"><?= $sudahDicatat ?></div>
							<div class="text-muted small">Sudah Dicatat</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-secondary"><?= $belumDicatat ?></div>
							<div class="text-muted small">Belum Dicatat</div>
						</div>
					</div>
				</div>
			</div>
		<?php else: ?>
			<div class="col-auto d-flex">
				<div class="card card-outline card-info mb-0">
					<div class="card-body d-flex align-items-center py-2 px-3">
						<div class="text-center px-3">
							<div class="h4 mb-0 font-weight-bold"><?= $totalPeserta ?></div>
							<div class="text-muted small">Total Peserta</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-success"><?= $sudahDicatat ?></div>
							<div class="text-muted small">Sudah Dicatat</div>
						</div>
						<div class="text-center px-3 border-left">
							<div class="h4 mb-0 font-weight-bold text-secondary"><?= $belumDicatat ?></div>
							<div class="text-muted small">Belum Dicatat</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
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
						<button type="button" class="btn btn-secondary mr-2 mb-1" data-toggle="modal" data-target="#modalTambahPeserta">
							<i class="fas fa-user-plus mr-1"></i> Tambah Peserta
						</button>
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
								<option value="sudah">Sudah Dicatat</option>
								<option value="ditambahkan">Ditambahkan, Belum Diisi</option>
								<option value="belum">Belum Dicatat</option>
							</select>
						</div>
					</div>
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable" id="tabelPeserta">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Nama</th>
								<?php if ($multiRt): ?><th>RT</th><?php endif; ?>
								<th>Usia</th>
								<th>Status</th>
								<th width="1">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($peserta)): ?>
								<tr>
									<td colspan="<?= $multiRt ? 6 : 5 ?>" class="text-center text-muted py-4">
										Tidak ada warga lansia (60+ tahun) yang terdaftar di scope ini. Gunakan tombol "Tambah Peserta Lain" untuk mencatat warga lain.
									</td>
								</tr>
							<?php else: ?>
								<?php foreach ($peserta as $i => $p): ?>
									<?php
										$existing = $catatan[$p->id_warga] ?? null;
										$hasData  = kesehatan_has_data($existing);
										$usia = (new DateTime($p->tanggal_lahir))->diff(new DateTime())->y;
										$statusKey = $hasData ? 'sudah' : ($existing !== null ? 'ditambahkan' : 'belum');
									?>
									<tr data-id-rt="<?= (int) $p->id_rt ?>" data-status="<?= $statusKey ?>">
										<td><?= $i + 1 ?></td>
										<td>
											<?= esc($p->nama_warga) ?>
											<?php if ($hasData): ?>
												<div class="mt-1">
													<?php foreach (kesehatan_summary_parts($existing) as $part): ?>
														<span class="badge badge-light border"><?= esc($part) ?></span>
													<?php endforeach; ?>
												</div>
												<?php if (!empty($existing->catatan)): ?>
													<div class="text-muted small font-italic">&ldquo;<?= esc($existing->catatan) ?>&rdquo;</div>
												<?php endif; ?>
												<?php
													$abnormal = array_filter(kesehatan_evaluate($existing, $p->jenis_kelamin ?? null), static fn ($f) => $f['level'] !== 'normal');
												?>
												<?php if (!empty($abnormal)): ?>
													<div class="mt-1">
														<?php foreach ($abnormal as $f): ?>
															<span class="badge badge-<?= $f['level'] === 'danger' ? 'danger' : 'warning' ?>" title="<?= esc($f['note']) ?>">
																<i class="fas fa-exclamation-triangle"></i> <?= esc($f['label']) ?>: <?= esc($f['note']) ?>
															</span>
														<?php endforeach; ?>
													</div>
												<?php else: ?>
													<div class="text-success small mt-1"><i class="fas fa-check-circle"></i> Semua nilai dalam batas normal</div>
												<?php endif; ?>
											<?php endif; ?>
										</td>
										<?php if ($multiRt): ?><td><?= esc($p->nama_rt ?? '-') ?></td><?php endif; ?>
										<td><?= $usia ?> th</td>
										<td>
											<?php if ($hasData): ?>
												<span class="badge badge-success">Sudah dicatat</span>
											<?php elseif ($existing !== null): ?>
												<span class="badge badge-info">Ditambahkan, belum diisi</span>
											<?php else: ?>
												<span class="badge badge-secondary">Belum dicatat</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if (!$readOnly): ?>
												<button type="button" class="btn btn-sm btn-outline-primary btn-isi-data"
													data-id-warga="<?= $p->id_warga ?>"
													data-nama="<?= esc($p->nama_warga) ?>"
													data-id-catatan="<?= esc($existing->id_catatan ?? '') ?>"
													data-tensi-sistol="<?= esc($existing->tensi_sistol ?? '') ?>"
													data-tensi-diastol="<?= esc($existing->tensi_diastol ?? '') ?>"
													data-berat-badan="<?= esc($existing->berat_badan ?? '') ?>"
													data-tinggi-badan="<?= esc($existing->tinggi_badan ?? '') ?>"
													data-lingkar-perut="<?= esc($existing->lingkar_perut ?? '') ?>"
													data-gula-darah="<?= esc($existing->gula_darah ?? '') ?>"
													data-gula-darah-ket="<?= esc($existing->gula_darah_ket ?? '') ?>"
													data-kolesterol="<?= esc($existing->kolesterol ?? '') ?>"
													data-asam-urat="<?= esc($existing->asam_urat ?? '') ?>"
													data-catatan="<?= esc($existing->catatan ?? '') ?>">
													<i class="fas fa-notes-medical"></i> Isi Data
												</button>
											<?php endif; ?>
											<a href="<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/cetak/' . $p->id_warga) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak/download PDF catatan kesehatan">
												<i class="fas fa-file-pdf"></i>
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

<div class="modal fade" id="modalIsiData" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalIsiDataLabel">Isi Data Kesehatan</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<?php echo form_open('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/simpan') ?>
				<div class="modal-body">
					<input type="hidden" name="id_warga" id="modalIdWarga" value="">
					<div class="form-row">
						<div class="col-md-2 form-group">
							<label>Berat Badan (BB)</label>
							<input type="number" step="0.1" name="berat_badan" id="modalBeratBadan" class="form-control" placeholder="kg">
						</div>
						<div class="col-md-3 form-group">
							<label>Tinggi Badan (TB)</label>
							<input type="number" step="0.1" name="tinggi_badan" id="modalTinggiBadan" class="form-control" placeholder="cm">
						</div>
						<div class="col-md-3 form-group">
							<label>Lingkar Perut (LP)</label>
							<input type="number" step="0.1" name="lingkar_perut" id="modalLingkarPerut" class="form-control" placeholder="cm">
						</div>
						<div class="col-md-2 form-group">
							<label>Tensi Sistol</label>
							<input type="number" name="tensi_sistol" id="modalTensiSistol" class="form-control" placeholder="mmHg">
						</div>
						<div class="col-md-2 form-group">
							<label>Tensi Diastol</label>
							<input type="number" name="tensi_diastol" id="modalTensiDiastol" class="form-control" placeholder="mmHg">
						</div>
					</div>
					<div class="form-row">
						<div class="col-md-2 form-group">
							<label>Gula Darah (GD)</label>
							<input type="number" step="0.1" name="gula_darah" id="modalGulaDarah" class="form-control" placeholder="mg/dL">
						</div>
						<div class="col-md-3 form-group">
							<label>Keterangan Gula Darah</label>
							<select name="gula_darah_ket" id="modalGulaDarahKet" class="form-control">
								<option value="">-</option>
								<option value="sewaktu" selected>Sewaktu</option>
								<option value="puasa">Puasa</option>
							</select>
						</div>
						<div class="col-md-2 form-group">
							<label>Kolesterol (KOL)</label>
							<input type="number" step="0.1" name="kolesterol" id="modalKolesterol" class="form-control" placeholder="mg/dL">
						</div>
						<div class="col-md-2 form-group">
							<label>Asam Urat (AU)</label>
							<input type="number" step="0.1" name="asam_urat" id="modalAsamUrat" class="form-control" placeholder="mg/dL">
						</div>
						<div class="col-md-3 form-group">
							<label>Catatan</label>
							<input type="text" name="catatan" id="modalCatatan" class="form-control" placeholder="Keluhan/catatan lain">
						</div>
					</div>
				</div>
				<div class="modal-footer justify-content-between">
					<div>
						<a href="#" id="modalLinkRiwayat" class="btn btn-outline-secondary btn-sm">
							<i class="fas fa-chart-line"></i> Lihat Riwayat
						</a>
						<a href="#" id="modalLinkHapus" class="btn btn-outline-danger btn-sm" style="display:none" onclick="return confirm('Hapus data ini dari kegiatan?')">
							<i class="fas fa-trash-alt"></i> Hapus
						</a>
					</div>
					<button type="submit" class="btn btn-primary">Simpan</button>
				</div>
			<?php echo form_close() ?>
		</div>
	</div>
</div>

<div class="modal fade" id="modalTambahPeserta" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Tambah Peserta Lain</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="table-responsive">
				<table class="table table-bordered table-striped" id="tabelSemuaWarga">
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
						<?php foreach ($semuaWarga as $i => $w): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($w->nama_warga) ?></td>
								<td><?= esc($w->nik) ?></td>
								<?php if ($multiRt): ?><td><?= esc($w->nama_rt ?? '-') ?></td><?php endif; ?>
								<td><?= (new DateTime($w->tanggal_lahir))->diff(new DateTime())->y ?> th</td>
								<td>
									<?php if (in_array((int) $w->id_warga, $pesertaIds, true)): ?>
										<span class="badge badge-secondary">Sudah di kegiatan ini</span>
									<?php else: ?>
										<?php echo form_open('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/tambah-peserta') ?>
											<input type="hidden" name="id_warga" value="<?= $w->id_warga ?>">
											<button type="submit" class="btn btn-sm btn-primary">
												<i class="fas fa-plus mr-1"></i> Tambahkan
											</button>
										<?php echo form_close() ?>
									<?php endif; ?>
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

<div class="modal fade" id="modalTambahWargaBaru" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Tambah Warga Belum Terdaftar</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<?php echo form_open('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/tambah-warga-baru') ?>
				<div class="modal-body">
					<p class="text-muted small">Untuk warga yang datang periksa tapi belum ada di data warga. Data lengkapnya (NIK, alamat, dll) bisa dilengkapi belakangan lewat menu Warga.</p>
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
					<button type="submit" class="btn btn-primary">Tambahkan &amp; Isi Data</button>
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
				<p class="text-muted">Kartu yang baru ditempel belum terhubung ke data warga manapun. Cari dan pilih warga pemilik kartu ini untuk mendaftarkannya (sekali saja) &mdash; scan berikutnya akan langsung otomatis.</p>
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
						<?php foreach ($semuaWarga as $i => $w): ?>
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

<?php echo form_open('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/daftar-rfid', ['id' => 'formDaftarRfid', 'class' => 'd-none']) ?>
	<input type="hidden" name="kode_rfid" id="daftarRfidKode">
	<input type="hidden" name="id_warga" id="daftarRfidIdWarga">
<?php echo form_close() ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
	// --- Filter RT / Status on the peserta table ---
	// Registered once, scoped to #tabelPeserta only (via settings.nTable.id)
	// so it doesn't affect the other DataTables on this page (tambah
	// peserta / daftar rfid modals). Safe to register before the table is
	// even initialized - it only runs on draw(), and footer.php's global
	// $(".datatable").DataTable() init has always finished by the time a
	// user can actually touch these dropdowns.
	jQuery.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
		if (settings.nTable.id !== 'tabelPeserta') {
			return true;
		}

		var row          = jQuery(settings.aoData[dataIndex].nTr);
		var filterRt      = jQuery('#filterRt').length ? jQuery('#filterRt').val() : '';
		var filterStatus  = jQuery('#filterStatus').val() || '';

		if (filterRt !== '' && String(row.data('idRt')) !== String(filterRt)) {
			return false;
		}

		if (filterStatus !== '' && row.data('status') !== filterStatus) {
			return false;
		}

		return true;
	});

	jQuery(document).on('change', '#filterRt, #filterStatus', function () {
		jQuery('#tabelPeserta').DataTable().draw();
	});

	var initialized = false;
	jQuery('#modalTambahPeserta').on('shown.bs.modal', function () {
		if (!initialized) {
			jQuery('#tabelSemuaWarga').DataTable({
				language: { search: '_INPUT_', searchPlaceholder: 'Cari nama/NIK...' },
			});
			initialized = true;
		} else {
			jQuery('#tabelSemuaWarga').DataTable().columns.adjust();
		}
	});

	var urlRiwayat = '<?= base_url('admin/kesehatan/warga') ?>';
	var urlHapus   = '<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/catatan') ?>';
	var urlScan    = '<?= base_url('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan . '/scan-rfid') ?>';

	function openIsiDataModal(d) {
		jQuery('#modalIsiDataLabel').text('Isi Data Kesehatan - ' + d.nama);
		jQuery('#modalIdWarga').val(d.idWarga);
		jQuery('#modalTensiSistol').val(d.tensiSistol || '');
		jQuery('#modalTensiDiastol').val(d.tensiDiastol || '');
		jQuery('#modalBeratBadan').val(d.beratBadan || '');
		jQuery('#modalTinggiBadan').val(d.tinggiBadan || '');
		jQuery('#modalLingkarPerut').val(d.lingkarPerut || '');
		jQuery('#modalGulaDarah').val(d.gulaDarah || '');
		jQuery('#modalGulaDarahKet').val(d.gulaDarahKet || '');
		jQuery('#modalKolesterol').val(d.kolesterol || '');
		jQuery('#modalAsamUrat').val(d.asamUrat || '');
		jQuery('#modalCatatan').val(d.catatan || '');
		jQuery('#modalLinkRiwayat').attr('href', urlRiwayat + '/' + d.idWarga);

		if (d.idCatatan) {
			jQuery('#modalLinkHapus').attr('href', urlHapus + '/' + d.idCatatan + '/hapus').show();
		} else {
			jQuery('#modalLinkHapus').hide();
		}

		jQuery('#modalIsiData').modal('show');
	}

	jQuery(document).on('click', '.btn-isi-data', function () {
		openIsiDataModal(this.dataset);
	});

	// --- Scan e-KTP (RFID) ---
	var scanInput   = document.getElementById('inputScanRfid');
	var scanStatus  = document.getElementById('scanRfidStatus');
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
						scanStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + res.warga.nama;
						openIsiDataModal({
							idWarga:      res.warga.idWarga,
							nama:         res.warga.nama,
							idCatatan:    res.catatan.idCatatan,
							tensiSistol:  res.catatan.tensiSistol,
							tensiDiastol: res.catatan.tensiDiastol,
							beratBadan:   res.catatan.beratBadan,
							tinggiBadan:  res.catatan.tinggiBadan,
							lingkarPerut: res.catatan.lingkarPerut,
							gulaDarah:    res.catatan.gulaDarah,
							gulaDarahKet: res.catatan.gulaDarahKet,
							kolesterol:   res.catatan.kolesterol,
							asamUrat:     res.catatan.asamUrat,
							catatan:      res.catatan.catatan
						});
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

	<?php if ($autoOpenWarga !== null): ?>
	var autoBtn = document.querySelector('.btn-isi-data[data-id-warga="<?= (int) $autoOpenWarga ?>"]');
	if (autoBtn) {
		autoBtn.click();
	}
	<?php endif; ?>
});
</script>
