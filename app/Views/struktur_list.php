<?= view('includes/nav-white') ?>

<?php
$__rtNama    = (isset($rt) && $rt !== null) ? $rt->nama : 'RT 29';
$uri         = service('uri');
$__isSlugged = ($uri->getTotalSegments() > 0 && isset($rt->slug) && $uri->getSegment(1) === $rt->slug);
$__homeUrl   = base_url($__isSlugged ? $rt->slug : '');
?>

<section class="page-section bg-light detail-page-section" id="struktur-organisasi" style="min-height: 85vh;">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?= $__homeUrl ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h3 class="section-subheading mb-1 text-muted text-uppercase" style="letter-spacing: 1px;">ORGANISASI</h3>
                <h2 class="section-heading mb-1">Struktur Organisasi <?= esc($__rtNama) ?></h2>
                <p class="text-muted mb-0">Susunan pengurus RW dan RT.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?= view('partials/org_tree', ['tree' => $tree]) ?>
            </div>
        </div>
    </div>
</section>
