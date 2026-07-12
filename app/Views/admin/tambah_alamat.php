<div class="container-fluid">
	<div class="row">
		<div class="col">
			<!-- general form elements -->
			<div class="card card-primary">
				<!-- form start -->
				<?php // form helper loaded in BaseController
					echo form_open('admin/alamat/store') ?>
					<div class="card-body">

						<?php if (auth()->user()->inGroup('superadmin')): ?>
							<div class="row">
								<div class="col">
									<div class="form-group">
										<label>RW</label>
										<select id="select-rw" class="form-control" required>
											<option value="">-- Pilih RW --</option>
											<?php foreach ($rws as $rw): ?>
												<option value="<?= $rw->id_rw ?>"><?= esc($rw->nama) ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<div class="col">
									<div class="form-group">
										<label>RT</label>
										<select id="select-rt" class="form-control" name="id_rt" required>
											<option value="">-- Pilih RT --</option>
											<?php foreach ($rts as $rt): ?>
												<option value="<?= $rt->id_rt ?>" data-rw="<?= $rt->id_rw ?>"><?= esc($rt->nama) ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
						<?php endif; ?>

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
										<option value="LELE 1">LELE 1</option>
										<option value="LELE 2">LELE 2</option>
										<option value="LELE 3">LELE 3</option>
										<option value="LELE 4">LELE 4</option>
										<option value="LELE 5">LELE 5</option>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	let $rwSelect = $('#select-rw');
	let $rtSelect = $('#select-rt');
	
	if ($rwSelect.length && $rtSelect.length) {
		// Keep a copy of all original RT options
		let allRtOptions = $rtSelect.find('option').clone();
		
		$rwSelect.on('change', function() {
			let selectedRw = $(this).val();
			
			// Empty the RT dropdown except for the placeholder
			$rtSelect.empty().append('<option value="">-- Pilih RT --</option>');
			
			if (selectedRw) {
				// Filter and append matching RT options
				allRtOptions.each(function() {
					let rwId = $(this).data('rw');
					if (rwId == selectedRw) {
						$rtSelect.append($(this).clone());
					}
				});
			} else {
				// If no RW selected, append all RT options
				allRtOptions.each(function() {
					if ($(this).val() !== '') {
						$rtSelect.append($(this).clone());
					}
				});
			}
		});
	}
});
</script>