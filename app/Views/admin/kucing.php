<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <h3 class="card-title font-weight-bold text-dark mb-0">
                            <i class="fas fa-cat text-primary mr-2"></i> Daftar Kucing Terdaftar
                        </h3>
                        <a href="<?= base_url('admin/kucing/add') ?>" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Kucing
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable align-middle">
                            <thead>
                                <tr>
                                    <th width="1" class="text-center">No.</th>
                                    <th width="80" class="text-center">Foto</th>
                                    <th>Nama Kucing</th>
                                    <th>Pemilik</th>
                                    <th>Ciri-ciri Fisik</th>
                                    <th class="text-center">Kelamin</th>
                                    <th class="text-center">Steril</th>
                                    <th class="text-center">Vaksin</th>
                                    <th width="90" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $adminOwnerCounts = [];
                                foreach ($kucings as $item) {
                                    $oName = !empty($item->nama_warga) ? trim($item->nama_warga) : 'Tanpa Pemilik';
                                    $adminOwnerCounts[$oName] = ($adminOwnerCounts[$oName] ?? 0) + 1;
                                }
                                ?>
                                <?php foreach ($kucings as $i => $k): ?>
                                    <?php
                                    $ownerName = !empty($k->nama_warga) ? trim($k->nama_warga) : 'Tanpa Pemilik';
                                    $catCount  = $adminOwnerCounts[$ownerName] ?? 1;
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $i + 1 ?></td>
                                        <td class="text-center align-middle">
                                            <?php if (!empty($k->foto)): ?>
                                                <img src="<?= foto_url($k->foto, 'kucing') ?>" alt="<?= esc($k->nama) ?>" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" class="rounded border shadow-sm" onclick="showImage('<?= foto_url($k->foto, 'kucing') ?>', '<?= esc($k->nama, 'js') ?>')">
                                            <?php else: ?>
                                                <span class="badge badge-light border text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle font-weight-bold"><?= esc($k->nama) ?></td>
                                        <td class="align-middle">
                                            <span class="text-dark font-weight-bold"><?= esc($k->nama_warga ?? '-') ?></span>
                                            <span class="badge badge-light border text-primary ml-1" title="Pemilik memiliki total <?= $catCount ?> kucing terdaftar"><i class="fas fa-paw mr-1"></i><?= $catCount ?> kucing</span>
                                            <?php if (!empty($k->alamat)): ?>
                                                <br><span class="text-muted small"><i class="fas fa-map-marker-alt text-danger mr-1"></i><?= esc($k->alamat) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle"><?= esc($k->warna_ciri ?? '-') ?></td>
                                        <td class="text-center align-middle">
                                            <?php if (($k->jenis_kelamin ?? '') === 'jantan'): ?>
                                                <span class="badge badge-primary"><i class="fas fa-mars mr-1"></i>Jantan</span>
                                            <?php elseif (($k->jenis_kelamin ?? '') === 'betina'): ?>
                                                <span class="badge badge-danger"><i class="fas fa-venus mr-1"></i>Betina</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?= $k->steril ? '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Ya</span>' : '<span class="badge badge-light border text-muted">Belum</span>' ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?= $k->vaksin ? '<span class="badge badge-info"><i class="fas fa-shield-alt mr-1"></i>Ya</span>' : '<span class="badge badge-light border text-muted">Belum</span>' ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a class="btn btn-sm btn-outline-info mr-1" href="<?= base_url('admin/kucing/edit/' . $k->id_kucing) ?>" title="Ubah Data">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            <a class="btn btn-sm btn-outline-danger" href="<?= base_url('admin/kucing/delete/' . $k->id_kucing) ?>" onclick="return confirm('Apakah anda yakin ingin menghapus data ini?')" title="Hapus Data">
                                                <i class="far fa-trash-alt"></i>
                                            </a>
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

<!-- Modal for Image Preview -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Preview Foto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Preview">
            </div>
        </div>
    </div>
</div>

<script>
    function showImage(src, title) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModalLabel').innerText = title;
        $('#imageModal').modal('show');
    }
</script>
