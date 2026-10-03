<?= view('includes/nav-white') ?>

<?php
$__rtNama       = (isset($rt) && $rt !== null) ? $rt->nama : 'RT 29';
$uri            = service('uri');
$__isSlugged    = ($uri->getTotalSegments() > 0 && isset($rt->slug) && $uri->getSegment(1) === $rt->slug);
$__beritaPrefix = $__isSlugged ? $rt->slug . '/' : '';
$__homeUrl      = base_url($__isSlugged ? $rt->slug : '');
?>

<style>
    .berita-card {
        border-radius: 1.25rem;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: none;
        background: #ffffff;
    }
    .berita-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 32px rgba(26, 117, 207, 0.12) !important;
    }
    .berita-img-wrapper {
        position: relative;
        height: 210px;
        overflow: hidden;
        background-color: #f1f3f5;
    }
    .berita-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .berita-card:hover .berita-img {
        transform: scale(1.05);
    }
    .berita-title {
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.45;
        color: #212529;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .berita-excerpt {
        font-size: 0.9rem;
        color: #6c757d;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<section class="page-section bg-light" id="daftar-berita" style="min-height: 85vh;">
    <div class="container">
        <!-- Breadcrumb / Back button -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?= $__homeUrl ?>#berita" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Header & Search Bar -->
        <div class="row align-items-end mb-4 g-3">
            <div class="col-lg-7 col-md-6">
                <h3 class="section-subheading mb-1 text-muted text-uppercase" style="letter-spacing: 1px;">KABAR LINGKUNGAN</h3>
                <h2 class="section-heading mb-1">Berita <?= esc($__rtNama) ?></h2>
                <p class="text-muted mb-0">Semua informasi terkini, agenda, dan pengumuman warga.</p>
            </div>
            <div class="col-lg-5 col-md-6">
                <form method="get" action="<?= current_url() ?>">
                    <div class="input-group shadow-sm" style="border-radius: 2rem; overflow: hidden; background: #fff;">
                        <input type="text" name="cari" class="form-control border-0 px-4 py-2" placeholder="Cari judul berita..." value="<?= esc($cari ?? '') ?>" aria-label="Cari judul berita">
                        <button class="btn btn-primary px-4" type="submit" aria-label="Cari">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($cari)): ?>
                            <a href="<?= current_url() ?>" class="btn btn-light px-3 d-flex align-items-center" title="Reset pencarian" aria-label="Reset">
                                <i class="fas fa-times text-muted"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($cari)): ?>
            <div class="alert alert-info py-2 px-3 mb-4 rounded-3 d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-info-circle me-1"></i>
                    Menampilkan hasil pencarian untuk: <strong>"<?= esc($cari) ?>"</strong>
                </div>
                <a href="<?= current_url() ?>" class="text-decoration-none small fw-bold">Reset Pencarian</a>
            </div>
        <?php endif; ?>

        <!-- Berita Grid -->
        <div class="row g-4 justify-content-start">
            <?php if (empty($beritas)): ?>
                <div class="col-12 text-center py-5">
                    <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
                        <div class="my-3">
                            <i class="far fa-newspaper fa-4x text-muted mb-3 opacity-50"></i>
                            <h4 class="text-secondary fw-bold">Belum Ada Berita Ditemukan</h4>
                            <?php if (!empty($cari)): ?>
                                <p class="text-muted">Tidak ada berita yang sesuai dengan kata kunci "<strong><?= esc($cari) ?></strong>".</p>
                                <a href="<?= current_url() ?>" class="btn btn-primary rounded-pill px-4 mt-2">Lihat Semua Berita</a>
                            <?php else: ?>
                                <p class="text-muted">Saat ini belum ada berita atau pengumuman yang dipublikasikan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($beritas as $b): ?>
                    <div class="col-lg-4 col-md-6 d-flex">
                        <div class="card w-100 shadow-sm berita-card d-flex flex-column">
                            <div class="berita-img-wrapper">
                                <?php
                                $fotoCover = !empty($b->foto)
                                    ? foto_url($b->foto)
                                    : base_url('public/home/assets/img/tentang-kami.png');
                                ?>
                                <img src="<?= $fotoCover ?>" alt="<?= esc($b->judul) ?>" class="berita-img" loading="lazy">
                            </div>
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <div class="text-muted small mb-2 d-flex align-items-center">
                                    <i class="far fa-calendar-alt me-1 text-primary"></i>
                                    <span><?= !empty($b->created_time) ? date('d M Y', strtotime($b->created_time)) : '-' ?></span>
                                </div>
                                <h3 class="berita-title mb-2">
                                    <?= esc($b->judul) ?>
                                </h3>
                                <?php
                                $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags($b->deskripsi ?? '')));
                                $excerpt   = mb_strlen($cleanText) > 130 ? mb_substr($cleanText, 0, 127) . '...' : $cleanText;
                                ?>
                                <p class="berita-excerpt mb-4 flex-grow-1">
                                    <?= esc($excerpt) ?>
                                </p>
                                <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                                    <span class="text-primary fw-bold small">
                                        Baca Selengkapnya <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                </div>
                                <a class="stretched-link" href="<?= base_url($__beritaPrefix . 'berita/' . $b->slug) ?>" aria-label="Baca berita <?= esc($b->judul) ?>"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pager) && $pager->getPageCount() > 1): ?>
            <div class="row mt-5">
                <div class="col-12 d-flex justify-content-center">
                    <?= $pager->links('default', 'bootstrap_pagination') ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
