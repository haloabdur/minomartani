<style>
	/* Latar belakang putih bersih di layar maupun saat dicetak */
	body,
	.wrapper,
	.content-wrapper,
	.content,
	.container-fluid {
		background-color: #ffffff !important;
		background: #ffffff !important;
	}

	.surat-lembar {
		font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
		background: #ffffff !important;
		max-width: 860px;
		margin: 15px auto 40px auto;
		padding: 30px 45px;
		box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
		border: 1px solid #e2e8f0;
		border-radius: 6px;
		color: #000000;
		font-size: 16px;
		line-height: 1.38;
	}

	/* Form Field dengan Garis Titik-titik */
	.form-row-custom {
		display: flex;
		align-items: baseline;
		margin-bottom: 3.5px;
		font-size: 16px;
	}

	.form-label-custom {
		width: 155px;
		flex-shrink: 0;
		color: #000000;
	}

	.form-colon-custom {
		width: 18px;
		flex-shrink: 0;
		text-align: center;
		color: #000000;
	}

	.form-line-custom {
		flex: 1;
		border-bottom: 1px dotted #000000;
		min-height: 21px;
		padding-left: 4px;
		text-transform: uppercase;
		font-size: 16px;
		color: #000000;
		word-break: break-word;
	}

	.form-line-full {
		border-bottom: 1px dotted #000000;
		min-height: 22px;
		width: 100%;
		padding-left: 4px;
		text-transform: uppercase;
		font-size: 16px;
		margin-bottom: 5px;
		color: #000000;
	}

	/* Lampiran list 1-10 */
	.lampiran-row-custom {
		display: flex;
		align-items: baseline;
		margin-bottom: 2px;
		font-size: 15.5px;
	}

	.lampiran-num-custom {
		width: 28px;
		flex-shrink: 0;
		text-align: right;
		padding-right: 6px;
	}

	.lampiran-line-custom {
		flex: 1;
		border-bottom: 1px dotted #000000;
		min-height: 20px;
		padding-left: 4px;
		font-size: 15.5px;
	}

	/* Pejabat tanda tangan */
	.pejabat-title-custom {
		display: flex;
		align-items: baseline;
		font-size: 15.5px;
		padding: 0 4px;
		white-space: nowrap;
	}

	.pejabat-title-line {
		flex: 1;
		border-bottom: 1px dotted #000000;
		margin-left: 6px;
		min-height: 15px;
	}

	.ttd-space-custom {
		height: 44px;
	}

	.pejabat-name-custom {
		border-bottom: 1px dotted #000000;
		margin: 0 8px;
		min-height: 21px;
		font-size: 16px;
		text-align: center;
	}

	/* Kotak Arsip di Kiri Bawah */
	.arsip-box-custom {
		border: 1px solid #000000;
		display: inline-block;
		padding: 3px 8px;
		font-size: 12px;
		line-height: 1.35;
		margin-top: 8px;
	}

	.arsip-box-custom table {
		border-collapse: collapse;
	}

	.arsip-box-custom td {
		padding: 0;
	}

	@media print {
		@page {
			size: A4 portrait;
			margin: 8mm 15mm 6mm 15mm;
		}

		html,
		body,
		.wrapper,
		.content-wrapper,
		.content,
		.container-fluid,
		.surat-lembar,
		.row,
		.col {
			background: #ffffff !important;
			background-color: #ffffff !important;
			color: #000000 !important;
			box-shadow: none !important;
			border: none !important;
			padding: 0 !important;
			margin: 0 !important;
			width: 100% !important;
			max-width: 100% !important;
		}

		.no-print,
		.main-header,
		.main-sidebar,
		.main-footer,
		.content-header {
			display: none !important;
		}

		.content-wrapper {
			margin-left: 0 !important;
			padding: 0 !important;
		}

		/* Pastikan garis titik-titik tetap tajam tercetak */
		.form-line-custom,
		.form-line-full,
		.lampiran-line-custom,
		.pejabat-title-line,
		.pejabat-name-custom {
			border-bottom: 1px dotted #000000 !important;
		}

		.arsip-box-custom {
			border: 1px solid #000000 !important;
		}
	}
</style>

<div class="container-fluid">
	<!-- Baris tombol navigasi & cetak (tidak tampil saat diprint) -->
	<div class="row no-print pt-2 pb-3">
		<div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
			<div class="mb-2 mb-md-0">
				<a href="#" class="btn btn-primary font-weight-bold shadow-sm mr-2" onclick="window.print();return false;">
					<i class="fas fa-print mr-1"></i> Cetak Surat Keterangan
				</a>
				<a href="<?= base_url('admin/surat/view/' . $surat->id_surat) ?>" class="btn btn-default border text-dark font-weight-bold shadow-sm">
					<i class="fas fa-arrow-left mr-1"></i> Kembali
				</a>
			</div>
			<div class="text-muted small">
				<i class="fas fa-info-circle mr-1 text-primary"></i> Dialog cetak otomatis terbuka. Klik tombol jika dialog tertutup.
			</div>
		</div>
	</div>

	<!-- Lembar Surat Resmi Sesuai Blanko Asli (Ukuran Font 16px) -->
	<div class="surat-lembar">
		<!-- Bagian Kepala Surat / Tujuan -->
		<div class="row mb-2" style="font-size: 16px; line-height: 1.38;">
			<div class="col-6">
				<table>
					<tr style="vertical-align: top;">
						<td style="width: 42px;">Hal</td>
						<td style="width: 14px; text-align: center;">:</td>
						<td>Permohonan serta Pernyataan<br>Kebenaran &amp; Keabsahan Dokumen</td>
					</tr>
				</table>
			</div>
			<div class="col-6" style="text-align: right;">
				<div>Sleman, <?= !empty($surat->created_at) ? date('d-m-Y', strtotime($surat->created_at)) : date('d-m-Y') ?></div>
				<div class="mt-1">Kepada</div>
				<div>Yth. Lurah Minomartani</div>
				<div>di Minomartani</div>
			</div>
		</div>

		<!-- Salam Pembuka -->
		<div class="my-2" style="font-size: 16px; line-height: 1.38;">
			<div>Dengan hormat,</div>
			<div>Yang bertanda tangan di bawah ini,</div>
		</div>

		<!-- Data Pemohon dengan Garis Titik-titik -->
		<?php
		$alamatFull = trim((string) ($pemohon['alamat'] ?? ''));
		$alamat1    = $alamatFull;
		$alamat2    = '';
		if (mb_strlen($alamatFull) > 55) {
			$pos = mb_strrpos(mb_substr($alamatFull, 0, 55), ' ');
			if ($pos !== false && $pos > 20) {
				$alamat1 = mb_substr($alamatFull, 0, $pos);
				$alamat2 = mb_substr($alamatFull, $pos + 1);
			}
		}

		$tglLahirDisplay = '';
		if (! empty($pemohon['tanggal_lahir']) && $pemohon['tanggal_lahir'] !== '0000-00-00') {
			$tglLahirDisplay = date('d-m-Y', strtotime($pemohon['tanggal_lahir']));
		}
		?>
		<div class="mb-2">
			<div class="form-row-custom">
				<div class="form-label-custom">Nama Lengkap</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($pemohon['nama']) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">No. KTP/NIK</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($pemohon['nik']) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">Alamat Rumah</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($alamat1) ?></div>
			</div>

			<!-- Baris kedua Alamat Rumah tetap ada garis titik-titik sesuai blanko -->
			<div class="form-row-custom">
				<div class="form-label-custom">&nbsp;</div>
				<div class="form-colon-custom">&nbsp;</div>
				<div class="form-line-custom"><?= esc($alamat2) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">Tempat Lahir</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($pemohon['tempat_lahir']) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">Tanggal Lahir</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($tglLahirDisplay) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">Agama</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($pemohon['agama']) ?></div>
			</div>

			<div class="form-row-custom">
				<div class="form-label-custom">No. Telp/HP</div>
				<div class="form-colon-custom">:</div>
				<div class="form-line-custom"><?= esc($pemohon['no_hp']) ?></div>
			</div>
		</div>

		<!-- Maksud Permohonan -->
		<div class="mt-2 mb-1" style="font-size: 16px;">Dengan ini bermaksud mengajukan permohonan :</div>
		<div class="form-line-full"><?= esc($surat->maksut) ?></div>

		<!-- Untuk Keperluan -->
		<div class="mt-2 mb-1" style="font-size: 16px;">Untuk Keperluan :</div>
		<div class="form-line-full"><?= esc($surat->perlu) ?></div>

		<!-- Berkas Lampiran 1-10 -->
		<div class="mt-2 mb-1" style="font-size: 15.5px; line-height: 1.35;">
			Sehubungan dengan hal tersebut di atas, berikut saya lampirkan berkas-berkas sebagai kelengkapan pendukung permohonan :
		</div>
		<div class="mb-2">
			<?php for ($i = 0; $i < 10; $i++): ?>
				<div class="lampiran-row-custom">
					<span class="lampiran-num-custom"><?= $i + 1 ?>.</span>
					<span class="lampiran-line-custom"><?= isset($lampiran[$i]) ? esc($lampiran[$i]) : '&nbsp;' ?></span>
				</div>
			<?php endfor ?>
		</div>

		<!-- Pernyataan & Penutup -->
		<div class="mb-2" style="font-size: 14.5px; line-height: 1.34;">
			<p class="font-weight-bold mb-1">Data yang terdapat dalam lampiran dokumen permohonan ini adalah Benar dan Sah.</p>
			<p class="mb-1">Apabila dikemudian hari ditemukan bahwa dokumen yang telah saya berikan tidak benar, maka saya bersedia dikenakan sanksi sesuai dengan peraturan dan ketentuan yang berlaku.</p>
			<p class="mb-1">Demikian permohonan dan pernyataan ini saya buat dengan sebenar-benarnya, tanpa ada paksaan dari pihak manapun.</p>
			<p class="mb-0">Atas perkenaan Bapak/Ibu, saya ucapkan terima kasih.</p>
		</div>

		<!-- Tanda Tangan: Hormat Saya di Kanan Atas Pejabat -->
		<div class="row text-center mb-2">
			<div class="col-4"></div>
			<div class="col-4"></div>
			<div class="col-4">
				<div style="font-size: 16px;">Hormat Saya,</div>
				<div class="ttd-space-custom"></div>
				<div class="pejabat-name-custom">
					<?= esc($pemohon['nama']) ?>
				</div>
			</div>
		</div>

		<!-- 3 Kolom Pejabat (Dukuh, Ketua RW, Ketua RT) -->
		<div class="row text-center">
			<div class="col-4">
				<div class="pejabat-title-custom">
					<span>Dukuh</span><span class="pejabat-title-line"></span>
				</div>
				<div class="ttd-space-custom"></div>
				<div class="pejabat-name-custom">
					<?= esc($dukuh ?? '') ?: '&nbsp;' ?>
				</div>
			</div>
			<div class="col-4">
				<div class="pejabat-title-custom">
					<span>Ketua RW</span><span class="pejabat-title-line"></span>
				</div>
				<div class="ttd-space-custom"></div>
				<div class="pejabat-name-custom">
					<?= esc($ketuaRw ?? '') ?: '&nbsp;' ?>
				</div>
			</div>
			<div class="col-4">
				<div class="pejabat-title-custom">
					<span>Ketua RT</span><span class="pejabat-title-line"></span>
				</div>
				<div class="ttd-space-custom"></div>
				<div class="pejabat-name-custom">
					<?= esc($ketuaRt ?? '') ?: '&nbsp;' ?>
				</div>
			</div>
		</div>

		<!-- Kotak Arsip di Kiri Bawah -->
		<div class="arsip-box-custom">
			<table>
				<tr>
					<td style="width: 50px;">Putih</td>
					<td style="width: 14px; text-align: center;">:</td>
					<td>Untuk pemohon dan diarsipkan di Kalurahan</td>
				</tr>
				<tr>
					<td>Kuning</td>
					<td style="text-align: center;">:</td>
					<td>Untuk diarsipkan Ketua RT</td>
				</tr>
				<tr>
					<td>Hijau</td>
					<td style="text-align: center;">:</td>
					<td>Untuk diarsipkan Ketua RW</td>
				</tr>
			</table>
		</div>
	</div>
</div>

<script>
	window.addEventListener('load', function () {
		// Otomatis membuka dialog cetak setelah halaman selesai dimuat
		setTimeout(function () {
			window.print();
		}, 350);
	});
</script>
