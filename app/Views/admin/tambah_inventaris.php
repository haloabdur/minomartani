<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo base_url('admin/inventaris/store') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="nama_barang">Nama Barang</label>
                            <input type="text" class="form-control" name="nama_barang" id="nama_barang" placeholder="Nama Barang" required>
                        </div>
                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" class="form-control" name="stok" id="stok" placeholder="Stok" required>
                        </div>
                        <div class="form-group">
                            <label for="foto">Foto</label>
                            <input type="file" class="form-control" name="foto" id="foto" accept="image/*" onchange="previewImage(this)">
                            <div class="mt-2" style="display: none;" id="preview-container">
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
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>