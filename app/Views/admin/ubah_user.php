<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php echo form_open('admin/users/update/' . $user->id) ?>
				<div class="card-body">

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label>Username</label>
								<input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $user->username ?>" required>
							</div>
						</div>

						<div class="col">
							<div class="form-group">
								<label>Email</label>
								<input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo $user->email ?>" required disabled>
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label>Ubah Password</label>
								<input type="password" name="password" class="form-control" placeholder="Password">
							</div>
						</div>

						<div class="col">
							<div class="form-group">
								<label>Ulangi Password</label>
								<input type="password" name="cpassword" class="form-control" placeholder="Ulangi Password">
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label for="id_rt">RT (Untuk Admin RT)</label>
								<select id="id_rt" name="id_rt" class="form-control">
									<option value="">-- Bukan Admin RT --</option>
									<?php foreach ($rts as $rt): ?>
										<option value="<?= $rt->id_rt ?>" <?= (int)$user->id_rt === (int)$rt->id_rt ? 'selected' : '' ?>><?= esc($rt->nama) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="col">
							<div class="form-group">
								<label for="id_rw">RW (Untuk Pengurus RW)</label>
								<select id="id_rw" name="id_rw" class="form-control">
									<option value="">-- Bukan Pengurus RW --</option>
									<?php foreach ($rws as $rw): ?>
										<option value="<?= $rw->id_rw ?>" <?= (int)$user->id_rw === (int)$rw->id_rw ? 'selected' : '' ?>><?= esc($rw->nama) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
					<small class="form-text text-muted">Pilih salah satu saja: RT untuk Admin RT, atau RW untuk Pengurus RW. Kosongkan keduanya untuk menjadikan Superadmin.</small>

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label>Hak Akses Menu <small class="text-muted">(khusus Admin RT)</small></label>
								<div>
									<?php foreach ($menuOptions as $key => $label): ?>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" name="menu_akses[]" value="<?= esc($key) ?>" id="menu_<?= esc($key) ?>" <?= in_array($key, $userPermissions, true) ? 'checked' : '' ?>>
											<label class="form-check-label" for="menu_<?= esc($key) ?>"><?= esc($label) ?></label>
										</div>
									<?php endforeach; ?>
								</div>
								<small class="form-text text-muted">Centang menu yang boleh diakses. Hapus centang untuk membatasi akses Admin RT ini hanya ke menu tertentu.</small>
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label>Hak Akses Menu <small class="text-muted">(khusus Pengurus RW)</small></label>
								<div>
									<?php foreach ($rwMenuOptions as $key => $label): ?>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" name="menu_akses[]" value="<?= esc($key) ?>" id="menu_<?= esc($key) ?>" <?= in_array($key, $userPermissions, true) ? 'checked' : '' ?>>
											<label class="form-check-label" for="menu_<?= esc($key) ?>"><?= esc($label) ?></label>
										</div>
									<?php endforeach; ?>
								</div>
								<small class="form-text text-muted">Centang menu yang boleh diakses. Hapus centang untuk membatasi akses Pengurus RW ini hanya ke menu tertentu.</small>
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col">
							<div class="form-group">
								<label>Hak Akses Menu <small class="text-muted">(berlaku untuk Admin RT maupun Pengurus RW)</small></label>
								<div>
									<?php foreach ($sharedMenuOptions as $key => $label): ?>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" name="menu_akses[]" value="<?= esc($key) ?>" id="menu_<?= esc($key) ?>" <?= in_array($key, $userPermissions, true) ? 'checked' : '' ?>>
											<label class="form-check-label" for="menu_<?= esc($key) ?>"><?= esc($label) ?></label>
										</div>
									<?php endforeach; ?>
								</div>
								<small class="form-text text-muted">Centang menu yang boleh diakses, berlaku apa pun peran user ini.</small>
							</div>
						</div>
					</div>

				</div>
				<!-- /.card-body -->

				<div class="card-footer">
					<a href="<?php echo base_url('admin/users') ?>" class="btn btn-light">Kembali</a>
					<button type="submit" class="btn btn-primary">Simpan</button>
				</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var idRt = document.getElementById('id_rt');
    var idRw = document.getElementById('id_rw');
    if (idRt && idRw) {
        idRt.addEventListener('change', function () {
            if (this.value !== '') idRw.value = '';
        });
        idRw.addEventListener('change', function () {
            if (this.value !== '') idRt.value = '';
        });
    }
});
</script>
