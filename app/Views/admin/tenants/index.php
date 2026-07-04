<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <a href="<?= base_url('admin/tenants/add-rw') ?>" class="btn btn-secondary"><i class="fas fa-plus mr-1"></i>Tambah RW</a>
        </div>
        <div class="col-md-6">
            <a href="<?= base_url('admin/tenants/add-rt') ?>" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Tambah RT</a>
        </div>
    </div>
    <div class="row">
        <!-- RW list card -->
        <div class="col-md-6">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Daftar RW</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th style="width: 100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rws)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada data RW.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rws as $rw): ?>
                                <tr>
                                    <td><?= esc($rw->nama) ?></td>
                                    <td><code><?= esc($rw->slug) ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $rw->is_aktif ? 'success' : 'danger' ?>">
                                            <?= $rw->is_aktif ? 'Aktif' : 'Non-aktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/tenants/edit-rw/' . $rw->id_rw) ?>" class="btn btn-sm btn-info">Ubah</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RT list card -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Daftar RT</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th style="width: 100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rts)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada data RT.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rts as $rt): ?>
                                <tr>
                                    <td><?= esc($rt->nama) ?></td>
                                    <td><code><?= esc($rt->slug) ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $rt->is_aktif ? 'success' : 'danger' ?>">
                                            <?= $rt->is_aktif ? 'Aktif' : 'Non-aktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/tenants/edit-rt/' . $rt->id_rt) ?>" class="btn btn-sm btn-info">Ubah</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
