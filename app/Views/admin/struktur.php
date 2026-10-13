<?php
/**
 * Expects: $rt, $rw (jabatan lists), $hasRw, $rtNama, $tree.
 */
$sections = [
    'rt' => ['judul' => 'Pengurus ' . $rtNama, 'items' => $rt, 'icon' => 'fa-user-tie', 'ada' => true],
    'rw' => ['judul' => 'Pengurus RW', 'items' => $rw, 'icon' => 'fa-landmark', 'ada' => $hasRw],
];
?>
<div class="container-fluid">
    <?php if ($tree['has_data']): ?>
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title font-weight-bold text-dark mb-0">
                            <i class="fas fa-sitemap text-info mr-2"></i> Bagan Struktur Organisasi
                        </h3>
                        <div class="card-tools ml-auto">
                            <a href="<?= base_url((current_rt()->slug ?? 'rt29') . '/struktur') ?>" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-external-link-alt mr-1"></i> Lihat Halaman Publik
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?= view('partials/org_tree', ['tree' => $tree]) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($sections as $level => $sec): ?>
            <div class="col-12">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <h3 class="card-title font-weight-bold text-dark mb-0">
                                <i class="fas <?= $sec['icon'] ?> text-primary mr-2"></i> <?= esc($sec['judul']) ?>
                            </h3>
                            <?php if ($sec['ada']): ?>
                                <a href="<?= base_url('admin/struktur/add/' . $level) ?>" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                                    <i class="fas fa-plus mr-1"></i> Tambah Pengurus <?= strtoupper($level) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$sec['ada']): ?>
                            <p class="text-muted mb-0">RT ini belum terhubung ke RW, jadi struktur RW belum bisa diisi.</p>
                        <?php else: ?>
                            <?php if ($level === 'rw'): ?>
                                <p class="text-muted small">Struktur RW dibagi bersama semua RT dalam RW yang sama. Pengurus boleh dipilih dari warga RT mana pun di RW ini.</p>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped datatable align-middle">
                                    <thead>
                                        <tr>
                                            <th width="1" class="text-center">No.</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th>Atasan</th>
                                            <th class="text-center">Urutan</th>
                                            <th width="110" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sec['items'] as $i => $s): ?>
                                            <tr>
                                                <td class="text-center align-middle"><?= $i + 1 ?></td>
                                                <td class="align-middle font-weight-bold">
                                                    <?= esc($s->nama_warga ?? '-') ?>
                                                    <?php if ($level === 'rw' && !empty($s->nama_rt)): ?>
                                                        <br><span class="text-muted small font-weight-normal"><?= esc($s->nama_rt) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle"><?= esc($s->jabatan) ?></td>
                                                <td class="align-middle"><?= esc($s->jabatan_atasan ?? '-') ?></td>
                                                <td class="text-center align-middle"><?= (int) $s->urutan ?></td>
                                                <td class="text-center align-middle">
                                                    <a class="btn btn-sm btn-outline-info mr-1" href="<?= base_url('admin/struktur/edit/' . $s->id_struktur) ?>" title="Ubah Data">
                                                        <i class="far fa-edit"></i>
                                                    </a>
                                                    <form action="<?= base_url('admin/struktur/delete/' . $s->id_struktur) ?>" method="post" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus Data" onclick="if (confirm('Hapus pengurus ini? Bawahannya akan naik ke atasan jabatan ini.')) { this.form.submit(); }">
                                                            <i class="far fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
