<div class="container-fluid">
	<div class="row">
		<div class="col">
			<div class="card card-primary">
				<?php echo form_open('admin/warga/store', ['id' => 'form-warga']) ?>

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
												<label>KK <span class="text-muted">(opsional)</span></label>
												<input type="text" name="no_kk" class="form-control" placeholder="KK">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>NIK <span class="text-danger">*</span></label>
												<input type="text" name="nik" class="form-control" placeholder="NIK" required>
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-group">
												<label>Nama Warga <span class="text-danger">*</span></label>
												<input type="text" name="nama_warga" class="form-control" placeholder="Nama Warga" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Alamat Bandeng <span class="text-danger">*</span></label>
												<select class="form-control select2 w-100" name="id_alamat" required="">
													<option value="">-Pilih Alamat-</option>
													<?php foreach ($alamats as $alamat): ?>
														<option value="<?php echo $alamat->id_alamat ?>"><?php echo $alamat->alamat ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Alamat Lengkap <span class="text-muted">(opsional)</span></label>
												<input type="text" name="alamat_lengkap" class="form-control" placeholder="Alamat Lengkap">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Tempat Lahir <span class="text-danger">*</span></label>
												<input type="text" name="tempat_lahir" class="form-control" placeholder="Tempat Lahir" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Tanggal Lahir <span class="text-danger">*</span></label>
												<input type="date" name="tanggal_lahir" class="form-control" required>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Jenis Kelamin</label>
												<div>
													<input type="radio" name="jenis_kelamin" id="laki-laki" value="L" checked> <label for="laki-laki">Laki-laki</label>
													<input type="radio" name="jenis_kelamin" id="perempuan" value="P" class="ml-3"> <label for="perempuan">Perempuan</label>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Gol. Darah</label>
												<div>
													<input type="radio" name="gol_darah" id="tidak" value="tidak" checked> <label for="tidak">Tidak Tahu</label>
													<input class="ml-3" type="radio" name="gol_darah" id="A" value="A"> <label for="A">A</label>
													<input class="ml-3" type="radio" name="gol_darah" id="B" value="B"> <label for="B">B</label>
													<input class="ml-3" type="radio" name="gol_darah" id="AB" value="AB"> <label for="AB">AB</label>
													<input class="ml-3" type="radio" name="gol_darah" id="O" value="O"> <label for="O">O</label>
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
												<label>Pendidikan <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="pendidikan">
													<option value="">-Pilih-</option>
													<option value="-">Belum Sekolah</option>
													<option value="SD">SD</option>
													<option value="SMP">SMP</option>
													<option value="SMA">SMA</option>
													<option value="D1">D1</option>
													<option value="D2">D2</option>
													<option value="D3">D3</option>
													<option value="S1">S1</option>
													<option value="S2">S2</option>
													<option value="S3">S3</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Pekerjaan <span class="text-muted">(opsional)</span></label>
												<select class="form-control select2 w-100" name="id_pekerjaan">
													<option value="">-Pilih Pekerjaan-</option>
													<?php foreach ($pekerjaans as $pekerjaan): ?>
														<option value="<?php echo $pekerjaan->id_pekerjaan ?>"><?php echo $pekerjaan->nama_pekerjaan ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Agama <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="agama">
											<option value="">-Pilih-</option>
											<option value="islam">Islam</option>
													<option value="kristen">Kristen</option>
													<option value="katholik">Katholik</option>
													<option value="hindu">Hindu / Budha</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Sumber Air <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="sumber_air">
													<option value="">-Pilih Sumber Air-</option>
													<option value="Sumur">Sumur</option>
													<option value="PDAM">PDAM</option>
													<option value="Sumur dan PDAM">Sumur dan PDAM</option>
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
												<label>Status Kawin <span class="text-danger">*</span></label>
												<select class="form-control" name="status_kawin" required="">
													<option value="0">Belum Kawin</option>
													<option value="1">Kawin</option>
													<option value="2">Cerai Hidup</option>
													<option value="3">Cerai Mati</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Tanggal Kawin</label>
												<input type="date" name="tanggal_kawin" class="form-control">
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label>Status Keluarga <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="id_status_keluarga">
													<option value="">-Pilih Status Keluarga-</option>
													<?php foreach ($status_keluargas as $status_keluarga): ?>
														<option value="<?php echo $status_keluarga->id_status_keluarga ?>"><?php echo $status_keluarga->status ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Ayah</label>
												<div class="input-group">
													<input type="text" name="ayah" id="input-ayah" class="form-control" placeholder="Ayah">
													<div class="input-group-append">
														<button type="button" class="btn btn-outline-primary" onclick="openWargaModal('ayah')">
															<i class="fas fa-search mr-1"></i> Pilih Warga
														</button>
													</div>
												</div>
												<p class="text-muted small mt-1">Apabila <strong>Ayah</strong> adalah warga RT maka masukkan nomor ID. Contoh : 1</p>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Ibu</label>
												<div class="input-group">
													<input type="text" name="ibu" id="input-ibu" class="form-control" placeholder="Ibu">
													<div class="input-group-append">
														<button type="button" class="btn btn-outline-primary" onclick="openWargaModal('ibu')">
															<i class="fas fa-search mr-1"></i> Pilih Warga
														</button>
													</div>
												</div>
												<p class="text-muted small mt-1">Apabila <strong>Ibu</strong> adalah warga RT maka masukkan nomor ID. Contoh : 2</p>
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
													<input type="number" name="no_hp" class="form-control" placeholder="838xxxxxx">
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Email Aktif</label>
												<input type="email" name="email" class="form-control" placeholder="Email Aktif">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Status Penduduk <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="id_status_penduduk">
													<?php foreach ($status_penduduks as $status_penduduk): ?>
														<option value="<?php echo $status_penduduk->id_status_penduduk ?>"><?php echo $status_penduduk->status ?></option>
													<?php endforeach ?>
												</select>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label>Status Warga <span class="text-muted">(opsional)</span></label>
												<select class="form-control" name="status_warga">
													<option value="1">Aktif</option>
													<option value="0">Tidak Aktif</option>
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