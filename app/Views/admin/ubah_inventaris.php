<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo base_url('admin/inventaris/update/' . $item->id) ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="nama_barang">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" id="nama_barang" value="<?php echo $item->nama_barang ?>" placeholder="Nama Barang" required>
                        </div>
                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" class="form-control" name="stok" id="stok" value="<?php echo $item->stok ?>" placeholder="Stok" required>
                        </div>
                        <div class="form-group">
                            <label for="foto">Foto</label>
                            <br>
                            <?php if (!empty($item->foto)): ?>
                                <div id="current-image">
                                    <img src="<?php echo base_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 100px; height: 100px; object-fit: cover;" class="mb-2">
                                    <p class="small text-muted">Foto saat ini</p>
                                </div>
                            <?php endif; ?>

                            <input type="file" class="form-control" name="foto" id="foto" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>

                            <div class="mt-2" style="display: none;" id="preview-container">
                                <p class="small text-muted mb-1">Preview Foto Baru:</p>
                                <img id="preview" src="" alt="Preview Foto" class="img-thumbnail" style="max-height: 200px;">
                                <div id="file-info" class="mt-1 text-muted small"></div>
                            </div>
                        </div>

                        <script>
                            function previewImage(input) {
                                var previewContainer = document.getElementById('preview-container');
                                var preview = document.getElementById('preview');
                                var fileInfo = document.getElementById('file-info');

                                if (input.files && input.files[0]) {
                                    var reader = new FileReader();
                                    var file = input.files[0];

                                    reader.onload = function(e) {
                                        preview.src = e.target.result;
                                        previewContainer.style.display = 'block';
                                    }

                                    reader.readAsDataURL(file);

                                    var size = (file.size / 1024).toFixed(2);
                                    fileInfo.innerHTML = 'File: ' + file.name + ' (' + size + ' KB) - ' + file.type;
                                } else {
                                    preview.src = "";
                                    previewContainer.style.display = 'none';
                                    fileInfo.innerHTML = "";
                                }
                            }
                        </script>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>