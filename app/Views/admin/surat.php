<?php

use App\Libraries\SuratPemohon;
use App\Models\SuratModel;
?>
<div class="container-fluid">
	<!-- Permohonan surat masuk lewat form Layanan publik; admin meninjau, menyetujui atau menolak di sini. -->
	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
					<table class="table table-bordered table-striped datatable" data-order='[]'>
						<thead>
							<tr>
								<th width="1">No.</th>
								<th>Pemohon</th>
								<th>Tujuan</th>
								<th>Data</th>
								<th>Tanggal</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($surats as $i => $surat):
								$nama  = SuratPemohon::hasSnapshot($surat) ? $surat->nama_pemohon : $surat->rt_nama;
								$hp    = SuratPemohon::normalizePhone(SuratPemohon::hasSnapshot($surat) ? $surat->no_hp : $surat->rt_no_hp);
								$beda  = SuratPemohon::jumlahBeda($surat);
							?>
							<tr>
								<td><?= $i + 1 ?></td>
								<td>
									<?= esc($nama) ?> <br>
									<?php if ($hp !== ''): ?>
										<a target="_blank" href="https://wa.me/62<?= esc($hp) ?>"> +62<?= esc($hp) ?></a>
									<?php endif ?>
								</td>
								<td>
									<span class="text-muted">Maksud : </span><?= esc($surat->maksut) ?> <br>
									<span class="text-muted">Perlu : </span><?= esc($surat->perlu) ?> <br>
									<span class="small text-muted"><i class="fas fa-file-alt"></i> &nbsp;<?= esc(implode(', ', SuratPemohon::parseLampiran($surat->lampiran))) ?></span>
								</td>
								<td>
									<?php if (! SuratPemohon::hasSnapshot($surat)): ?>
										<span class="badge badge-secondary">Data lama</span>
									<?php elseif ($beda > 0): ?>
										<span class="badge badge-danger"><?= $beda ?> data tidak sama dengan data RT</span>
									<?php else: ?>
										<span class="badge badge-success">Sama dengan data RT</span>
									<?php endif ?>
								</td>
								<td><?= date('d-m-Y', strtotime($surat->created_at)) ?> <br> <small class="text-muted"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($surat->created_at)) ?></small></td>
								<td>
									<?php if ((int) $surat->status_surat === SuratModel::STATUS_DISETUJUI): ?>
										<span class="badge badge-success">Disetujui</span>
									<?php elseif ((int) $surat->status_surat === SuratModel::STATUS_DITOLAK): ?>
										<span class="badge badge-dark">Ditolak</span>
									<?php else: ?>
										<span class="badge badge-warning">Menunggu</span>
									<?php endif ?>
								</td>
								<td width="120">
									<a class="btn btn-primary btn-sm" href="<?= base_url('admin/surat/view/' . $surat->id_surat) ?>">Periksa</a>
									<?php if ((int) $surat->status_surat === SuratModel::STATUS_DISETUJUI): ?>
										<p class="text-muted small mb-0 mt-1"><i>Disetujui : <br><?= esc($surat->timestamp) ?></i></p>
									<?php endif ?>
								</td>
							</tr>
							<?php endforeach ?>
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
