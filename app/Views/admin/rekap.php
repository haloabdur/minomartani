<div class="container-fluid">
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<table class="table table-bordered table-striped datatable">
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>RT</th>
								<th>Jumlah Warga</th>
								<th>Jumlah KK</th>
								<th>Laki-laki</th>
								<th>Perempuan</th>
								<th>Surat</th>
								<th>Detail</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rekap as $i => $rt): ?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td><?= esc($rt->nama) ?></td>
								<td><?= $rt->jml_warga ?></td>
								<td><?= $rt->jml_kk ?></td>
								<td><?= $rt->jml_l ?></td>
								<td><?= $rt->jml_p ?></td>
								<td><?= $rt->jml_surat ?></td>
								<td>
									<a href="<?= base_url('admin/rekap/warga/' . $rt->id_rt) ?>">
										<i class="far fa-eye"></i> Lihat Warga
									</a>
								</td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
