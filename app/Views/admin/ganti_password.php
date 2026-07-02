<div class="container-fluid">
	<div class="row justify-content-center">
		<div class="col-4">
			<div class="card">
				<div class="card-body">
          <?php // form helper loaded in BaseController
            echo form_open('admin/login/update_password') ?>
              <div class="form-group">
                <label class="sr-only">Password Lama</label>
              <input type="password" name="password" class="form-control" placeholder="Password Lama Admin" required autofocus>
              </div>
              <div class="form-group">
                <label class="sr-only">Password Baru</label>
                <input type="password" name="npassword" class="form-control" placeholder="Password Baru Admin" required>
              </div>
              <button class="btn btn-lg btn-primary btn-block" type="submit">Ganti Password</button>
              <p class="mt-5 mb-3 text-muted">&copy; Copyright <a href="<?php echo base_url() ?>">RT 29 Minomartani</a> <?php echo date('Y') ?></p>
            </form>
				</div>
			</div>
		</div>
	</div>
</div>