<?= view('includes/nav-white') ?>

<section class="page-section" id="berita-rt">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="row py-3">
                    <div class="col"><a href="<?= base_url() ?>#berita"><i class="fa fa-arrow-left"></i> &nbsp;Kembali</a></div>
                    <div class="col text-end text-muted"><i class="fas fa-clock"></i> &nbsp;<?= date('d-m-Y', strtotime($berita->created_time)) ?></div>
                </div>
                <div class="card border-0">

                    <?php
                        $galeri = !empty($fotos)
                            ? array_map(static fn ($f) => foto_url($f->foto), $fotos)
                            : (!empty($berita->foto) ? [foto_url($berita->foto)] : []);
                    ?>

                    <?php if (!empty($galeri)): ?>
                        <?php if (count($galeri) === 1): ?>
                            <div class="berita-bento berita-bento-1">
                                <div class="berita-bento-item" onclick="bukaGaleriBerita(0)">
                                    <img src="<?= $galeri[0] ?>" alt="Gambar">
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="berita-bento">
                                <div class="berita-bento-item berita-bento-hero" onclick="bukaGaleriBerita(0)">
                                    <img src="<?= $galeri[0] ?>" alt="Gambar">
                                </div>
                                <div class="berita-bento-side<?= count($galeri) === 5 ? ' berita-bento-side-grid' : '' ?>">
                                    <?php foreach (array_slice($galeri, 1) as $j => $url): $i = $j + 1; ?>
                                        <div class="berita-bento-item" onclick="bukaGaleriBerita(<?= $i ?>)">
                                            <img src="<?= $url ?>" alt="Gambar">
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        <?php endif ?>

                        <div class="modal fade" id="galeriBeritaModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                <div class="modal-content bg-dark border-0">
                                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal" aria-label="Close"></button>
                                    <div id="galeriBeritaCarousel" class="carousel slide">
                                        <div class="carousel-inner">
                                            <?php foreach ($galeri as $i => $url): ?>
                                                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                                    <img src="<?= $url ?>" class="d-block w-100" style="max-height: 85vh; object-fit: contain;" alt="Gambar">
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                        <?php if (count($galeri) > 1): ?>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#galeriBeritaCarousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#galeriBeritaCarousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            </button>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <style>
                            .berita-bento { display: flex; gap: 8px; height: 400px; margin-bottom: 1rem; }
                            .berita-bento-1 { height: 480px; }
                            .berita-bento-item { border-radius: 12px; overflow: hidden; cursor: zoom-in; }
                            .berita-bento-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
                            .berita-bento-hero { flex: 0 0 58%; }
                            .berita-bento-side { flex: 1; display: flex; flex-direction: column; gap: 8px; }
                            .berita-bento-side .berita-bento-item { flex: 1; }
                            .berita-bento-side-grid { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
                            @media (max-width: 576px) {
                                .berita-bento, .berita-bento-1 { height: 260px; }
                                .berita-bento-hero { flex-basis: 55%; }
                            }
                        </style>

                        <script>
                            function bukaGaleriBerita(index) {
                                var carousel = bootstrap.Carousel.getOrCreateInstance(document.getElementById('galeriBeritaCarousel'));
                                carousel.to(index);
                                bootstrap.Modal.getOrCreateInstance(document.getElementById('galeriBeritaModal')).show();
                            }
                        </script>
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