<div class="container-fluid">
    <div class="row">
        <!-- RW list card -->
        <div class="col-md-6">
            <div class="card card-secondary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar RW</h3>
                    <a href="<?= base_url('admin/tenants/add-rw') ?>" class="btn btn-sm btn-light ml-auto">Tambah RW</a>
                </div>
                <div class="card-body p-0">
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

        <!-- RT list card -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar RT</h3>
                    <a href="<?= base_url('admin/tenants/add-rt') ?>" class="btn btn-sm btn-light ml-auto">Tambah RT</a>
                </div>
                <div class="card-body p-0">
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
