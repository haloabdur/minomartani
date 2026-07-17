<div class="container-fluid">
	<!-- Permohonan surat masuk lewat form Layanan publik; admin hanya meninjau & menyetujui di sini. -->
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Warga</th>
								<th>Tujuan</th>
								<th>Surat</th>
								<th>Tanggal</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($surats as $i=>$surat) {
							?>
							<tr>
								<td><?php echo $i+1 ?></td>
								<td>
									<?php echo $surat->nama_warga ?> <br>
									<?php if ($surat->no_hp): ?>
										<a target="_blank" href="https://wa.me/62<?php echo $surat->no_hp ?>" alt="Whatsapp"> +62<?php echo $surat->no_hp ?></a>
									<?php endif ?>
								</td>
								<td>
									<span class="text-muted">Maksut : </span><?php echo $surat->maksut ?> <br>
									<span class="text-muted">Perlu : </span><?php echo $surat->perlu ?> <br>
									<span class="small text-muted"><i class="fas fa-file-alt"></i> &nbsp;<?php echo $surat->lampiran ?></span>
								</td>
								<td> <a href="<?php echo base_url('admin/surat/view/'.$surat->id_surat) ?>"><img src="<?php echo base_url('public/img/pdf.svg') ?>" width="48" ></a> </td>
								<td><?php echo date('d-m-Y', strtotime($surat->created_at)) ?> <br> <small class="text-muted"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($surat->created_at)) ?></small></td>
								<td><?php echo $surat->status_surat == 1 ? '<label class="badge badge-pills badge-success">Disetujui</label>' : '<label class="badge badge-pills badge-danger">Belum Disetujui</label>' ?></td>
								<td width="80">
									<?php if ($surat->status_surat == 0) { ?>
										<a onclick="return confirm('Apakah Anda yakin akan mensetujui lampiran ini?')" class="btn btn-success btn-sm" href="<?php echo base_url('admin/surat/setuju/'.$surat->id_surat) ?>">
										Setujui
									</a>
									<?php } else { ?>
										<p class="text-muted small"><i>Disetujui : <br><?php echo $surat->timestamp ?></i></p>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					</div>
				</div>
				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>
	</div>
	<!-- /.row -->
</div><!-- /.container-fluid -->