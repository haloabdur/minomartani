<div class="container-fluid">
	<!-- Card Silsilah Pohon Keluarga -->
	<div class="card card-outline card-primary shadow-sm mb-4" id="cardFamilyTree">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h3 class="card-title font-weight-bold text-dark mb-0">
				<i class="fas fa-sitemap text-primary mr-2"></i> Silsilah Pohon Keluarga
				<span class="badge badge-light border ml-2 font-weight-normal text-muted" id="treeBadgeCount">
					<?= count($familyTree['nodes'] ?? []) ?> Anggota Terhubung
				</span>
			</h3>
			<div class="card-tools ml-auto d-flex align-items-center">
				<div class="btn-group btn-group-sm mr-2" role="group" aria-label="Tree Controls">
					<button type="button" class="btn btn-default" id="btnTreeZoomIn" title="Perbesar (Zoom In)">
						<i class="fas fa-search-plus"></i>
					</button>
					<button type="button" class="btn btn-default" id="btnTreeZoomOut" title="Perkecil (Zoom Out)">
						<i class="fas fa-search-minus"></i>
					</button>
					<button type="button" class="btn btn-default" id="btnTreeReset" title="Pusatkan / Reset Posisi">
						<i class="fas fa-crosshairs"></i>
					</button>
				</div>
				<button type="button" class="btn btn-sm btn-outline-success mr-2" id="btnTreeDownload" title="Unduh Gambar Silsilah (PNG)">
					<i class="fas fa-camera mr-1"></i> Unduh PNG
				</button>
				<button type="button" class="btn btn-tool" data-card-widget="maximize" title="Layar Penuh">
					<i class="fas fa-expand"></i>
				</button>
				<button type="button" class="btn btn-tool" data-card-widget="collapse" title="Sembunyikan / Buka">
					<i class="fas fa-minus"></i>
				</button>
			</div>
		</div>
		<div class="card-body p-0 position-relative" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
			<!-- Viewport Canvas -->
			<div id="treeViewport" class="tree-viewport">
				<svg id="treeSvg" class="tree-svg">
					<g id="treeSvgLayer"></g>
				</svg>
				<div id="treeContainer" class="tree-container">
					<!-- Nodes & Generations will be rendered here dynamically -->
				</div>
				<!-- Empty fallback if no nodes -->
				<div id="treeEmptyState" class="tree-empty-state d-none">
					<i class="fas fa-users fa-3x text-muted mb-2"></i>
					<p class="text-muted font-weight-bold mb-0">Belum ada data silsilah keluarga yang terhubung</p>
				</div>
			</div>

			<!-- Canvas Legend & Navigation Guide -->
			<div class="tree-legend px-3 py-2 bg-white border-top d-flex flex-wrap align-items-center justify-content-between small text-muted">
				<div class="d-flex align-items-center flex-wrap mr-3">
					<span class="mr-3 font-weight-bold text-dark"><i class="fas fa-info-circle text-primary mr-1"></i> Panduan:</span>
					<span class="mr-3"><i class="fas fa-mouse text-secondary mr-1"></i> Drag untuk geser</span>
					<span class="mr-3"><i class="fas fa-search text-secondary mr-1"></i> <strong>Ctrl + Scroll</strong> untuk zoom</span>
					<span><i class="fas fa-hand-pointer text-secondary mr-1"></i> Klik kartu untuk detail warga</span>
				</div>
				<div class="d-flex align-items-center flex-wrap mt-1 mt-md-0">
					<span class="badge badge-pill badge-primary mr-2 px-2 py-1"><i class="fas fa-star mr-1"></i> Warga Ini</span>
					<span class="badge badge-pill mr-2 px-2 py-1" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;"><i class="fas fa-mars mr-1"></i> Laki-laki</span>
					<span class="badge badge-pill mr-2 px-2 py-1" style="background:#fce7f3; color:#be185d; border:1px solid #fbcfe8;"><i class="fas fa-venus mr-1"></i> Perempuan</span>
					<span class="badge badge-pill badge-secondary px-2 py-1 mr-2"><i class="fas fa-globe-americas mr-1"></i> Luar RT</span>
					<span class="badge badge-pill badge-dark px-2 py-1"><i class="fas fa-ribbon mr-1"></i> Almarhum</span>
				</div>
			</div>
		</div>
	</div>

	<?php
	// Resolusi Data Tambahan untuk Tampilan Bersih
	$ayahText = '-';
	$ayahUrl  = null;
	if (!empty($warga->ayah)) {
		if (is_numeric($warga->ayah)) {
			$db = \Config\Database::connect();
			$parentAyah = $db->table('warga')->where('id_warga', $warga->ayah)->get()->getRow();
			if ($parentAyah) {
				$ayahText = $parentAyah->nama_warga;
				$ayahUrl  = base_url('admin/warga/view/' . $warga->ayah);
			} else {
				$ayahText = 'Warga ID: ' . $warga->ayah;
			}
		} else {
			$ayahText = $warga->ayah;
		}
	}

	$ibuText = '-';
	$ibuUrl  = null;
	if (!empty($warga->ibu)) {
		if (is_numeric($warga->ibu)) {
			$db = \Config\Database::connect();
			$parentIbu = $db->table('warga')->where('id_warga', $warga->ibu)->get()->getRow();
			if ($parentIbu) {
				$ibuText = $parentIbu->nama_warga;
				$ibuUrl  = base_url('admin/warga/view/' . $warga->ibu);
			} else {
				$ibuText = 'Warga ID: ' . $warga->ibu;
			}
		} else {
			$ibuText = $warga->ibu;
		}
	}

	$umurText = '';
	if (!empty($warga->tanggal_lahir)) {
		try {
			$bDate = new \DateTime($warga->tanggal_lahir);
			$today = new \DateTime('today');
			$umurText = ' (' . $bDate->diff($today)->y . ' tahun)';
		} catch (\Exception $e) {
			$umurText = '';
		}
	}

	$statusKawinText = 'Belum Kawin';
	switch ((int) $warga->status_kawin) {
		case 1:
			$statusKawinText = 'Kawin';
			break;
		case 2:
			$statusKawinText = 'Cerai Hidup';
			break;
		case 3:
			$statusKawinText = 'Cerai Mati';
			break;
		default:
			$statusKawinText = 'Belum Kawin';
			break;
	}
	?>

	<div class="row">
		<!-- Kolom Kiri -->
		<div class="col-lg-6">
			<!-- 1. Identitas Pribadi -->
			<div class="card card-outline card-primary shadow-sm mb-4">
				<div class="card-header bg-white">
					<h5 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-id-card text-primary mr-2"></i> Identitas Pribadi
					</h5>
					<div class="card-tools">
						<span class="badge badge-light border text-muted px-2 py-1">Kependudukan</span>
					</div>
				</div>
				<div class="card-body p-0">
					<table class="table table-sm table-striped mb-0 table-detail-warga">
						<tbody>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2" style="width: 36%;">Nama Lengkap</td>
								<td class="font-weight-bold text-dark py-2"><?= esc($warga->nama_warga) ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">No. KK</td>
								<td class="font-weight-bold text-dark py-2">
									<code class="px-2 py-1 bg-light border rounded"><?= esc($warga->no_kk) ?></code>
									<button type="button" class="btn btn-xs btn-default border ml-1 btn-copy" onclick="copyText('<?= esc($warga->no_kk) ?>', this)" title="Salin No. KK">
										<i class="far fa-copy mr-1"></i>Salin
									</button>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">NIK</td>
								<td class="font-weight-bold text-dark py-2">
									<code class="px-2 py-1 bg-light border rounded"><?= esc($warga->nik) ?></code>
									<button type="button" class="btn btn-xs btn-default border ml-1 btn-copy" onclick="copyText('<?= esc($warga->nik) ?>', this)" title="Salin NIK">
										<i class="far fa-copy mr-1"></i>Salin
									</button>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Jenis Kelamin</td>
								<td class="py-2">
									<?php if ($warga->jenis_kelamin === 'L') : ?>
										<span class="badge badge-pill px-2 py-1" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">
											<i class="fas fa-mars mr-1"></i> Laki-Laki
										</span>
									<?php else : ?>
										<span class="badge badge-pill px-2 py-1" style="background:#fce7f3; color:#be185d; border:1px solid #fbcfe8;">
											<i class="fas fa-venus mr-1"></i> Perempuan
										</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">TTL</td>
								<td class="py-2 font-weight-600 text-dark">
									<?= esc($warga->tempat_lahir) ?>, <?= !empty($warga->tanggal_lahir) ? date('d-m-Y', strtotime($warga->tanggal_lahir)) : '-' ?>
									<span class="text-muted font-weight-normal"><?= $umurText ?></span>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Gol. Darah</td>
								<td class="py-2">
									<span class="badge badge-secondary px-2 py-1"><?= !empty($warga->gol_darah) ? esc($warga->gol_darah) : 'Tidak Tercatat' ?></span>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Agama</td>
								<td class="py-2 text-dark font-weight-600"><?= esc(ucwords($warga->agama ?? '-')) ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Alamat RT</td>
								<td class="py-2 text-dark font-weight-600"><?= esc($warga->alamat ?? '-') ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Alamat Lengkap</td>
								<td class="py-2 text-dark"><?= esc($warga->alamat_lengkap ?: '-') ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- 2. Pendidikan & Pekerjaan -->
			<div class="card card-outline card-success shadow-sm mb-4">
				<div class="card-header bg-white">
					<h5 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-briefcase text-success mr-2"></i> Pendidikan & Pekerjaan
					</h5>
					<div class="card-tools">
						<span class="badge badge-light border text-muted px-2 py-1">Sosial & Ekonomi</span>
					</div>
				</div>
				<div class="card-body p-0">
					<table class="table table-sm table-striped mb-0 table-detail-warga">
						<tbody>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2" style="width: 36%;">Pendidikan</td>
								<td class="font-weight-bold text-dark py-2"><?= esc($warga->pendidikan ?: '-') ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Pekerjaan</td>
								<td class="font-weight-bold text-dark py-2"><?= esc($pekerjaan->nama_pekerjaan ?? '-') ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Sumber Air</td>
								<td class="py-2 text-dark font-weight-600"><?= esc($warga->sumber_air ?: '-') ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Kolom Kanan -->
		<div class="col-lg-6">
			<!-- 3. Hubungan Keluarga -->
			<div class="card card-outline card-warning shadow-sm mb-4">
				<div class="card-header bg-white">
					<h5 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-users text-warning mr-2"></i> Hubungan Keluarga
					</h5>
					<div class="card-tools">
						<span class="badge badge-light border text-muted px-2 py-1">Silsilah</span>
					</div>
				</div>
				<div class="card-body p-0">
					<table class="table table-sm table-striped mb-0 table-detail-warga">
						<tbody>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2" style="width: 36%;">Status Keluarga</td>
								<td class="py-2 font-weight-bold text-dark">
									<span class="badge badge-primary px-2 py-1"><?= esc(ucwords($warga->status_keluarga ?? '-')) ?></span>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Status Kawin</td>
								<td class="py-2 font-weight-bold text-dark"><?= esc($statusKawinText) ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Tanggal Kawin</td>
								<td class="py-2 text-dark"><?= !empty($warga->tanggal_kawin) ? date('d-m-Y', strtotime($warga->tanggal_kawin)) : '-' ?></td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Ayah</td>
								<td class="py-2">
									<?php if ($ayahUrl) : ?>
										<a href="<?= $ayahUrl ?>" class="font-weight-bold text-primary" title="Buka detail data Ayah">
											<i class="fas fa-user-circle mr-1"></i><?= esc($ayahText) ?>
											<i class="fas fa-external-link-alt ml-1 small"></i>
										</a>
									<?php else : ?>
										<span class="text-dark font-weight-600"><?= esc($ayahText) ?></span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Ibu</td>
								<td class="py-2">
									<?php if ($ibuUrl) : ?>
										<a href="<?= $ibuUrl ?>" class="font-weight-bold text-primary" title="Buka detail data Ibu">
											<i class="fas fa-user-circle mr-1"></i><?= esc($ibuText) ?>
											<i class="fas fa-external-link-alt ml-1 small"></i>
										</a>
									<?php else : ?>
										<span class="text-dark font-weight-600"><?= esc($ibuText) ?></span>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- 4. Kontak & Status Kependudukan -->
			<div class="card card-outline card-info shadow-sm mb-4">
				<div class="card-header bg-white">
					<h5 class="card-title font-weight-bold text-dark mb-0">
						<i class="fas fa-address-book text-info mr-2"></i> Kontak & Status
					</h5>
					<div class="card-tools">
						<span class="badge badge-light border text-muted px-2 py-1">Aktivitas</span>
					</div>
				</div>
				<div class="card-body p-0">
					<table class="table table-sm table-striped mb-0 table-detail-warga">
						<tbody>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2" style="width: 36%;">No. HP / WhatsApp</td>
								<td class="py-2">
									<?php if (!empty($warga->no_hp)) : ?>
										<?php $waClean = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $warga->no_hp)); ?>
										<a target="_blank" href="https://wa.me/<?= $waClean ?>" class="btn btn-xs btn-outline-success font-weight-bold px-2 py-1">
											<i class="fab fa-whatsapp mr-1 text-success"></i> +<?= esc($warga->no_hp) ?>
										</a>
									<?php else : ?>
										<span class="text-muted">-</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Email</td>
								<td class="py-2">
									<?php if (!empty($warga->email) && $warga->email !== '-') : ?>
										<a href="mailto:<?= esc($warga->email) ?>" class="text-primary font-weight-500">
											<i class="far fa-envelope mr-1"></i> <?= esc($warga->email) ?>
										</a>
									<?php else : ?>
										<span class="text-muted">-</span>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Status Hidup</td>
								<td class="py-2">
									<?= $warga->is_hidup ? '<span class="badge badge-pill badge-primary px-2 py-1"><i class="fas fa-heartbeat mr-1"></i> Hidup</span>' : '<span class="badge badge-pill badge-dark px-2 py-1"><i class="fas fa-ribbon mr-1"></i> Meninggal</span>' ?>
								</td>
							</tr>
							<tr>
								<td class="text-muted font-weight-500 pl-3 py-2">Status Warga</td>
								<td class="py-2">
									<?= $warga->status_warga ? '<span class="badge badge-pill badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Aktif</span>' : '<span class="badge badge-pill badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Tidak Aktif</span>' ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Action Footer Buttons -->
	<div class="card card-outline card-light shadow-sm mb-4">
		<div class="card-body py-2.5">
			<div class="d-flex justify-content-between align-items-center w-100">
				<a href="<?php echo base_url('admin/warga') ?>" class="btn btn-light border my-1">
					<i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Warga
				</a>
				<div class="ml-auto">
					<?php if (auth()->user()->inGroup('superadmin', 'admin')): ?>
							<a href="<?php echo base_url('admin/aktivitas?module=warga&record_id=' . $warga->id_warga) ?>" class="btn btn-light border px-3 my-1 mr-1">
								<i class="fas fa-history mr-1"></i> Riwayat
							</a>
						<?php endif ?>
						<a href="<?php echo base_url('admin/warga/edit/' . $warga->id_warga) ?>" class="btn btn-primary px-3 my-1">
						<i class="fas fa-edit mr-1"></i> Ubah Data Warga
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function copyText(text, btn) {
		if (!text || text === '-') return;
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(showSuccess, fallbackCopy);
		} else {
			fallbackCopy();
		}

		function fallbackCopy() {
			const temp = document.createElement('input');
			temp.value = text;
			document.body.appendChild(temp);
			temp.select();
			try {
				document.execCommand('copy');
				showSuccess();
			} catch (err) {
				console.error('Gagal menyalin:', err);
			}
			document.body.removeChild(temp);
		}

		function showSuccess() {
			if (!btn) return;
			const originalHtml = btn.innerHTML;
			btn.innerHTML = '<i class="fas fa-check text-success mr-1"></i>Disalin!';
			btn.classList.add('btn-success', 'text-white');
			btn.classList.remove('btn-default');
			setTimeout(() => {
				btn.innerHTML = originalHtml;
				btn.classList.remove('btn-success', 'text-white');
				btn.classList.add('btn-default');
			}, 1400);
		}
	}
</script>

<style>
/* === Detail Warga Table & Cards Styling === */
.table-detail-warga td {
	vertical-align: middle !important;
	border-top: 1px solid #f1f5f9 !important;
	font-size: 13.5px;
}
.table-detail-warga tr:last-child td {
	border-bottom: none !important;
}
.font-weight-500 {
	font-weight: 500;
}
.font-weight-600 {
	font-weight: 600;
}
.btn-copy {
	padding: 1px 7px;
	font-size: 11px;
	border-radius: 4px;
	transition: all 0.2s ease;
}

/* === Family Tree Vector Canvas Styling === */
.tree-viewport {
	position: relative;
	width: 100%;
	height: 480px;
	overflow: hidden;
	cursor: grab;
	user-select: none;
	background-color: #f8fafc;
	background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
	background-size: 24px 24px;
	border-radius: 0 0 4px 4px;
}
.tree-viewport.grabbing {
	cursor: grabbing !important;
}
.tree-svg {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	pointer-events: none;
	z-index: 1;
	overflow: visible;
}
.tree-container {
	position: absolute;
	top: 0;
	left: 0;
	transform-origin: 0 0;
	z-index: 2;
	will-change: transform;
}
.tree-card {
	position: absolute;
	width: 220px;
	min-height: 105px;
	background: #ffffff;
	border-radius: 12px;
	border: 1px solid #e2e8f0;
	box-shadow: 0 3px 8px rgba(15, 23, 42, 0.06);
	padding: 10px 12px;
	transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
	cursor: pointer;
	user-select: none;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	gap: 6px;
}
.tree-card:hover {
	transform: translateY(-3px);
	box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
	border-color: #93c5fd;
	z-index: 10;
}
.tree-card.is-current {
	border: 2.2px solid #2563eb;
	box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.22), 0 8px 18px rgba(37, 99, 235, 0.16);
	background: linear-gradient(to bottom, #ffffff, #f0f7ff);
}
.tree-card.is-manual {
	background: #f8fafc;
	border: 1.5px dashed #94a3b8;
	cursor: default;
}
.tree-card.is-deceased {
	opacity: 0.85;
	background: #f1f5f9;
	border-color: #cbd5e1;
}
.tree-card-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 6px;
}
.tree-avatar {
	width: 32px;
	height: 32px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: 700;
	font-size: 13px;
	flex-shrink: 0;
}
.tree-avatar.male {
	background: #e0f2fe;
	color: #0284c7;
	border: 1px solid #bae6fd;
}
.tree-avatar.female {
	background: #fce7f3;
	color: #db2777;
	border: 1px solid #fbcfe8;
}
.tree-avatar.manual {
	background: #e2e8f0;
	color: #475569;
}
.tree-status-badge {
	font-size: 10px;
	padding: 2px 7px;
	border-radius: 9999px;
	font-weight: 600;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 125px;
}
.tree-name {
	font-size: 13px;
	font-weight: 700;
	color: #1e293b;
	line-height: 1.3;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
	min-height: 34px;
}
.tree-card-footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 11px;
	padding-top: 4px;
	border-top: 1px solid #f1f5f9;
}
.tree-connector-line {
	fill: none;
	stroke: #94a3b8;
	stroke-width: 2.2;
	stroke-linecap: round;
	transition: stroke 0.15s ease, stroke-width 0.15s ease;
}
.tree-connector-line.highlight {
	stroke: #2563eb;
	stroke-width: 3.5;
}
.tree-spouse-line {
	fill: none;
	stroke: #0284c7;
	stroke-width: 2.2;
	stroke-dasharray: 4, 3;
}
.tree-gen-label {
	position: absolute;
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	color: #475569;
	background: #ffffff;
	padding: 4px 12px;
	border-radius: 20px;
	border: 1px solid #cbd5e1;
	box-shadow: 0 2px 6px rgba(0,0,0,0.06);
	pointer-events: none;
	z-index: 3;
	white-space: nowrap;
}
.tree-zoom-hint {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	background: rgba(15, 23, 42, 0.85);
	color: #ffffff;
	padding: 10px 18px;
	border-radius: 8px;
	font-size: 13px;
	pointer-events: none;
	opacity: 0;
	transition: opacity 0.25s ease;
	z-index: 20;
	box-shadow: 0 4px 14px rgba(0,0,0,0.25);
}
.tree-zoom-hint.show {
	opacity: 1;
}
.tree-empty-state {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	text-align: center;
}
</style>

<script type="text/javascript">
(function() {
	const treeData = <?= json_encode($familyTree ?? ['nodes' => [], 'edges' => [], 'current_id' => 0], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

	const viewport = document.getElementById('treeViewport');
	const container = document.getElementById('treeContainer');
	const svgLayer = document.getElementById('treeSvgLayer');
	const emptyState = document.getElementById('treeEmptyState');

	if (!viewport || !container || !svgLayer) return;

	if (!treeData || !treeData.nodes || treeData.nodes.length === 0) {
		emptyState.classList.remove('d-none');
		return;
	}

	const CARD_W = 220;
	const CARD_H = 105;
	const GAP_X  = 34;
	const GAP_Y  = 110;

	// 1. Inisialisasi node graph
	const nodeMap = {};
	treeData.nodes.forEach(n => {
		nodeMap[n.id] = {
			...n,
			parents: [],
			children: [],
			spouses: [],
			rank: null,
			x: 0,
			y: 0
		};
	});

	treeData.edges.forEach(e => {
		if (e.type === 'parent') {
			if (nodeMap[e.from] && nodeMap[e.to]) {
				nodeMap[e.from].children.push(e.to);
				nodeMap[e.to].parents.push(e.from);
			}
		} else if (e.type === 'spouse') {
			if (nodeMap[e.from] && nodeMap[e.to]) {
				if (!nodeMap[e.from].spouses.includes(e.to)) nodeMap[e.from].spouses.push(e.to);
				if (!nodeMap[e.to].spouses.includes(e.from)) nodeMap[e.to].spouses.push(e.from);
			}
		}
	});

	// 2. Hitung Generation Rank (DAG ranking)
	// Node tanpa orang tua di tree diberi rank 0
	Object.values(nodeMap).forEach(n => {
		if (n.parents.length === 0) {
			n.rank = 0;
		}
	});

	// Relaksasi rank untuk koneksi orang tua -> anak dan pasangan
	for (let iter = 0; iter < 12; iter++) {
		Object.values(nodeMap).forEach(n => {
			if (n.rank !== null) {
				// Sinkronisasi pasangan (rank sama)
				n.spouses.forEach(sId => {
					const s = nodeMap[sId];
					if (s) {
						const maxR = Math.max(n.rank, s.rank !== null ? s.rank : 0);
						n.rank = maxR;
						s.rank = maxR;
					}
				});
				// Dorong anak ke rank + 1
				n.children.forEach(cId => {
					const c = nodeMap[cId];
					if (c) {
						c.rank = Math.max(c.rank !== null ? c.rank : 0, n.rank + 1);
					}
				});
			}
		});
	}

	// Fallback untuk node yang belum ter-rank
	Object.values(nodeMap).forEach(n => {
		if (n.rank === null) n.rank = 0;
	});

	// Normalisasi rank minimum ke 0
	const minRank = Math.min(...Object.values(nodeMap).map(n => n.rank));
	Object.values(nodeMap).forEach(n => {
		n.rank -= minRank;
	});

	// 3. Kelompokkan per Generasi
	const generations = {};
	Object.values(nodeMap).forEach(n => {
		if (!generations[n.rank]) generations[n.rank] = [];
		generations[n.rank].push(n);
	});

	const ranks = Object.keys(generations).map(Number).sort((a, b) => a - b);

	// Urutkan tiap generasi agar pasangan berada berdampingan
	ranks.forEach(r => {
		const list = generations[r];
		const visited = new Set();
		const sortedList = [];

		list.forEach(n => {
			if (!visited.has(n.id)) {
				visited.add(n.id);
				sortedList.push(n);
				// Jika punya pasangan di generasi yang sama, tempatkan tepat setelahnya
				n.spouses.forEach(sId => {
					const spouse = list.find(item => item.id === sId);
					if (spouse && !visited.has(spouse.id)) {
						visited.add(spouse.id);
						// Prioritaskan Laki-laki di sebelah kiri jika memungkinkan
						if (spouse.jenis_kelamin === 'L' && n.jenis_kelamin === 'P') {
							sortedList.pop();
							sortedList.push(spouse);
							sortedList.push(n);
						} else {
							sortedList.push(spouse);
						}
					}
				});
			}
		});
		generations[r] = sortedList;
	});

	// 4. Hitung Posisi (X, Y) tiap Node
	ranks.forEach(r => {
		const list = generations[r];
		const y = 40 + r * (CARD_H + GAP_Y);
		const rowWidth = list.length * CARD_W + (list.length - 1) * GAP_X;
		const startX = -rowWidth / 2;

		list.forEach((n, idx) => {
			n.x = startX + idx * (CARD_W + GAP_X);
			n.y = y;
		});
	});

	// 5. Render Label Generasi & Kartu Node ke HTML
	container.innerHTML = '';
	svgLayer.innerHTML = '';

	// Hitung batas paling kiri dari seluruh kartu agar label generasi berada di sisi kiri tanpa menutupi kartu
	let globalMinX = Infinity;
	Object.values(nodeMap).forEach(n => {
		if (n.x < globalMinX) globalMinX = n.x;
	});
	const labelX = (globalMinX === Infinity ? -180 : globalMinX - 170);

	ranks.forEach(r => {
		const list = generations[r];
		if (list.length > 0) {
			const labelDiv = document.createElement('div');
			labelDiv.className = 'tree-gen-label';
			labelDiv.style.left = labelX + 'px';
			labelDiv.style.top = (list[0].y + CARD_H / 2 - 14) + 'px';
			let genName = 'Generasi ' + (r + 1);
			if (r === 0 && ranks.length > 1) {
				genName += ' (Leluhur)';
			} else if (r === 1 && ranks.length > 2) {
				genName += ' (Inti)';
			} else if (r === ranks.length - 1 && ranks.length > 1) {
				genName += ' (Anak)';
			}
			labelDiv.innerText = genName;
			container.appendChild(labelDiv);
		}
	});

	Object.values(nodeMap).forEach(n => {
		const card = document.createElement('div');
		let cardClasses = ['tree-card'];
		if (n.is_current) cardClasses.push('is-current');
		if (n.is_manual) cardClasses.push('is-manual');
		if (parseInt(n.is_hidup) === 0) cardClasses.push('is-deceased');
		card.className = cardClasses.join(' ');
		card.id = 'card-' + n.id;
		card.style.left = n.x + 'px';
		card.style.top = n.y + 'px';
		card.title = n.nama + (n.url ? ' (Klik untuk melihat detail)' : '');

		const isMale = (n.jenis_kelamin === 'L');
		const avatarClass = n.is_manual ? 'manual' : (isMale ? 'male' : 'female');
		const genderIcon = isMale ? '<i class="fas fa-mars"></i>' : '<i class="fas fa-venus"></i>';
		const genderColor = isMale ? '#0284c7' : '#db2777';

		let statusBadgeBg = '#f1f5f9';
		let statusBadgeColor = '#475569';
		const stLower = (n.status_keluarga || '').toLowerCase();
		if (stLower.includes('kepala')) {
			statusBadgeBg = '#dcfce7';
			statusBadgeColor = '#15803d';
		} else if (stLower.includes('istri') || stLower.includes('suami')) {
			statusBadgeBg = '#ede9fe';
			statusBadgeColor = '#6d28d9';
		} else if (stLower.includes('anak')) {
			statusBadgeBg = '#e0f2fe';
			statusBadgeColor = '#0369a1';
		} else if (stLower.includes('ayah') || stLower.includes('ibu')) {
			statusBadgeBg = '#fef3c7';
			statusBadgeColor = '#b45309';
		}

		card.innerHTML = `
			<div class="tree-card-header">
				<div class="tree-avatar ${avatarClass}">
					${genderIcon}
				</div>
				<div class="d-flex align-items-center">
					${n.is_current ? '<span class="badge badge-primary mr-1 px-1.5 py-0.5"><i class="fas fa-star mr-1"></i>Ini</span>' : ''}
					<span class="tree-status-badge" style="background:${statusBadgeBg}; color:${statusBadgeColor};">
						${escapeHtml(n.status_keluarga)}
					</span>
				</div>
			</div>
			<div class="tree-name" title="${escapeHtml(n.nama)}">
				${escapeHtml(n.nama)}
			</div>
			<div class="tree-card-footer text-muted">
				<span><span style="color:${genderColor}; font-weight:600;">${isMale ? 'L' : 'P'}</span> &bull; ${parseInt(n.is_hidup) === 0 ? '<span class="text-danger font-weight-bold">Almarhum</span>' : 'Hidup'}</span>
				${n.is_manual ? '<span class="badge badge-secondary px-1">Luar RT</span>' : '<span class="text-primary font-weight-bold small"><i class="fas fa-arrow-right"></i></span>'}
			</div>
		`;

		// Navigasi ketika kartu diklik
		card.addEventListener('click', function(e) {
			if (dragDistance > 6) return; // Mencegah klik saat dragging
			if (!n.is_manual && n.url) {
				window.location.href = n.url;
			}
		});

		// Hover highlight relasi garis
		card.addEventListener('mouseenter', function() {
			document.querySelectorAll(`.line-from-${n.id}, .line-to-${n.id}`).forEach(el => {
				el.classList.add('highlight');
			});
		});
		card.addEventListener('mouseleave', function() {
			document.querySelectorAll(`.line-from-${n.id}, .line-to-${n.id}`).forEach(el => {
				el.classList.remove('highlight');
			});
		});

		container.appendChild(card);
	});

	// 6. Gambar Garis Hubungan (Spouse & Parent-Child) pada SVG
	// A. Garis Pasangan (Spouse)
	const spouseDrawn = new Set();
	treeData.edges.forEach(e => {
		if (e.type === 'spouse') {
			const n1 = nodeMap[e.from];
			const n2 = nodeMap[e.to];
			if (!n1 || !n2) return;
			const sKey = [e.from, e.to].sort().join('--');
			if (spouseDrawn.has(sKey)) return;
			spouseDrawn.add(sKey);

			const leftN = (n1.x < n2.x) ? n1 : n2;
			const rightN = (n1.x < n2.x) ? n2 : n1;

			const x1 = leftN.x + CARD_W;
			const y1 = leftN.y + CARD_H / 2;
			const x2 = rightN.x;
			const y2 = rightN.y + CARD_H / 2;

			const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
			line.setAttribute('x1', x1);
			line.setAttribute('y1', y1);
			line.setAttribute('x2', x2);
			line.setAttribute('y2', y2);
			line.setAttribute('class', `tree-spouse-line line-from-${n1.id} line-to-${n2.id}`);
			svgLayer.appendChild(line);

			// Titik/simbol cincin di tengah garis pasangan
			const midX = (x1 + x2) / 2;
			const midY = (y1 + y2) / 2;
			const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
			circle.setAttribute('cx', midX);
			circle.setAttribute('cy', midY);
			circle.setAttribute('r', 5);
			circle.setAttribute('fill', '#0284c7');
			circle.setAttribute('stroke', '#ffffff');
			circle.setAttribute('stroke-width', 2);
			svgLayer.appendChild(circle);
		}
	});

	// B. Garis Orang Tua -> Anak (Bezier Curves)
	const drawnChildEdges = new Set();
	treeData.edges.forEach(e => {
		if (e.type === 'parent') {
			const parent = nodeMap[e.from];
			const child = nodeMap[e.to];
			if (!parent || !child) return;

			// Periksa apakah anak memiliki kedua orang tua (ayah & ibu) yang berstatus pasangan di tree
			let parentX = parent.x + CARD_W / 2;
			let parentY = parent.y + CARD_H;
			let lineClass = `tree-connector-line line-from-${parent.id} line-to-${child.id}`;

			if (child.parents.length >= 2) {
				const p1 = nodeMap[child.parents[0]];
				const p2 = nodeMap[child.parents[1]];
				if (p1 && p2 && p1.spouses.includes(p2.id)) {
					const coupleKey = child.id + '--couple';
					if (drawnChildEdges.has(coupleKey)) return;
					drawnChildEdges.add(coupleKey);

					// Tarik dari titik tengah kedua orang tua
					const leftP = (p1.x < p2.x) ? p1 : p2;
					const rightP = (p1.x < p2.x) ? p2 : p1;
					parentX = (leftP.x + CARD_W + rightP.x) / 2;
					parentY = leftP.y + CARD_H;
					lineClass += ` line-from-${p1.id} line-from-${p2.id}`;
				}
			}

			const childX = child.x + CARD_W / 2;
			const childY = child.y;

			const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			const d = `M ${parentX} ${parentY} C ${parentX} ${parentY + 45}, ${childX} ${childY - 45}, ${childX} ${childY}`;
			path.setAttribute('d', d);
			path.setAttribute('class', lineClass);
			svgLayer.appendChild(path);
		}
	});

	// 7. Pan & Zoom Controller
	let panX = 0;
	let panY = 0;
	let scale = 1;
	let isDragging = false;
	let startDragX = 0;
	let startDragY = 0;
	let dragDistance = 0;

	function applyTransform() {
		const t = `translate(${panX}px, ${panY}px) scale(${scale})`;
		container.style.transform = t;
		svgLayer.setAttribute('transform', `translate(${panX}, ${panY}) scale(${scale})`);
	}

	function centerTree() {
		const allNodes = Object.values(nodeMap);
		if (allNodes.length === 0) return;

		let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
		allNodes.forEach(n => {
			minX = Math.min(minX, n.x);
			maxX = Math.max(maxX, n.x + CARD_W);
			minY = Math.min(minY, n.y);
			maxY = Math.max(maxY, n.y + CARD_H);
		});

		// Sertakan batas label generasi di sebelah kiri
		if (ranks.length > 0) {
			minX = Math.min(minX, labelX);
		}

		const contentW = maxX - minX;
		const contentH = maxY - minY;
		const viewW = viewport.clientWidth || 900;
		const viewH = viewport.clientHeight || 480;

		const scaleX = (viewW - 100) / contentW;
		const scaleY = (viewH - 100) / contentH;
		scale = Math.min(scaleX, scaleY, 1.0);
		scale = Math.max(scale, 0.55);

		panX = (viewW - contentW * scale) / 2 - minX * scale;
		panY = (viewH - contentH * scale) / 2 - minY * scale;

		applyTransform();
	}

	viewport.addEventListener('mousedown', function(e) {
		isDragging = true;
		startDragX = e.clientX - panX;
		startDragY = e.clientY - panY;
		dragDistance = 0;
		viewport.classList.add('grabbing');
	});

	window.addEventListener('mousemove', function(e) {
		if (!isDragging) return;
		const newPanX = e.clientX - startDragX;
		const newPanY = e.clientY - startDragY;
		dragDistance += Math.abs(newPanX - panX) + Math.abs(newPanY - panY);
		panX = newPanX;
		panY = newPanY;
		applyTransform();
	});

	window.addEventListener('mouseup', function() {
		if (isDragging) {
			isDragging = false;
			viewport.classList.remove('grabbing');
		}
	});

	// Zoom hanya aktif jika menekan CTRL + Scroll (biarkan scroll biasa untuk menggulir halaman)
	let hintTimeout = null;
	const zoomHint = document.createElement('div');
	zoomHint.className = 'tree-zoom-hint';
	zoomHint.innerHTML = '<i class="fas fa-keyboard mr-1"></i> Gunakan <strong>CTRL + Scroll</strong> untuk Zoom';
	viewport.appendChild(zoomHint);

	viewport.addEventListener('wheel', function(e) {
		if (e.ctrlKey || e.metaKey) {
			e.preventDefault();
			const rect = viewport.getBoundingClientRect();
			const mouseX = e.clientX - rect.left;
			const mouseY = e.clientY - rect.top;
			const zoomFactor = (e.deltaY < 0) ? 1.12 : 0.89;
			const newScale = Math.min(Math.max(scale * zoomFactor, 0.35), 2.2);

			panX = mouseX - (mouseX - panX) * (newScale / scale);
			panY = mouseY - (mouseY - panY) * (newScale / scale);
			scale = newScale;
			applyTransform();
		} else {
			// Scroll normal tanpa Ctrl: biarkan halaman bergulir ke section bawahnya secara normal
			zoomHint.classList.add('show');
			clearTimeout(hintTimeout);
			hintTimeout = setTimeout(() => {
				zoomHint.classList.remove('show');
			}, 1200);
		}
	}, { passive: false });

	document.getElementById('btnTreeZoomIn').addEventListener('click', function() {
		const newScale = Math.min(scale * 1.25, 2.2);
		const cx = viewport.clientWidth / 2;
		const cy = viewport.clientHeight / 2;
		panX = cx - (cx - panX) * (newScale / scale);
		panY = cy - (cy - panY) * (newScale / scale);
		scale = newScale;
		applyTransform();
	});

	document.getElementById('btnTreeZoomOut').addEventListener('click', function() {
		const newScale = Math.max(scale * 0.8, 0.35);
		const cx = viewport.clientWidth / 2;
		const cy = viewport.clientHeight / 2;
		panX = cx - (cx - panX) * (newScale / scale);
		panY = cy - (cy - panY) * (newScale / scale);
		scale = newScale;
		applyTransform();
	});

	document.getElementById('btnTreeReset').addEventListener('click', centerTree);

	// Inisialisasi awal
	setTimeout(centerTree, 80);
	window.addEventListener('resize', centerTree);

	// 8. Download Silsilah sebagai Gambar PNG (Resolusi Tinggi 2x)
	document.getElementById('btnTreeDownload').addEventListener('click', function() {
		const allNodes = Object.values(nodeMap);
		if (allNodes.length === 0) return;

		let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
		allNodes.forEach(n => {
			minX = Math.min(minX, n.x);
			maxX = Math.max(maxX, n.x + CARD_W);
			minY = Math.min(minY, n.y);
			maxY = Math.max(maxY, n.y + CARD_H);
		});

		if (ranks.length > 0) {
			minX = Math.min(minX, labelX);
		}

		const padX = 70;
		const padY = 120;
		const width = (maxX - minX) + padX * 2;
		const height = (maxY - minY) + padY * 2;

		const canvas = document.createElement('canvas');
		const scaleFactor = 2; // Retina 2x
		canvas.width = width * scaleFactor;
		canvas.height = height * scaleFactor;
		const ctx = canvas.getContext('2d');
		ctx.scale(scaleFactor, scaleFactor);

		// Background putih bersih
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, width, height);

		// Header Banner Silsilah
		ctx.fillStyle = '#0f172a';
		ctx.font = 'bold 20px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
		ctx.textAlign = 'center';
		ctx.fillText('SILSILAH POHON KELUARGA', width / 2, 45);

		const currentWarga = allNodes.find(n => n.is_current) || allNodes[0];
		ctx.font = 'normal 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
		ctx.fillStyle = '#64748b';
		const subTitle = `Warga: ${currentWarga ? currentWarga.nama : '-'} | No. KK: ${currentWarga ? currentWarga.no_kk : '-'} | RT 29 Minomartani`;
		ctx.fillText(subTitle, width / 2, 70);

		// Pembatas header
		ctx.strokeStyle = '#e2e8f0';
		ctx.lineWidth = 1;
		ctx.beginPath();
		ctx.moveTo(padX, 90);
		ctx.lineTo(width - padX, 90);
		ctx.stroke();

		const offX = -minX + padX;
		const offY = -minY + padY;

		// Gambar Garis Pasangan pada Canvas
		spouseDrawn.forEach(sKey => {
			const [id1, id2] = sKey.split('--');
			const n1 = nodeMap[id1];
			const n2 = nodeMap[id2];
			if (!n1 || !n2) return;
			const leftN = (n1.x < n2.x) ? n1 : n2;
			const rightN = (n1.x < n2.x) ? n2 : n1;
			const x1 = leftN.x + CARD_W + offX;
			const y1 = leftN.y + CARD_H / 2 + offY;
			const x2 = rightN.x + offX;
			const y2 = rightN.y + CARD_H / 2 + offY;

			ctx.strokeStyle = '#0284c7';
			ctx.lineWidth = 2.2;
			ctx.setLineDash([5, 4]);
			ctx.beginPath();
			ctx.moveTo(x1, y1);
			ctx.lineTo(x2, y2);
			ctx.stroke();
			ctx.setLineDash([]);

			// Titik tengah
			ctx.fillStyle = '#0284c7';
			ctx.beginPath();
			ctx.arc((x1 + x2) / 2, (y1 + y2) / 2, 5, 0, Math.PI * 2);
			ctx.fill();
		});

		// Gambar Garis Orang Tua -> Anak pada Canvas
		const drawnCanvasCouple = new Set();
		treeData.edges.forEach(e => {
			if (e.type === 'parent') {
				const parent = nodeMap[e.from];
				const child = nodeMap[e.to];
				if (!parent || !child) return;

				let px = parent.x + CARD_W / 2 + offX;
				let py = parent.y + CARD_H + offY;

				if (child.parents.length >= 2) {
					const p1 = nodeMap[child.parents[0]];
					const p2 = nodeMap[child.parents[1]];
					if (p1 && p2 && p1.spouses.includes(p2.id)) {
						const cKey = child.id + '--couple';
						if (drawnCanvasCouple.has(cKey)) return;
						drawnCanvasCouple.add(cKey);
						const leftP = (p1.x < p2.x) ? p1 : p2;
						const rightP = (p1.x < p2.x) ? p2 : p1;
						px = (leftP.x + CARD_W + rightP.x) / 2 + offX;
						py = leftP.y + CARD_H + offY;
					}
				}

				const cx = child.x + CARD_W / 2 + offX;
				const cy = child.y + offY;

				ctx.strokeStyle = '#94a3b8';
				ctx.lineWidth = 2.2;
				ctx.beginPath();
				ctx.moveTo(px, py);
				ctx.bezierCurveTo(px, py + 45, cx, cy - 45, cx, cy);
				ctx.stroke();
			}
		});

		// Gambar Label Generasi pada Canvas PNG di sebelah kiri
		ranks.forEach(r => {
			const list = generations[r];
			if (list.length > 0) {
				const lx = labelX + offX;
				const ly = list[0].y + CARD_H / 2 - 13 + offY;
				ctx.fillStyle = '#ffffff';
				ctx.strokeStyle = '#cbd5e1';
				ctx.lineWidth = 1;
				drawRoundedRect(ctx, lx, ly, 130, 26, 13);
				ctx.fill();
				ctx.stroke();

				ctx.fillStyle = '#475569';
				ctx.font = 'bold 10px sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				let genName = 'GENERASI ' + (r + 1);
				ctx.fillText(genName, lx + 65, ly + 13);
			}
		});

		// Gambar Seluruh Kartu Node pada Canvas
		allNodes.forEach(n => {
			const nx = n.x + offX;
			const ny = n.y + offY;

			// Background kartu
			ctx.fillStyle = n.is_current ? '#f0f7ff' : (n.is_manual ? '#f8fafc' : '#ffffff');
			ctx.strokeStyle = n.is_current ? '#2563eb' : (n.is_manual ? '#94a3b8' : '#e2e8f0');
			ctx.lineWidth = n.is_current ? 2.5 : 1.2;

			if (n.is_manual) ctx.setLineDash([4, 3]);
			drawRoundedRect(ctx, nx, ny, CARD_W, CARD_H, 12);
			ctx.fill();
			ctx.stroke();
			ctx.setLineDash([]);

			// Avatar Circle
			const isMale = (n.jenis_kelamin === 'L');
			const avX = nx + 24;
			const avY = ny + 25;
			ctx.fillStyle = isMale ? '#e0f2fe' : '#fce7f3';
			ctx.beginPath();
			ctx.arc(avX, avY, 15, 0, Math.PI * 2);
			ctx.fill();
			ctx.strokeStyle = isMale ? '#bae6fd' : '#fbcfe8';
			ctx.lineWidth = 1;
			ctx.stroke();

			// Teks inisial avatar
			ctx.fillStyle = isMale ? '#0284c7' : '#db2777';
			ctx.font = 'bold 12px sans-serif';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText(isMale ? 'L' : 'P', avX, avY + 1);

			// Badge Status Keluarga
			ctx.fillStyle = '#f1f5f9';
			drawRoundedRect(ctx, nx + CARD_W - 105, ny + 14, 95, 20, 10);
			ctx.fill();
			ctx.fillStyle = '#334155';
			ctx.font = 'bold 10px sans-serif';
			ctx.textAlign = 'center';
			ctx.fillText(truncateText(n.status_keluarga || 'Warga', 14), nx + CARD_W - 57, ny + 25);

			// Nama Warga
			ctx.fillStyle = '#0f172a';
			ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
			ctx.textAlign = 'left';
			ctx.textBaseline = 'top';
			wrapText(ctx, n.nama, nx + 12, ny + 46, CARD_W - 24, 16, 2);

			// Garis footer kartu
			ctx.strokeStyle = '#f1f5f9';
			ctx.lineWidth = 1;
			ctx.beginPath();
			ctx.moveTo(nx + 10, ny + CARD_H - 24);
			ctx.lineTo(nx + CARD_W - 10, ny + CARD_H - 24);
			ctx.stroke();

			// Teks Footer Kartu
			ctx.font = 'normal 10px sans-serif';
			ctx.fillStyle = '#64748b';
			ctx.textAlign = 'left';
			ctx.fillText((isMale ? 'Laki-laki' : 'Perempuan') + (parseInt(n.is_hidup) === 0 ? ' (Almarhum)' : ''), nx + 12, ny + CARD_H - 12);

			if (n.is_current) {
				ctx.fillStyle = '#2563eb';
				ctx.font = 'bold 10px sans-serif';
				ctx.textAlign = 'right';
				ctx.fillText('Warga Ini', nx + CARD_W - 12, ny + CARD_H - 12);
			} else if (n.is_manual) {
				ctx.fillStyle = '#64748b';
				ctx.font = 'normal 10px sans-serif';
				ctx.textAlign = 'right';
				ctx.fillText('Luar RT', nx + CARD_W - 12, ny + CARD_H - 12);
			}
		});

		// Trigger download
		const wargaSlug = (currentWarga ? currentWarga.nama : 'warga').toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
		const link = document.createElement('a');
		link.download = `silsilah-keluarga-${wargaSlug}.png`;
		link.href = canvas.toDataURL('image/png');
		link.click();
	});

	// Helper Canvas Drawing Functions
	function drawRoundedRect(c, x, y, w, h, r) {
		c.beginPath();
		c.moveTo(x + r, y);
		c.lineTo(x + w - r, y);
		c.quadraticCurveTo(x + w, y, x + w, y + r);
		c.lineTo(x + w, y + h - r);
		c.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
		c.lineTo(x + r, y + h);
		c.quadraticCurveTo(x, y + h, x, y + h - r);
		c.lineTo(x, y + r);
		c.quadraticCurveTo(x, y, x + r, y);
		c.closePath();
	}

	function truncateText(text, maxLen) {
		if (!text) return '';
		return text.length > maxLen ? text.substring(0, maxLen - 1) + '..' : text;
	}

	function wrapText(c, text, x, y, maxWidth, lineHeight, maxLines) {
		const words = (text || '').split(' ');
		let line = '';
		let lineCount = 0;

		for (let i = 0; i < words.length; i++) {
			const testLine = line + words[i] + ' ';
			const metrics = c.measureText(testLine);
			if (metrics.width > maxWidth && i > 0) {
				lineCount++;
				if (lineCount >= maxLines) {
					c.fillText(line.trim() + '...', x, y);
					return;
				}
				c.fillText(line.trim(), x, y);
				line = words[i] + ' ';
				y += lineHeight;
			} else {
				line = testLine;
			}
		}
		if (line.trim().length > 0 && lineCount < maxLines) {
			c.fillText(line.trim(), x, y);
		}
	}

	function escapeHtml(text) {
		if (!text) return '';
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}
})();
</script>