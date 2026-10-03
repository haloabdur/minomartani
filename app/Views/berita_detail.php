<?= view('includes/nav-white') ?>

<?php
$__rt           = (isset($rt) && $rt !== null) ? $rt : current_rt();
$__rtNama       = $__rt !== null ? $__rt->nama : 'RT 29';
$uri            = service('uri');
$__isSlugged    = ($uri->getTotalSegments() > 0 && isset($__rt->slug) && $uri->getSegment(1) === $__rt->slug);
$__tenantPrefix = $__isSlugged ? $__rt->slug . '/' : '';
$__homeUrl      = base_url($__isSlugged ? $__rt->slug : '');
?>

<style>
    .article-card {
        border-radius: 1.5rem;
        border: none;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .article-title {
        font-size: 2.15rem;
        font-weight: 700;
        line-height: 1.35;
        color: #212529;
    }
    .article-body {
        font-size: 1.05rem;
        line-height: 1.85;
        color: #333333;
    }
    .article-body p {
        margin-bottom: 1.25rem;
    }
    .article-body img {
        max-width: 100%;
        height: auto;
        border-radius: 1rem;
        margin: 1rem 0;
    }
    .berita-bento {
        display: flex;
        gap: 12px;
        height: 420px;
        margin-bottom: 2rem;
        border-radius: 1.25rem;
        overflow: hidden;
        position: relative;
    }
    .berita-bento-1 {
        height: 460px;
    }
    .berita-bento-item {
        border-radius: 1rem;
        overflow: hidden;
        cursor: zoom-in;
        position: relative;
        background-color: #f1f3f5;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .berita-bento-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.35s ease;
    }
    .berita-bento-item:hover img {
        transform: scale(1.04);
    }
    .berita-bento-hero {
        flex: 0 0 58%;
    }
    .berita-bento-side {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .berita-bento-side .berita-bento-item {
        flex: 1;
    }
    .berita-bento-side-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
    }
    @media (max-width: 768px) {
        .article-title {
            font-size: 1.65rem;
        }
        .berita-bento, .berita-bento-1 {
            height: 280px;
        }
        .berita-bento-hero {
            flex-basis: 55%;
        }
    }
</style>

<section class="page-section bg-light detail-page-section" id="berita-rt" style="min-height: 85vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <!-- Navigation / Back Button -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= base_url($__tenantPrefix . 'berita') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="fa fa-arrow-left me-1"></i> Semua Berita
                        </a>
                        <a href="<?= $__homeUrl ?>#berita" class="btn btn-link btn-sm text-muted text-decoration-none">
                            <i class="fas fa-home me-1"></i> Beranda
                        </a>
                    </div>
                    <div>
                        <span class="badge bg-white text-muted border px-3 py-2 rounded-pill small shadow-sm">
                            <i class="far fa-calendar-alt text-primary me-1"></i> <?= date('d M Y', strtotime($berita->created_time)) ?>
                        </span>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="card article-card p-4 p-md-5">
                    <!-- Categories -->
                    <?php if (!empty($berita->kategori)): ?>
                        <div class="mb-3 d-flex flex-wrap gap-1">
                            <?php foreach (explode(',', $berita->kategori) as $kat): ?>
                                <?php if (trim($kat) !== ''): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                        <i class="fas fa-tag me-1 small"></i><?= esc(trim($kat)) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Title -->
                    <h1 class="article-title mb-4"><?= esc($berita->judul) ?></h1>

                    <!-- Photo Bento Gallery -->
                    <?php
                    $galeri = !empty($fotos)
                        ? array_map(static fn ($f) => foto_url($f->foto), $fotos)
                        : (!empty($berita->foto) ? [foto_url($berita->foto)] : []);
                    ?>

                    <?php if (!empty($galeri)): ?>
                        <?php if (count($galeri) === 1): ?>
                            <div class="berita-bento berita-bento-1 position-relative">
                                <div class="berita-bento-item w-100 h-100" onclick="bukaGaleriBerita(0)">
                                    <img src="<?= $galeri[0] ?>" alt="<?= esc($berita->judul) ?>">
                                </div>
                                <div class="position-absolute bottom-0 end-0 m-3 d-flex align-items-center bg-dark bg-opacity-75 text-white px-3 py-1 rounded-pill small shadow" style="backdrop-filter: blur(4px); pointer-events: none; z-index: 2;">
                                    <i class="fas fa-expand me-2"></i> Klik untuk perbesar
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="berita-bento position-relative">
                                <div class="berita-bento-item berita-bento-hero" onclick="bukaGaleriBerita(0)">
                                    <img src="<?= $galeri[0] ?>" alt="<?= esc($berita->judul) ?>">
                                </div>
                                <div class="berita-bento-side<?= count($galeri) === 5 ? ' berita-bento-side-grid' : '' ?>">
                                    <?php foreach (array_slice($galeri, 1) as $j => $url): $i = $j + 1; ?>
                                        <div class="berita-bento-item" onclick="bukaGaleriBerita(<?= $i ?>)">
                                            <img src="<?= $url ?>" alt="Foto <?= $i ?>">
                                        </div>
                                    <?php endforeach ?>
                                </div>
                                <div class="position-absolute bottom-0 end-0 m-3 d-flex align-items-center bg-dark bg-opacity-75 text-white px-3 py-1 rounded-pill small shadow" style="backdrop-filter: blur(4px); pointer-events: none; z-index: 2;">
                                    <i class="fas fa-images me-2"></i> <?= count($galeri) ?> Foto &bull; Klik untuk perbesar
                                </div>
                            </div>
                        <?php endif ?>

                        <!-- Lightbox Modal -->
                        <div class="modal fade" id="galeriBeritaModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                <div class="modal-content bg-dark border-0 rounded-4 overflow-hidden">
                                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal" aria-label="Close"></button>
                                    <div id="galeriBeritaCarousel" class="carousel slide">
                                        <div class="carousel-inner">
                                            <?php foreach ($galeri as $i => $url): ?>
                                                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                                    <img src="<?= $url ?>" class="d-block w-100" style="max-height: 85vh; object-fit: contain;" alt="Gambar <?= $i + 1 ?>">
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

                        <script>
                            function bukaGaleriBerita(index) {
                                var carouselEl = document.getElementById('galeriBeritaCarousel');
                                var carousel = bootstrap.Carousel.getInstance(carouselEl) || new bootstrap.Carousel(carouselEl);
                                carousel.to(index);

                                var modalEl = document.getElementById('galeriBeritaModal');
                                var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                modal.show();
                            }
                        </script>
                    <?php endif ?>

                    <!-- Article Body -->
                    <div class="article-body">
                        <?= $berita->deskripsi ?>
                    </div>

                    <!-- Source & Attachment Info -->
                    <?php if (!empty($berita->sumber) || !empty($berita->lampiran)): ?>
                        <div class="mt-4 pt-4 border-top">
                            <?php if (!empty($berita->sumber)): ?>
                                <div class="mb-3 p-3 bg-light rounded-3 d-flex align-items-center">
                                    <i class="fas fa-link text-primary me-2"></i>
                                    <span class="small text-muted me-2">Sumber:</span>
                                    <a target="_blank" href="<?= esc($berita->sumber) ?>" class="small fw-semibold text-break"><?= esc($berita->sumber) ?></a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($berita->lampiran)): ?>
                                <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= base_url('public/img/pdf.svg') ?>" width="32" class="me-3" alt="PDF">
                                        <div>
                                            <div class="fw-bold small text-dark">Dokumen Lampiran</div>
                                            <div class="text-muted small">Unduh berkas terkait berita ini</div>
                                        </div>
                                    </div>
                                    <a target="_blank" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" href="<?= esc($berita->lampiran) ?>" download="">
                                        <i class="fas fa-download me-1"></i> Unduh Berkas
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bottom Nav / Share -->
                    <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <a href="<?= base_url($__tenantPrefix . 'berita') ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                            <i class="fa fa-arrow-left me-1"></i> Kembali ke Daftar Berita
                        </a>
                        <a href="<?= $__homeUrl ?>" class="btn btn-light rounded-pill px-4 py-2 text-muted fw-semibold">
                            <i class="fas fa-home me-1"></i> Halaman Utama
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>