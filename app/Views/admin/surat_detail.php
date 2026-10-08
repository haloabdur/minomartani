<?php

use App\Models\SuratModel;

$status = (int) $surat->status_surat;
$beda   = count(array_filter($banding, static fn ($r) => ! $r['sama']));
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-8">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Pengajuan #<?= (int) $surat->id_surat ?> &middot; <?= date('d-m-Y H:i', strtotime($surat->created_at)) ?></h3>
					<div class="card-tools">
						<?php if ($status === SuratModel::STATUS_DISETUJUI): ?>
							<span class="badge badge-success">Disetujui</span>
						<?php elseif ($status === SuratModel::STATUS_DITOLAK): ?>
							<span class="badge badge-dark">Ditolak</span>
						<?php else: ?>
							<span class="badge badge-warning">Menunggu</span>
						<?php endif ?>
					</div>
				</div>
				<div class="card-body">
					<?php if (empty($banding)): ?>
						<div class="alert alert-secondary">Pengajuan lama: tidak ada salinan data pemohon untuk dibandingkan. Yang ditampilkan adalah data warga di RT.</div>
					<?php elseif ($beda > 0): ?>
						<div class="alert alert-danger"><?= $beda ?> isian pemohon tidak sama dengan data RT. Periksa sebelum menyetujui atau mencetak.</div>
					<?php else: ?>
						<div class="alert alert-success">Semua isian pemohon sama dengan data RT.</div>
					<?php endif ?>

					<?php if (! empty($banding)): ?>
					<div class="table-responsive">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th>Data</th>
									<th>Diisi pemohon</th>
									<th>Data RT</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($banding as $row): ?>
									<tr class="<?= $row['sama'] ? '' : 'table-danger' ?>">
										<td><?= esc($row['label']) ?></td>
										<td>
											<?= esc($row['pemohon']) ?>
											<?php if (! $row['sama']): ?>
												<br><span class="badge badge-danger">Tidak sama dengan data RT</span>
											<?php endif ?>
										</td>
										<td><?= esc($row['rt']) ?></td>
									</tr>
								<?php endforeach ?>
							</tbody>
						</table>
					</div>
					<?php endif ?>

					<dl class="row mb-0">
						<dt class="col-sm-3">Permohonan</dt>
						<dd class="col-sm-9"><?= esc($surat->maksut) ?></dd>
						<dt class="col-sm-3">Untuk keperluan</dt>
						<dd class="col-sm-9"><?= esc($surat->perlu) ?></dd>
						<dt class="col-sm-3">Lampiran</dt>
						<dd class="col-sm-9">
							<?php if (empty($lampiran)): ?>
								<span class="text-muted">-</span>
							<?php else: ?>
								<ol class="pl-3 mb-0">
									<?php foreach ($lampiran as $item): ?>
										<li><?= esc($item) ?></li>
									<?php endforeach ?>
								</ol>
							<?php endif ?>
						</dd>
						<?php if ($status === SuratModel::STATUS_DITOLAK): ?>
							<dt class="col-sm-3">Alasan ditolak</dt>
							<dd class="col-sm-9"><?= esc($surat->alasan_tolak) ?></dd>
						<?php endif ?>
					</dl>
				</div>
				<div class="card-footer">
					<a href="<?= base_url('admin/surat') ?>" class="btn btn-light">Kembali</a>
					<?php if ($status !== SuratModel::STATUS_DITOLAK): ?>
						<a href="<?= base_url('admin/surat/cetak/' . $surat->id_surat) ?>" class="btn btn-primary"><i class="fas fa-print"></i> Cetak Surat</a>
					<?php endif ?>
				</div>
			</div>
		</div>

		<?php if ($status === SuratModel::STATUS_MENUNGGU): ?>
		<div class="col-lg-4">
			<div class="card">
				<div class="card-header"><h3 class="card-title">Keputusan RT</h3></div>
				<div class="card-body">
					<?= form_open('admin/surat/setuju/' . $surat->id_surat, ['onsubmit' => "return confirm('Apakah Anda yakin akan menyetujui pengajuan ini?')"]) ?>
						<button type="submit" class="btn btn-success btn-block">Setujui</button>
					<?= form_close() ?>

					<hr>

					<?= form_open('admin/surat/tolak/' . $surat->id_surat) ?>
						<div class="form-group">
							<label for="alasan_tolak">Alasan penolakan</label>
							<textarea id="alasan_tolak" name="alasan_tolak" class="form-control" rows="3" maxlength="255" required></textarea>
						</div>
						<button type="submit" class="btn btn-outline-danger btn-block">Tolak</button>
					<?= form_close() ?>
				</div>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
