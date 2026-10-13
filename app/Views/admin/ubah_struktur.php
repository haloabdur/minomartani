<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <h3 class="card-title font-weight-bold text-dark mb-0">
                            <i class="fas fa-edit text-primary mr-2"></i> Ubah Pengurus <?= strtoupper($level) ?>
                        </h3>
                        <a href="<?= base_url('admin/struktur') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Struktur Organisasi
                        </a>
                    </div>
                </div>

                <form action="<?= base_url('admin/struktur/update/' . $item->id_struktur) ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="card-body">
                        <?= view('admin/_form_struktur', ['level' => $level, 'warga' => $warga, 'atasan' => $atasan, 'item' => $item]) ?>
                    </div>
                    <div class="card-footer bg-white border-top py-3">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <a href="<?= base_url('admin/struktur') ?>" class="btn btn-default px-4">
                                <i class="fas fa-times mr-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-5 font-weight-bold shadow-sm">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
