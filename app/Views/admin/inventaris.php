<div class="container-fluid">
    <div class="row mb-3">
        <div class="col"><a href="<?php echo base_url('admin/inventaris/add') ?>" class="btn btn-primary">Tambah Inventaris</a></div>
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
                                    <th>Nama Barang</th>
                                    <th>Stok</th>
                                    <th>Waktu</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($inventaris as $i => $item) {
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo ($i + 1) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($item->foto)): ?>
                                                <img src="<?php echo base_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="showImage('<?php echo base_url($item->foto); ?>', '<?php echo $item->nama_barang; ?>')">
                                            <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $item->nama_barang ?>
                                        </td>
                                        <td>
                                            <?php echo $item->stok ?>
                                        </td>
                                        <td>
                                            <span class="text-muted small">Created: <?php echo $item->created_at ?></span><br>
                                            <span class="text-muted small">Updated: <?php echo $item->updated_at ?></span>
                                        </td>
                                        <td>
                                            <a class="text-success mr-1" href="<?php echo base_url('admin/inventaris/edit/' . $item->id) ?>">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            <a class="text-danger" href="<?php echo base_url('admin/inventaris/delete/' . $item->id) ?>" onclick="return confirm('Apakah anda yakin ingin menghapus data ini?')">
                                                <i class="far fa-trash-alt"></i>
                                            </a>
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