<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/users/store') ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Username</label>
									<input type="text" name="username" class="form-control" placeholder="Username" required>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Email</label>
									<input type="email" name="email" class="form-control" placeholder="Email" required>
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Password</label>
									<input type="password" name="password" class="form-control" placeholder="Password" required>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Ulangi Password</label>
									<input type="password" name="cpassword" class="form-control" placeholder="Ulangi Password" required>
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
											<option value="<?= $rt->id_rt ?>"><?= esc($rt->nama) ?></option>
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
											<option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
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