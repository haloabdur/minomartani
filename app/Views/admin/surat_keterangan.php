<div class="container-fluid">
	<!-- this row will not appear when printing -->
	<div class="row no-print">
		<div class="col-12">
			<a href="#" class="btn btn-primary" onclick="window.print();return false;"><i class="fas fa-print"></i> Cetak Surat Keterangan</a>
			<a href="<?= base_url('admin/surat/view/' . $surat->id_surat) ?>" class="btn btn-light">Kembali</a>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<div class="row my-4">
				<div class="col">
					Hal : Permohonan serta Pernyataan <br> Kebenaran &amp; Keabsahan Dokumen
				</div>
				<div style="text-align: right" class="col">
					Sleman, <?= date('d-m-Y') ?><br>
					Kepada<br>
					Yth. Lurah Minomartani<br>
					di Minomartani
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					Dengan hormat, <br>
					Yang bertanda tangan di bawah ini,
				</div>
			</div>

			<dl class="row pt-4">
				<dd class="col-sm-2">Nama Lengkap</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?= esc($pemohon['nama']) ?></dd>

				<dd class="col-sm-2">No. KTP/NIK</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;<?= esc($pemohon['nik']) ?></dd>

				<dd class="col-sm-2">Alamat Rumah</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?= esc($pemohon['alamat']) ?></dd>

				<dd class="col-sm-2">Tempat Lahir</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?= esc($pemohon['tempat_lahir']) ?></dd>

				<dd class="col-sm-2">Tanggal Lahir</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;<?= $pemohon['tanggal_lahir'] === '' ? '' : date('d-m-Y', strtotime($pemohon['tanggal_lahir'])) ?></dd>

				<dd class="col-sm-2">Agama</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?= esc($pemohon['agama']) ?></dd>

				<dd class="col-sm-2">No. Telp/HP</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;<?= esc($pemohon['no_hp']) ?></dd>
			</dl>

			<p>Dengan ini bermaksud mengajukan permohonan :</p>
			<p class="border-bottom text-uppercase"><?= esc($surat->maksut) ?></p>

			<p>Untuk Keperluan :</p>
			<p class="border-bottom text-uppercase"><?= esc($surat->perlu) ?></p>

			<p>Sehubungan dengan hal tersebut di atas, berikut saya lampirkan berkas-berkas sebagai kelengkapan pendukung permohonan :</p>
			<div class="pb-3">
				<?php for ($i = 0; $i < 10; $i++): ?>
					<div><?= $i + 1 ?>. <?= isset($lampiran[$i]) ? esc($lampiran[$i]) : '.........' ?></div>
				<?php endfor ?>
			</div>
			<p class="font-weight-bold">Data yang terdapat dalam lampiran dokumen permohonan ini adalah Benar dan Sah.</p>
			<p>Apabila dikemudian hari ditemukan bahwa dokumen yang telah saya berikan tidak benar, maka saya bersedia dikenakan sanksi sesuai dengan peraturan dan ketentuan yang berlaku.</p>
			<p>Demikian permohonan dan pernyataan ini saya buat dengan sebenar-benarnya, tanpa ada paksaan dari pihak manapun.</p>
			<p>Atas perkenan Bapak/Ibu, saya ucapkan terima kasih.</p>

			<div class="py-3 text-right mr-5">
				<p>Hormat Saya,</p>
				<br>
				<br>
				<p><?= esc($pemohon['nama']) ?></p>
			</div>

			<div class="text-center">
				<div class="row">
					<div class="col">Dukuh<br><br><br><br><?= esc($dukuh ?? '') ?: '..................' ?></div>
					<div class="col">Ketua RW<br><br><br><br><?= esc($ketuaRw ?? '') ?: '..................' ?></div>
					<div class="col">Ketua RT<br><br><br><br><?= esc($ketuaRt ?? '') ?: '..................' ?></div>
				</div>
			</div>

			<div class="border small mt-4 p-2" style="max-width: 22rem">
				Putih : Untuk pemohon dan diarsipkan di Kalurahan<br>
				Kuning : Untuk diarsipkan Ketua RT<br>
				Hijau : Untuk diarsipkan Ketua RW
			</div>
		</div>
	</div>
</div>
