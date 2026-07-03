<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/warga/update/' . $warga->id_warga, ['id' => 'form-warga']) ?>

				<div class="card-body">
					<!-- Accordion -->
					<div class="accordion" id="accordionWarga">

						<!-- 1. Informasi Pribadi (default open) -->
						<div class="card mb-2">
							<div class="card-header p-0" id="headingPribadi">
								<h2 class="mb-0">
									<button class="btn btn-block text-left font-weight-bold p-3" type="button" data-toggle="collapse" data-target="#collapsePribadi" aria-expanded="true">
										<i class="fas fa-user mr-2"></i> Informasi Pribadi
									</button>
								</h2>
							</div>
							<div id="collapsePribadi" class="collapse show" aria-labelledby="headingPribadi" data-parent="#accordionWarga">
								<div class="card-body">
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label>KK</label>
												<input type="text" name="no_kk" class="form-control" placeholder="KK" value="<?php echo $warga->no_kk ?>" readonly required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>NIK <a href="#" class="ml-2 small text-primary" onclick="toggleNik(event)">Ubah NIK</a></label>
												<input type="text" name="nik" id="nik-field" class="form-control" placeholder="NIK" value="<?php echo $warga->nik ?>" readonly required>
												<input type="hidden" name="onik" value="<?php echo $warga->nik ?>">
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-group">
												<label>Nama Warga</label>
												<input type="text" name="nama_warga" class="form-control" placeholder="Nama Warga" value="<?php echo $warga->nama_warga ?>" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Alamat Bandeng</label>
												<select class="form-control select2 w-100" name="id_alamat" required="">
													<option value="">-Pilih Alamat Bandeng-</option>
													<?php foreach ($alamats as $alamat): ?>
														<option <?php echo $alamat->id_alamat == $warga->id_alamat ? "selected" : ""; ?> value="<?php echo $alamat->id_alamat ?>"><?php echo $alamat->alamat ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Alamat Lengkap</label>
												<input type="text" name="alamat_lengkap" class="form-control" placeholder="Alamat Lengkap" value="<?php echo $warga->alamat_lengkap ?>" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Tempat Lahir</label>
												<input type="text" name="tempat_lahir" class="form-control" value="<?php echo $warga->tempat_lahir ?>" placeholder="Tempat Lahir" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Tanggal Lahir</label>
												<input type="date" name="tanggal_lahir" class="form-control" value="<?php echo $warga->tanggal_lahir ?>" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Jenis Kelamin</label>
												<div>
													<input type="radio" name="jenis_kelamin" id="laki-laki" value="L" <?php echo $warga->jenis_kelamin == 'L' ? 'checked' : '' ?>> <label for="laki-laki">Laki-laki</label>
													<input class="ml-3" type="radio" name="jenis_kelamin" id="perempuan" value="P" <?php echo $warga->jenis_kelamin == 'P' ? 'checked' : '' ?>> <label for="perempuan">Perempuan</label>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Gol. Darah</label>
												<div>
													<input type="radio" name="gol_darah" id="tidak" value="tidak" <?php echo $warga->gol_darah == 'tidak' || $warga->gol_darah == null ? 'checked' : '' ?>> <label for="tidak">Tidak Tahu</label>
													<input class="ml-3" type="radio" name="gol_darah" id="A" value="A" <?php echo $warga->gol_darah == 'A' ? 'checked' : '' ?>> <label for="A">A</label>
													<input class="ml-3" type="radio" name="gol_darah" id="B" value="B" <?php echo $warga->gol_darah == 'B' ? 'checked' : '' ?>> <label for="B">B</label>
													<input class="ml-3" type="radio" name="gol_darah" id="AB" value="AB" <?php echo $warga->gol_darah == 'AB' ? 'checked' : '' ?>> <label for="AB">AB</label>
													<input class="ml-3" type="radio" name="gol_darah" id="O" value="O" <?php echo $warga->gol_darah == 'O' ? 'checked' : '' ?>> <label for="O">O</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- 2. Informasi Lain -->
						<div class="card mb-2">
							<div class="card-header p-0" id="headingLain">
								<h2 class="mb-0">
									<button class="btn btn-block text-left font-weight-bold collapsed p-3" type="button" data-toggle="collapse" data-target="#collapseLain" aria-expanded="false">
										<i class="fas fa-info-circle mr-2"></i> Informasi Lain
									</button>
								</h2>
							</div>
							<div id="collapseLain" class="collapse" aria-labelledby="headingLain" data-parent="#accordionWarga">
								<div class="card-body">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>Pendidikan</label>
												<select class="form-control" name="pendidikan" required="">
													<option value="">-Pilih-</option>
													<option <?php echo $warga->pendidikan == '-' ? 'selected' : '' ?> value="-">Belum Sekolah</option>
													<option <?php echo $warga->pendidikan == 'SD' ? 'selected' : '' ?> value="SD">SD</option>
													<option <?php echo $warga->pendidikan == 'SMP' ? 'selected' : '' ?> value="SMP">SMP</option>
													<option <?php echo $warga->pendidikan == 'SMA' ? 'selected' : '' ?> value="SMA">SMA</option>
													<option <?php echo $warga->pendidikan == 'SMK' ? 'selected' : '' ?> value="SMK">SMK</option>
													<option <?php echo $warga->pendidikan == 'D1' ? 'selected' : '' ?> value="D1">D1</option>
													<option <?php echo $warga->pendidikan == 'D2' ? 'selected' : '' ?> value="D2">D2</option>
													<option <?php echo $warga->pendidikan == 'D3' ? 'selected' : '' ?> value="D3">D3</option>
													<option <?php echo $warga->pendidikan == 'D4' ? 'selected' : '' ?> value="D4">D4</option>
													<option <?php echo $warga->pendidikan == 'S1' ? 'selected' : '' ?> value="S1">S1</option>
													<option <?php echo $warga->pendidikan == 'S2' ? 'selected' : '' ?> value="S2">S2</option>
													<option <?php echo $warga->pendidikan == 'S3' ? 'selected' : '' ?> value="S3">S3</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Pekerjaan</label>
												<select class="form-control select2 w-100" name="id_pekerjaan" required="">
													<option value="">-Pilih Pekerjaan-</option>
													<?php foreach ($pekerjaans as $pekerjaan): ?>
														<option <?php echo $pekerjaan->id_pekerjaan == $warga->id_pekerjaan ? 'selected' : '' ?> value="<?php echo $pekerjaan->id_pekerjaan ?>"><?php echo $pekerjaan->nama_pekerjaan ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Agama</label>
												<select class="form-control" name="agama" required="">
													<option <?php echo $warga->agama == 'islam' ? 'selected' : '' ?> value="islam">Islam</option>
													<option <?php echo $warga->agama == 'kristen' ? 'selected' : '' ?> value="kristen">Kristen</option>
													<option <?php echo $warga->agama == 'katholik' ? 'selected' : '' ?> value="katholik">Katholik</option>
													<option <?php echo $warga->agama == 'hindu' ? 'selected' : '' ?> value="hindu">Hindu / Budha</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Sumber Air <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="sumber_air">
													<option value="">-Pilih Sumber Air-</option>
													<option <?php echo ($warga->sumber_air ?? '') == 'Sumur' ? 'selected' : '' ?> value="Sumur">Sumur</option>
													<option <?php echo ($warga->sumber_air ?? '') == 'PDAM' ? 'selected' : '' ?> value="PDAM">PDAM</option>
													<option <?php echo ($warga->sumber_air ?? '') == 'Sumur dan PDAM' ? 'selected' : '' ?> value="Sumur dan PDAM">Sumur dan PDAM</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- 3. Informasi Keluarga -->
						<div class="card mb-2">
							<div class="card-header p-0" id="headingKeluarga">
								<h2 class="mb-0">
									<button class="btn btn-block text-left font-weight-bold collapsed p-3" type="button" data-toggle="collapse" data-target="#collapseKeluarga" aria-expanded="false">
										<i class="fas fa-users mr-2"></i> Informasi Keluarga
									</button>
								</h2>
							</div>
							<div id="collapseKeluarga" class="collapse" aria-labelledby="headingKeluarga" data-parent="#accordionWarga">
								<div class="card-body">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Kawin</label>
												<select class="form-control" name="status_kawin" required="">
													<option value="">-Pilih Status-</option>
													<option <?php echo $warga->status_kawin == '0' ? 'selected' : '' ?> value="0">Belum Kawin</option>
													<option <?php echo $warga->status_kawin == '1' ? 'selected' : '' ?> value="1">Kawin</option>
													<option <?php echo $warga->status_kawin == '2' ? 'selected' : '' ?> value="2">Cerai Hidup</option>
													<option <?php echo $warga->status_kawin == '3' ? 'selected' : '' ?> value="3">Cerai Mati</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Tanggal Kawin</label>
												<input type="date" name="tanggal_kawin" class="form-control" value="<?php echo $warga->tanggal_kawin ?>">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Keluarga</label>
												<select class="form-control" name="id_status_keluarga" required="">
													<option value="">-Pilih Status Keluarga-</option>
													<?php foreach ($status_keluargas as $status_keluarga): ?>
														<option <?php echo $warga->id_status_keluarga == $status_keluarga->id_status_keluarga ? 'selected' : '' ?> value="<?php echo $status_keluarga->id_status_keluarga ?>"><?php echo $status_keluarga->status ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Ayah</label>
												<div class="input-group">
													<input type="text" name="ayah" id="input-ayah" class="form-control" placeholder="Ayah" value="<?php echo $warga->ayah ?>">
													<div class="input-group-append">
														<button type="button" class="btn btn-outline-primary" onclick="openWargaModal('ayah')">
															<i class="fas fa-search mr-1"></i> Pilih Warga
														</button>
													</div>
												</div>
												<p class="text-muted small mt-1">Apabila <strong>Ayah</strong> adalah warga RT 29 maka masukkan nomor ID. Contoh : 1</p>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Ibu</label>
												<div class="input-group">
													<input type="text" name="ibu" id="input-ibu" class="form-control" placeholder="Ibu" value="<?php echo $warga->ibu ?>">
													<div class="input-group-append">
														<button type="button" class="btn btn-outline-primary" onclick="openWargaModal('ibu')">
															<i class="fas fa-search mr-1"></i> Pilih Warga
														</button>
													</div>
												</div>
												<p class="text-muted small mt-1">Apabila <strong>Ibu</strong> adalah warga RT 29 maka masukkan nomor ID. Contoh : 2</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- 4. Informasi Kontak & Status -->
						<div class="card mb-2">
							<div class="card-header p-0" id="headingKontak">
								<h2 class="mb-0">
									<button class="btn btn-block text-left font-weight-bold collapsed p-3" type="button" data-toggle="collapse" data-target="#collapseKontak" aria-expanded="false">
										<i class="fas fa-phone mr-2"></i> Informasi Kontak & Status
									</button>
								</h2>
							</div>
							<div id="collapseKontak" class="collapse" aria-labelledby="headingKontak" data-parent="#accordionWarga">
								<div class="card-body">
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label>No. HP Aktif</label>
												<div class="input-group">
													<div class="input-group-prepend">
														<span class="input-group-text bg-white">+62</span>
													</div>
													<input type="number" name="no_hp" class="form-control" placeholder="838xxxxxx" value="<?php echo $warga->no_hp ?>">
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Email Aktif</label>
												<input type="email" name="email" class="form-control" placeholder="Email Aktif" value="<?php echo $warga->email ?>">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Penduduk</label>
												<select class="form-control" name="id_status_penduduk" required="">
													<?php foreach ($status_penduduks as $status_penduduk): ?>
														<option <?php echo $warga->id_status_penduduk == $status_penduduk->id_status_penduduk ? 'selected' : '' ?> value="<?php echo $status_penduduk->id_status_penduduk ?>"><?php echo $status_penduduk->status ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Hidup</label>
												<select class="form-control" name="is_hidup" required="">
													<option <?php echo $warga->is_hidup == 1 ? 'selected' : '' ?> value="1">Hidup</option>
													<option <?php echo $warga->is_hidup == 0 ? 'selected' : '' ?> value="0">Meninggal</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Warga</label>
												<select class="form-control" name="status_warga" required="">
													<option <?php echo $warga->status_warga == 1 ? 'selected' : '' ?> value="1">Aktif</option>
													<option <?php echo $warga->status_warga == 0 ? 'selected' : '' ?> value="0">Tidak Aktif</option>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

					</div>
					<!-- /.accordion -->
				</div>
				<!-- /.card-body -->

				<div class="card-footer">
					<a href="<?php echo base_url('admin/warga') ?>" class="btn btn-light">Kembali</a>
					<button type="submit" class="btn btn-primary btn-submit">
						<i class="fas fa-save mr-1"></i> Simpan
					</button>
				</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Modal Pilih Warga -->
<div class="modal fade" id="wargaModal" tabindex="-1" role="dialog" aria-labelledby="wargaModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="wargaModalLabel">Pilih Warga Terdaftar</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="table-responsive">
					<table class="table table-bordered table-striped datatable" id="table-select-warga" style="width: 100%">
						<thead>
							<tr>
								<th>ID</th>
								<th>Nama Warga</th>
								<th>NIK</th>
								<th>Alamat</th>
								<th>Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($wargas)): ?>
								<?php foreach ($wargas as $w): ?>
									<tr>
										<td><?php echo $w->id_warga; ?></td>
										<td><strong><?php echo $w->nama_warga; ?></strong></td>
										<td><?php echo $w->nik; ?></td>
										<td><?php echo 'Jl. ' . $w->alamat . ($w->alamat_lengkap ? ' (' . $w->alamat_lengkap . ')' : ''); ?></td>
										<td>
											<button type="button" class="btn btn-primary btn-sm btn-select-warga" data-id="<?php echo $w->id_warga; ?>" data-nama="<?php echo htmlspecialchars($w->nama_warga, ENT_QUOTES, 'UTF-8'); ?>">
												Pilih
											</button>
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

<script>
	function toggleNik(e) {
		e.preventDefault();
		var nikField = document.getElementById('nik-field');
		nikField.readOnly = !nikField.readOnly;
		if (!nikField.readOnly) nikField.focus();
	}

	let currentTargetInputId = '';

	window.openWargaModal = function(target) {
		currentTargetInputId = 'input-' + target;
		jQuery('#wargaModal').modal('show');
	};

	document.addEventListener("DOMContentLoaded", function() {
		// When the modal is shown, adjust columns of DataTable inside it
		jQuery('#wargaModal').on('shown.bs.modal', function () {
			jQuery(jQuery.fn.dataTable.tables(true)).DataTable().columns.adjust();
		});

		// Handle Pilih button click inside modal
		jQuery(document).on('click', '.btn-select-warga', function() {
			var idWarga = jQuery(this).data('id');
			var namaWarga = jQuery(this).data('nama');
			
			if (currentTargetInputId) {
				jQuery('#' + currentTargetInputId).val(idWarga);
			}
			jQuery('#wargaModal').modal('hide');
		});
	});
</script>