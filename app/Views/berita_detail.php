<?= view('includes/nav-white') ?>

<section class="page-section" id="berita-rt">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="row py-3">
                    <div class="col"><a href="<?= base_url() ?>#berita"><i class="fa fa-arrow-left"></i> &nbsp;Kembali</a></div>
                    <div class="col text-end text-muted"><i class="fas fa-clock"></i> &nbsp;<?= date('d-m-Y', strtotime($berita->timestamp)) ?></div>
                </div>
                <div class="card border-0">

                    <?php if (!empty($fotos)): ?>
                        <?php if (count($fotos) === 1): ?>
                            <img src="<?= foto_url($fotos[0]->foto) ?>" class="card-img-top rounded" alt="Gambar">
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($fotos as $f): ?>
                                    <div class="col-6 col-md-4">
                                        <img src="<?= foto_url($f->foto) ?>" class="img-fluid rounded" alt="Gambar">
                                    </div>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    <?php else: ?>
                        <img src="<?= foto_url($berita->foto) ?>" class="card-img-top rounded" alt="Gambar">
                    <?php endif ?>
                    <div class="card-body px-0">
                        <h2 class="py-2"><?= $berita->judul ?></h2>
                        <p class="text-muted"><?= strtoupper($berita->kategori) ?></p>
                        <p class="text-dark"><?= $berita->deskripsi ?></p>
                        <?php if (!empty($berita->sumber)): ?>
                            <p class="small text-muted"><i>Sumber : <a target="_blank" href="<?= $berita->sumber ?>"><?= explode("/", $berita->sumber)[2] ?></a></i></p>
                        <?php endif ?>
                        <?php if (!empty($berita->lampiran)): ?>
                            <p class="text-muted">Lihat Lampiran</p>
                            <a target="_blank" class="text-dark" href="<?= $berita->lampiran ?>"><img src="<?= base_url('public/img/pdf.svg') ?>" width="24" download=""> &nbsp; Lampiran</a>
                        <?php endif ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>