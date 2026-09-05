<?= view('includes/nav-white') ?>

<section class="page-section" id="papan-informasi-detail">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="row py-3">
                    <div class="col"><a href="<?= base_url() ?>#papan-informasi"><i class="fa fa-arrow-left"></i> &nbsp;Kembali</a></div>
                    <div class="col text-end text-muted"><i class="fas fa-clock"></i> &nbsp;<?= date('d-m-Y', strtotime($papan->timestamp)) ?></div>
                </div>
                <div class="card border-0">
                    <div class="card-body px-0">
                        <h2 class="py-2"><?= $papan->judul ?></h2>
                        <p class="text-dark"><?= $papan->isi ?></p>
                        <?php if (!empty($papan->lampiran)): ?>
                            <p class="text-muted">Lihat Lampiran</p>
                            <a target="_blank" class="text-dark" href="<?= $papan->lampiran ?>"><img src="<?= base_url('public/img/pdf.svg') ?>" width="24" download=""> &nbsp; Lampiran</a>
                        <?php endif ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
