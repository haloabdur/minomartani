<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo base_url('admin/kucing/store') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?= view('admin/_form_kucing', ['pemilik' => $pemilik]) ?>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
