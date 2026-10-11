<?= view('includes/nav-white') ?>

<?php
$__rtNama    = (isset($rt) && $rt !== null) ? $rt->nama : 'RT 29';
$uri         = service('uri');
$__isSlugged = ($uri->getTotalSegments() > 0 && isset($rt->slug) && $uri->getSegment(1) === $rt->slug);
$__homeUrl   = base_url($__isSlugged ? $rt->slug : '');
?>

<style>
    .kucing-card { border-radius: 1.25rem; overflow: hidden; border: none; background: #fff; }
    .kucing-img-wrapper { height: 230px; background-color: #f1f3f5; overflow: hidden; }
    .kucing-img { width: 100%; height: 100%; object-fit: cover; }
</style>

<section class="page-section bg-light detail-page-section" id="daftar-kucing" style="min-height: 85vh;">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?= $__homeUrl ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <div class="row align-items-end mb-4 g-3">
            <div class="col-lg-7 col-md-6">
                <h3 class="section-subheading mb-1 text-muted text-uppercase" style="letter-spacing: 1px;">PENDATAAN</h3>
                <h2 class="section-heading mb-1">Kucing Terdaftar <?= esc($__rtNama) ?></h2>
                <p class="text-muted mb-0">Kucing yang tidak ada dalam daftar ini dianggap kucing liar.</p>
            </div>
            <div class="col-lg-5 col-md-6">
                <form method="get" action="<?= current_url() ?>">
                    <div class="input-group shadow-sm" style="border-radius: 2rem; overflow: hidden; background: #fff;">
                        <input type="text" name="cari" class="form-control border-0 px-4 py-2" placeholder="Cari nama kucing..." value="<?= esc($cari ?? '') ?>" aria-label="Cari nama kucing">
                        <button class="btn btn-primary px-4" type="submit" aria-label="Cari"><i class="fas fa-search"></i></button>
                        <?php if (!empty($cari)): ?>
                            <a href="<?= current_url() ?>" class="btn btn-light px-3 d-flex align-items-center" title="Reset pencarian" aria-label="Reset"><i class="fas fa-times text-muted"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($kucings)): ?>
                <div class="col-12 text-center py-5">
                    <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
                        <h4 class="text-secondary fw-bold">Belum Ada Kucing Terdaftar</h4>
                        <?php if (!empty($cari)): ?>
                            <p class="text-muted">Tidak ada kucing dengan nama "<strong><?= esc($cari) ?></strong>".</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($kucings as $k): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex">
                        <div class="card w-100 shadow-sm kucing-card">
                            <div class="kucing-img-wrapper">
                                <?php if (!empty($k->foto)): ?>
                                    <img src="<?= foto_url($k->foto, 'kucing') ?>" alt="<?= esc($k->nama) ?>" class="kucing-img" loading="lazy">
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3">
                                <h5 class="fw-bold mb-1"><?= esc($k->nama) ?></h5>
                                <?php if (!empty($k->warna_ciri)): ?>
                                    <p class="text-muted small mb-1"><?= esc($k->warna_ciri) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($k->jalan)): ?>
                                    <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt me-1 text-primary"></i><?= esc($k->jalan) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
