<?php

use App\Libraries\SuratPemohon;
use App\Models\SuratModel;

$status        = (int) $surat->status_surat;
$beda          = count(array_filter($banding, static fn ($r) => ! $r['sama']));
$rawPhone      = SuratPemohon::hasSnapshot($surat) ? $surat->no_hp : ($surat->rt_no_hp ?? '');
$cleanPhone    = SuratPemohon::normalizePhone($rawPhone);
$namaPemohon   = SuratPemohon::hasSnapshot($surat) ? $surat->nama_pemohon : $surat->rt_nama;
$nikPemohon    = SuratPemohon::hasSnapshot($surat) ? $surat->nik_pemohon : $surat->rt_nik;
$alamatPemohon = SuratPemohon::hasSnapshot($surat) ? $surat->alamat_pemohon : ($surat->rt_alamat_lengkap ?: $surat->rt_alamat);
?>
<style>
	.surat-detail-card {
		box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
		border-radius: 0.75rem;
		border: 1px solid rgba(0, 0, 0, 0.08);
		overflow: hidden;
	}
	.surat-info-box {
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 0.5rem;
	}
	.table-comparison th {
		background-color: #f8fafc;
		font-weight: 600;
		text-transform: uppercase;
		font-size: 0.78rem;
		letter-spacing: 0.5px;
		color: #475569;
		vertical-align: middle;
	}
	.table-comparison td {
		vertical-align: middle;
	}
	.border-left-divider {
		border-left: 2px solid #e2e8f0;
	}
	.alert .alert-icon {
		color: #ffffff !important;
		font-size: 1.85rem;
		line-height: 1;
		flex-shrink: 0;
	}
	.btn-back {
		background-color: #ffffff !important;
		border: 1px solid #ced4da !important;
		color: #212529 !important;
		font-weight: 600 !important;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
		transition: all 0.2s ease-in-out;
	}
	.btn-back:hover {
		background-color: #f1f3f5 !important;
		border-color: #adb5bd !important;
		color: #000000 !important;
	}
	@media (max-width: 767.98px) {
		.border-left-divider {
			border-left: none;
			border-top: 2px solid #e2e8f0;
			padding-top: 1rem;
			margin-top: 1rem;
		}
	}
</style>

<div class="container-fluid">
	<div class="row">
		<!-- Kolom Kiri: Detail Permohonan & Tabel Verifikasi -->
		<div class="col-lg-8">
			<!-- Card 1: Informasi Permohonan Surat -->
			<div class="card surat-detail-card mb-4">
				<div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
					<h3 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-file-invoice text-primary mr-2"></i> Pengajuan Surat #<?= (int) $surat->id_surat ?>
					</h3>
					<div class="card-tools d-flex align-items-center">
						<span class="text-muted small mr-3">
							<i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y, H:i', strtotime($surat->created_at)) ?> WIB
						</span>
						<?php if ($status === SuratModel::STATUS_DISETUJUI): ?>
							<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>
						<?php elseif ($status === SuratModel::STATUS_DITOLAK): ?>
							<span class="badge badge-dark px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Ditolak</span>
						<?php else: ?>
							<span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i> Menunggu</span>
						<?php endif ?>
					</div>
				</div>
				<div class="card-body">
					<!-- Highlight Maksud & Keperluan -->
					<div class="surat-info-box p-3 mb-3">
						<div class="row align-items-center">
							<div class="col-md-7">
								<div class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Permohonan Surat</div>
								<h5 class="font-weight-bold text-primary mb-1 mt-1">
									<i class="fas fa-envelope-open-text mr-2"></i><?= esc($surat->maksut) ?>
								</h5>
							</div>
							<div class="col-md-5 border-left-divider">
								<div class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Untuk Keperluan</div>
								<p class="text-dark font-weight-500 mb-0 mt-1">
									<?= esc($surat->perlu) ?>
								</p>
							</div>
						</div>
					</div>

					<!-- Berkas Lampiran -->
					<div class="mb-2">
						<div class="text-muted small text-uppercase font-weight-bold mb-2" style="letter-spacing: 0.5px;">
							<i class="fas fa-paperclip text-secondary mr-1"></i> Berkas Lampiran Pendukung
						</div>
						<?php if (empty($lampiran)): ?>
							<span class="text-muted small font-italic">- Tidak ada berkas lampiran yang dicantumkan -</span>
						<?php else: ?>
							<div class="d-flex flex-wrap">
								<?php foreach ($lampiran as $idx => $item): ?>
									<div class="mr-2 mb-2 p-2 bg-light border rounded small d-flex align-items-center">
										<span class="badge badge-primary mr-2"><?= $idx + 1 ?></span>
										<i class="fas fa-file-alt text-muted mr-1"></i>
										<span class="font-weight-bold text-dark"><?= esc($item) ?></span>
									</div>
								<?php endforeach ?>
							</div>
						<?php endif ?>
					</div>

					<?php if ($status === SuratModel::STATUS_DITOLAK && ! empty($surat->alasan_tolak)): ?>
						<div class="alert alert-danger mt-3 mb-0">
							<h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Alasan Penolakan:</h6>
							<div><?= esc($surat->alasan_tolak) ?></div>
						</div>
					<?php endif ?>
				</div>
			</div>

			<!-- Card 2: Verifikasi & Perbandingan Data Pemohon -->
			<div class="card surat-detail-card mb-4">
				<div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
					<h3 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-user-check text-success mr-2"></i> Verifikasi Data Pemohon vs Database RT
					</h3>
				</div>
				<div class="card-body">
					<!-- Alert Status Kecocokan -->
					<?php if (empty($banding)): ?>
						<div class="alert alert-secondary d-flex align-items-center mb-3">
							<i class="fas fa-info-circle alert-icon text-white mr-3"></i>
							<div>
								<strong>Pengajuan Arsip / Data Lama</strong>
								<div class="small">Pengajuan lama: tidak ada salinan data pemohon untuk dibandingkan. Yang ditampilkan adalah data warga di RT.</div>
							</div>
						</div>
					<?php elseif ($beda > 0): ?>
						<div class="alert alert-danger d-flex align-items-center mb-3">
							<i class="fas fa-exclamation-triangle alert-icon text-white mr-3"></i>
							<div>
								<strong>Perhatian: <?= $beda ?> isian pemohon tidak sama dengan data RT.</strong>
								<div class="small">Periksa sebelum menyetujui atau mencetak.</div>
							</div>
						</div>
					<?php else: ?>
						<div class="alert alert-success d-flex align-items-center mb-3">
							<i class="fas fa-check-circle alert-icon text-white mr-3"></i>
							<div>
								<strong>Semua isian pemohon sama dengan data RT.</strong>
								<div class="small">Data yang diisi pemohon cocok dengan arsip data warga RT.</div>
							</div>
						</div>
					<?php endif ?>

					<!-- Tabel Perbandingan -->
					<?php if (! empty($banding)): ?>
					<div class="table-responsive">
						<table class="table table-bordered table-hover table-comparison mb-0">
							<thead>
								<tr>
									<th style="width: 22%;">Data</th>
									<th style="width: 32%;">Diisi Pemohon</th>
									<th style="width: 32%;">Data RT</th>
									<th style="width: 14%; text-align: center;">Kesesuaian</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$fieldIcons = [
									'nama'          => 'fa-user',
									'nik'           => 'fa-id-card',
									'alamat'        => 'fa-map-marker-alt',
									'tempat_lahir'  => 'fa-city',
									'tanggal_lahir' => 'fa-calendar-alt',
									'agama'         => 'fa-praying-hands',
									'no_hp'         => 'fa-phone-alt',
								];

								foreach ($banding as $key => $row):
									$iconClass = $fieldIcons[$key] ?? 'fa-info-circle';

									// Display formatting
									$displayPemohon = $row['pemohon'];
									$displayRt      = $row['rt'];

									if ($key === 'tanggal_lahir') {
										$displayPemohon = ! empty($row['pemohon']) ? tanggal_indo($row['pemohon']) : '-';
										$displayRt      = ! empty($row['rt']) ? tanggal_indo($row['rt']) : '-';
									} elseif ($key === 'agama') {
										$displayPemohon = ! empty($row['pemohon']) ? ucwords(strtolower($row['pemohon'])) : '-';
										$displayRt      = ! empty($row['rt']) ? ucwords(strtolower($row['rt'])) : '-';
									} elseif ($key === 'no_hp') {
										$phoneNormPemohon = SuratPemohon::normalizePhone($row['pemohon']);
										$phoneNormRt      = SuratPemohon::normalizePhone($row['rt']);
										$displayPemohon   = $phoneNormPemohon !== '' ? '0' . $phoneNormPemohon : '-';
										$displayRt        = $phoneNormRt !== '' ? '0' . $phoneNormRt : '-';
									}
								?>
									<tr class="<?= $row['sama'] ? '' : 'table-danger' ?>">
										<td class="font-weight-600 text-dark">
											<i class="fas <?= esc($iconClass) ?> text-muted mr-1" style="width: 16px;"></i>
											<?= esc($row['label']) ?>
										</td>
										<td>
											<span class="font-weight-500"><?= esc($displayPemohon) ?></span>
											<?php if ($key === 'no_hp' && ! empty($phoneNormPemohon)): ?>
												<a href="https://wa.me/62<?= esc($phoneNormPemohon) ?>" target="_blank" class="btn btn-xs btn-success ml-1 py-0 px-1" title="Hubungi via WhatsApp">
													<i class="fab fa-whatsapp"></i> WA
												</a>
											<?php endif ?>
											<?php if (! $row['sama']): ?>
												<br><span class="badge badge-danger mt-1">Tidak sama dengan data RT</span>
											<?php endif ?>
										</td>
										<td class="text-muted">
											<?= esc($displayRt) ?>
										</td>
										<td class="text-center align-middle">
											<?php if ($row['sama']): ?>
												<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Cocok</span>
											<?php else: ?>
												<span class="badge badge-danger px-2 py-1"><i class="fas fa-times mr-1"></i> Beda</span>
											<?php endif ?>
										</td>
									</tr>
								<?php endforeach ?>
							</tbody>
						</table>
					</div>
					<?php endif ?>
				</div>
				<div class="card-footer bg-white d-flex align-items-center justify-content-between py-3">
					<a href="<?= base_url('admin/surat') ?>" class="btn btn-back px-3 py-2">
						<i class="fas fa-arrow-left mr-1 text-secondary"></i> Kembali ke Daftar Surat
					</a>
					<div>
						<?php if ($status === SuratModel::STATUS_DISETUJUI): ?>
							<a href="<?= base_url('admin/surat/cetak/' . $surat->id_surat) ?>" target="_blank" class="btn btn-primary font-weight-bold px-3 py-2 shadow-sm">
								<i class="fas fa-print mr-1"></i> Cetak Surat Keterangan
							</a>
						<?php elseif ($status === SuratModel::STATUS_MENUNGGU): ?>
							<a href="<?= base_url('admin/surat/cetak/' . $surat->id_surat) ?>" target="_blank" class="btn btn-outline-primary font-weight-bold px-3 py-2 shadow-sm" title="Pratinjau lembar cetak sebelum menyetujui">
								<i class="fas fa-print mr-1"></i> Pratinjau Cetak
							</a>
						<?php endif ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Kolom Kanan: Status & Keputusan RT -->
		<div class="col-lg-4">
			<?php if ($status === SuratModel::STATUS_MENUNGGU): ?>
				<!-- Card Keputusan RT (Status: Menunggu) -->
				<div class="card surat-detail-card card-warning card-outline mb-4">
					<div class="card-header bg-white py-3">
						<h3 class="card-title font-weight-bold text-dark mb-0">
							<i class="fas fa-gavel text-warning mr-2"></i> Keputusan RT
						</h3>
					</div>
					<div class="card-body">
						<div class="text-center mb-3">
							<span class="badge badge-warning p-2 w-100 text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">
								<i class="fas fa-clock mr-1"></i> Menunggu Persetujuan
							</span>
						</div>
						<p class="text-muted small mb-3">
							Periksa keabsahan dan kesesuaian data pemohon di sebelah kiri sebelum memberikan keputusan.
						</p>

						<?= form_open('admin/surat/setuju/' . $surat->id_surat, ['onsubmit' => "return confirm('Apakah Anda yakin akan menyetujui pengajuan surat ini?')"]) ?>
							<button type="submit" class="btn btn-success btn-block btn-lg shadow-sm font-weight-bold mb-3">
								<i class="fas fa-check-circle mr-2"></i> Setujui Pengajuan
							</button>
						<?= form_close() ?>

						<div class="position-relative text-center my-3">
							<hr>
							<span class="position-absolute bg-white px-2 text-muted small font-weight-bold" style="top: -10px; left: 50%; transform: translateX(-50%);">
								ATAU TOLAK
							</span>
						</div>

						<?= form_open('admin/surat/tolak/' . $surat->id_surat, ['onsubmit' => "return confirm('Apakah Anda yakin ingin menolak pengajuan surat ini?')"]) ?>
							<div class="form-group mb-2">
								<label for="alasan_tolak" class="font-weight-bold small text-dark">
									Alasan Penolakan <span class="text-danger">*</span>
								</label>
								<textarea id="alasan_tolak" name="alasan_tolak" class="form-control" rows="3" maxlength="255" placeholder="Contoh: Berkas persyaratan belum lengkap..." required></textarea>
								<small class="form-text text-muted">Maksimal 255 karakter</small>
							</div>
							<button type="submit" class="btn btn-outline-danger btn-block">
								<i class="fas fa-times-circle mr-1"></i> Tolak Pengajuan
							</button>
						<?= form_close() ?>
					</div>
				</div>

			<?php elseif ($status === SuratModel::STATUS_DISETUJUI): ?>
				<!-- Card Status: Disetujui -->
				<div class="card surat-detail-card card-success card-outline mb-4">
					<div class="card-header bg-white py-3">
						<h3 class="card-title font-weight-bold text-success mb-0">
							<i class="fas fa-check-circle mr-2"></i> Status Pengajuan
						</h3>
					</div>
					<div class="card-body">
						<div class="text-center mb-3">
							<span class="badge badge-success p-2 w-100 text-uppercase font-weight-bold" style="font-size: 0.95rem; letter-spacing: 0.5px;">
								<i class="fas fa-check-double mr-1"></i> Disetujui
							</span>
						</div>
						<div class="bg-light p-2 rounded small text-muted mb-3 border">
							<i class="far fa-clock mr-1"></i> Waktu Persetujuan:<br>
							<strong class="text-dark">
								<?= ! empty($surat->timestamp) && $surat->timestamp !== '0000-00-00 00:00:00' ? date('d M Y, H:i', strtotime($surat->timestamp)) . ' WIB' : date('d M Y, H:i', strtotime($surat->created_at)) . ' WIB' ?>
							</strong>
						</div>

						<a href="<?= base_url('admin/surat/cetak/' . $surat->id_surat) ?>" target="_blank" class="btn btn-primary btn-block btn-lg shadow-sm mb-2">
							<i class="fas fa-print mr-2"></i> Cetak Surat Keterangan
						</a>

						<?php if ($cleanPhone !== ''):
							$waMsg = urlencode("Halo Bpk/Ibu " . $namaPemohon . ", pengajuan surat keterangan (" . $surat->maksut . ") Anda telah disetujui oleh pengurus RT. Silakan mengambil lembar surat keterangan.");
						?>
							<a href="https://wa.me/62<?= esc($cleanPhone) ?>?text=<?= $waMsg ?>" target="_blank" class="btn btn-outline-success btn-block">
								<i class="fab fa-whatsapp mr-1"></i> Kabari Pemohon via WA
							</a>
						<?php endif ?>
					</div>
				</div>

			<?php elseif ($status === SuratModel::STATUS_DITOLAK): ?>
				<!-- Card Status: Ditolak -->
				<div class="card surat-detail-card card-danger card-outline mb-4">
					<div class="card-header bg-white py-3">
						<h3 class="card-title font-weight-bold text-danger mb-0">
							<i class="fas fa-times-circle mr-2"></i> Status Pengajuan
						</h3>
					</div>
					<div class="card-body">
						<div class="text-center mb-3">
							<span class="badge badge-dark p-2 w-100 text-uppercase font-weight-bold" style="font-size: 0.95rem; letter-spacing: 0.5px;">
								<i class="fas fa-ban mr-1"></i> Ditolak
							</span>
						</div>
						<div class="bg-light p-2 rounded small text-muted mb-3 border">
							<i class="far fa-clock mr-1"></i> Waktu Penolakan:<br>
							<strong class="text-dark">
								<?= ! empty($surat->timestamp) && $surat->timestamp !== '0000-00-00 00:00:00' ? date('d M Y, H:i', strtotime($surat->timestamp)) . ' WIB' : date('d M Y, H:i', strtotime($surat->created_at)) . ' WIB' ?>
							</strong>
						</div>

						<?php if (! empty($surat->alasan_tolak)): ?>
							<div class="callout callout-danger bg-light p-3 rounded mb-3">
								<h6 class="font-weight-bold text-danger mb-1"><i class="fas fa-info-circle mr-1"></i> Alasan Penolakan:</h6>
								<p class="mb-0 text-dark small"><?= esc($surat->alasan_tolak) ?></p>
							</div>
						<?php endif ?>

						<?php if ($cleanPhone !== ''):
							$waMsgTolak = urlencode("Halo Bpk/Ibu " . $namaPemohon . ", mohon maaf pengajuan surat (" . $surat->maksut . ") belum dapat disetujui dengan alasan: " . $surat->alasan_tolak);
						?>
							<a href="https://wa.me/62<?= esc($cleanPhone) ?>?text=<?= $waMsgTolak ?>" target="_blank" class="btn btn-outline-success btn-block">
								<i class="fab fa-whatsapp mr-1"></i> Hubungi Pemohon via WA
							</a>
						<?php endif ?>
					</div>
				</div>
			<?php endif ?>

			<!-- Card 2: Profil Singkat Pemohon -->
			<div class="card surat-detail-card card-light card-outline">
				<div class="card-header bg-white py-3">
					<h3 class="card-title font-weight-bold text-muted mb-0 small text-uppercase" style="letter-spacing: 0.5px;">
						<i class="fas fa-id-card text-primary mr-2"></i> Profil Warga Terkait
					</h3>
				</div>
				<div class="card-body">
					<div class="d-flex align-items-center mb-3">
						<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 44px; height: 44px; font-weight: bold; font-size: 1.1rem; flex-shrink: 0;">
							<i class="fas fa-user"></i>
						</div>
						<div class="overflow-hidden">
							<h6 class="font-weight-bold text-dark mb-0 text-truncate"><?= esc($namaPemohon) ?></h6>
							<small class="text-muted d-block text-truncate">NIK: <?= esc($nikPemohon) ?></small>
						</div>
					</div>

					<ul class="list-unstyled small mb-3">
						<li class="mb-2 d-flex">
							<i class="fas fa-map-marker-alt text-muted mr-2 mt-1" style="width: 14px;"></i>
							<span><?= esc($alamatPemohon) ?></span>
						</li>
						<?php if ($cleanPhone !== ''): ?>
							<li class="mb-2 d-flex align-items-center">
								<i class="fas fa-phone-alt text-muted mr-2" style="width: 14px;"></i>
								<span>+62 <?= esc($cleanPhone) ?></span>
								<a href="https://wa.me/62<?= esc($cleanPhone) ?>" target="_blank" class="btn btn-xs btn-success ml-2 py-0 px-2">
									<i class="fab fa-whatsapp"></i> Chat
								</a>
							</li>
						<?php endif ?>
					</ul>

					<a href="<?= base_url('admin/warga/view/' . $surat->id_warga) ?>" target="_blank" class="btn btn-sm btn-default border text-dark font-weight-bold btn-block shadow-sm">
						<i class="fas fa-external-link-alt mr-1 text-primary"></i> Buka Data Master Warga
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
