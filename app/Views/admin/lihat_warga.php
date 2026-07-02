<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<div class="card-body">
					<p class="text-muted small border-bottom">INFORMASI PRIBADI</p>
					<dl class="row">
						<dd class="col-sm-2">Nama</dd>
						<dt class="col-sm-10"><?php echo $warga->nama_warga ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">KK</dd>
						<dt class="col-sm-10"><?php echo $warga->no_kk ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">NIK</dd>
						<dt class="col-sm-10"><?php echo $warga->nik ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">Jenis Kelamin</dd>
						<dt class="col-sm-10"><?php echo $warga->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">TTL</dd>
						<dt class="col-sm-10"><?php echo $warga->tempat_lahir . ' , ' . date('d-m-Y', strtotime($warga->tanggal_lahir)) ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">Gol. Darah</dd>
						<dt class="col-sm-10">
							<?php
							if ($warga->gol_darah == NULL) {
								echo "Tidak Tercatat";
							} else {
								echo $warga->gol_darah;
							}
							?>
						</dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">Agama</dd>
						<dt class="col-sm-10"><?php echo ucwords($warga->agama) ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">Alamat Bandeng</dd>
						<dt class="col-sm-10"><?php echo $warga->alamat ?></dt>
					</dl>
					<dl class="row">
						<dd class="col-sm-2">Alamat Lengkap</dd>
						<dt class="col-sm-10"><?php echo $warga->alamat_lengkap ?></dt>
					</dl>

					<p class="mt-4 text-muted small border-bottom">INFORMASI LAIN</p>

					<dl class="row">
						<dd class="col-sm-2">Pendidikan</dd>
						<dt class="col-sm-10"><?php echo $warga->pendidikan ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Pekerjaan</dd>
						<dt class="col-sm-10"><?php echo $pekerjaan->nama_pekerjaan ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Sumber Air</dd>
						<dt class="col-sm-10"><?php echo $warga->sumber_air ?? '-' ?></dt>
					</dl>

					<p class="mt-4 text-muted small border-bottom">INFORMASI KELUARGA</p>

					<dl class="row">
						<dd class="col-sm-2">Status Kawin</dd>
						<dt class="col-sm-10">
							<?php
							switch ($warga->status_kawin) {
								case 1:
									echo 'Kawin';
									break;
								case 1:
									echo 'Cerai Hidup';
									break;
								case 1:
									echo 'Cerai Mati';
									break;
								default:
									echo 'Belum Kawin';
									break;
							}
							?>
						</dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Tanggal Kawin</dd>
						<dt class="col-sm-10"><?php echo $warga->tanggal_kawin ? date('d-m-Y', strtotime($warga->tanggal_kawin)) : '-' ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Status Keluarga</dd>
						<dt class="col-sm-10"><?php echo ucwords($warga->status_keluarga) ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Ayah</dd>
						<dt class="col-sm-10"><?php echo $warga->ayah ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Ibu</dd>
						<dt class="col-sm-10"><?php echo $warga->ibu ?></dt>
					</dl>

					<p class="mt-4 text-muted small border-bottom">INFORMASI KONTAK</p>

					<dl class="row">
						<dd class="col-sm-2">No. HP</dd>
						<dt class="col-sm-10"><?php if ($warga->no_hp) { ?><a target="_blank" href="https://wa.me/62<?php echo $warga->no_hp ?>" alt="Whatsapp"> +62<?php echo $warga->no_hp ?></a><?php } else {
																																																	echo '-';
																																																} ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Email</dd>
						<dt class="col-sm-10"><?php echo $warga->email ? $warga->email : '-' ?></dt>
					</dl>

					<p class="mt-4 text-muted small border-bottom">INFORMASI STATUS</p>

					<dl class="row">
						<dd class="col-sm-2">Status Hidup</dd>
						<dt class="col-sm-10"><?php echo $warga->is_hidup ? '<label class="ml-1 badge badge-pills badge-primary">Hidup</label>' : '<label class="ml-1 badge badge-pills badge-danger">Meninggal</label>' ?></dt>
					</dl>

					<dl class="row">
						<dd class="col-sm-2">Status Warga</dd>
						<dt class="col-sm-10"><?php echo $warga->status_warga ? '<label class="ml-1 badge badge-pills badge-success">Aktif</label>' : '<label class="ml-1 badge badge-pills badge-danger">Tidak Aktif</label>' ?></dt>
					</dl>
				</div>
				<!-- /.card-body -->

				<div class="card-footer">
					<a href="<?php echo base_url('admin/warga') ?>" class="btn btn-light">Kembali</a>
					<a href="<?php echo base_url('admin/warga/edit/' . $warga->id_warga) ?>" type="submit" class="btn btn-outline-primary">Ubah</a>
				</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>

<script type="text/javascript">
	function copy() {
		/* Get the text field */
		const el = document.getElementById('kk');
		el.select();
		document.execCommand('copy');
	}
</script>