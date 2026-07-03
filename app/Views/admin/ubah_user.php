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
								<label>RT (Untuk Admin RT)</label>
								<select name="id_rt" class="form-control">
									<option value="">-- Bukan Admin RT --</option>
									<?php foreach ($rts as $rt): ?>
										<option value="<?= $rt->id_rt ?>" <?= (int)$user->id_rt === (int)$rt->id_rt ? 'selected' : '' ?>><?= esc($rt->nama) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="col">
							<div class="form-group">
								<label>RW (Untuk Pengurus RW)</label>
								<select name="id_rw" class="form-control">
									<option value="">-- Bukan Pengurus RW --</option>
									<?php foreach ($rws as $rw): ?>
										<option value="<?= $rw->id_rw ?>" <?= (int)$user->id_rw === (int)$rw->id_rw ? 'selected' : '' ?>><?= esc($rw->nama) ?></option>
									<?php endforeach; ?>
								</select>
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