<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/alamat/store') ?>
					<div class="card-body">

						<div class="row mt-3">
							<div class="col">
								<div class="form-group">
									<label>Jalan</label>
									<select class="form-control" name="jalan" required="">
										<option value="BANDENG 1">BANDENG 1</option>
										<option value="BANDENG 2">BANDENG 2</option>
										<option value="BANDENG 3">BANDENG 3</option>
										<option value="BANDENG 4">BANDENG 4</option>
										<option value="KAKAP RAYA">KAKAP RAYA</option>
									</select>
								</div>
							</div>

							<div class="col">
								<div class="form-group">
									<label>Nomor</label>
									<input type="number" name="nomor" class="form-control" placeholder="Nomor" min="1" required>
								</div>
							</div>
						</div>
					</div>
					<!-- /.card-body -->

					<div class="card-footer">
						<a href="<?php echo base_url('admin/alamat') ?>" class="btn btn-light">Kembali</a>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
			<!-- /.card -->
		</div>
	</div>
</div>