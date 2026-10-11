<div class="container-fluid">
    <div class="row mb-3">
        <div class="col"><a href="<?php echo base_url('admin/kucing/add') ?>" class="btn btn-primary">Tambah Kucing</a></div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped datatable">
                            <thead>
                                <tr>
                                    <th width="1">No.</th>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Pemilik</th>
                                    <th>Ciri-ciri</th>
                                    <th>Kelamin</th>
                                    <th>Steril</th>
                                    <th>Vaksin</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kucings as $i => $k): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <?php if (!empty($k->foto)): ?>
                                                <img src="<?= foto_url($k->foto, 'kucing') ?>" alt="<?= esc($k->nama) ?>" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="showImage('<?= foto_url($k->foto, 'kucing') ?>', '<?= esc($k->nama, 'js') ?>')">
                                            <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($k->nama) ?></td>
                                        <td>
                                            <?= esc($k->nama_warga ?? '-') ?>
                                            <?php if (!empty($k->alamat)): ?>
                                                <br><span class="text-muted small"><?= esc($k->alamat) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($k->warna_ciri ?? '-') ?></td>
                                        <td><?= esc(ucfirst($k->jenis_kelamin ?? '-')) ?></td>
                                        <td><?= $k->steril ? 'Ya' : 'Belum' ?></td>
                                        <td><?= $k->vaksin ? 'Ya' : 'Belum' ?></td>
                                        <td>
                                            <a class="text-success mr-1" href="<?= base_url('admin/kucing/edit/' . $k->id_kucing) ?>">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            <a class="text-danger" href="<?= base_url('admin/kucing/delete/' . $k->id_kucing) ?>" onclick="return confirm('Apakah anda yakin ingin menghapus data ini?')">
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
