<div class="container-fluid">
	<!-- this row will not appear when printing -->
	<div class="row no-print">
		<div class="col-12">
			<a href="#" class="btn btn-primary" onclick="window.print();return false;"><i class="fas fa-print"></i> Cetak Surat Keterangan</a>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<div class="row my-4">
				<div class="col">
					Hal : Permohonan Surat Keterangan <br> serta Pernyataan Kebenaran & Keabsahan <br> Dokumen.
				</div>
				<div style="text-align: right" class="col">
					Sleman, <?php echo date('d-m-Y') ?><br>
					Kepada, Yth. Lurah Minomartani<br>
					di Minomartani
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					Dengan hormat, <br>
					Yang bertanda tangan dibawah ini,
				</div>
			</div>

			<dl class="row pt-4">
				<dd class="col-sm-2">Nama Lengkap</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?php echo $surat->nama_warga; ?></dd>

				<dd class="col-sm-2">No. KTP/NIK</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;<?php echo $surat->nik; ?></dd>

				<dd class="col-sm-2">Alamat Rumah</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;Jl. <?php echo $surat->alamat; ?> Minomartani, Ngaglik, Sleman, Daerah Istimewa Yogyakarta</dd>

				<dd class="col-sm-2">Tempat Lahir</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?php echo $surat->tempat_lahir; ?></dd>

				<dd class="col-sm-2">Tanggal Lahir</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;<?php echo date('d-m-Y', strtotime($surat->tanggal_lahir)); ?></dd>

				<dd class="col-sm-2">Agama</dd>
				<dd class="col-sm-10 text-uppercase">: &nbsp;&nbsp;<?php echo $surat->agama; ?></dd>

				<dd class="col-sm-2">No. Telp / HP</dd>
				<dd class="col-sm-10">: &nbsp;&nbsp;0<?php echo $surat->no_hp; ?></dd>
			</dl>

			<p>Dengan ini bermaksud mengajukan permohonan Surat Keterangan :</p>
			<p class="border-bottom text-uppercase"><?php echo $surat->maksut; ?></p>

			<p>Untuk Keperluan</p>
			<p class="border-bottom text-uppercase"><?php echo $surat->perlu; ?></p>

			<p>Sehubungan dengan hal tersebut di atas, berikut Saya akan lampirkan berkas-berkas sebagai kelengkapan pendukung permohonan :</p>
			<div class="pb-3">
				<?php
				$lampiran = explode(',', trim($surat->lampiran));

				for ($i = 0; $i < 10; $i++) {
					if (!empty($lampiran[$i])) {
						echo '<div>' . ($i + 1) . '. ' . $lampiran[$i] . '</div>';
					} else {
						echo '<div>' . ($i + 1) . '. .........</div>';
					}
				}
				?>
			</div>
			<p class="font-weight-bold">Data yang terdapat dalam lampiran dokumen permohonan ini adalah Benar dan Sah.</p>
			<p>Apabila dikemudian hari ditemukan bahwa dokumen yang telah saya berikan tidak benar, maka saya bersedia dikenakan sanksi sesuai dengan peraturan dan ketentuan yang berlaku.</p>
			<p>Demikian permohonan dan pernyataan ini saya buat dengan sebenar-benarnya, tanpa ada paksaan dari pihak manapun.</p>
			<p>Atas perkenan Bapak / Ibu kami ucapkan terima kasih.</p>

			<div class="py-3 text-right mr-5">
				<p>Hormat Saya,</p>
				<br>
				<br>
				<p><?php echo $surat->nama_warga ?></p>
			</div>

			<div class="text-center">
				<p>Mengetahui,</p>
				<div class="row">
					<div class="col">Ketua RT Ristohadi S.</div>
					<div class="col">Ketua RW</div>
					<div class="col">Dukuh</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	window.addEventListener("load", window.print());
</script>