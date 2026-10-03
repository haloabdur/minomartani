<?= view('includes/nav-white') ?>

<?php
$__rt           = (isset($rt) && $rt !== null) ? $rt : current_rt();
$__rtNama       = $__rt !== null ? $__rt->nama : 'RT 29';
$uri            = service('uri');
$__isSlugged    = ($uri->getTotalSegments() > 0 && isset($__rt->slug) && $uri->getSegment(1) === $__rt->slug);
$__homeUrl      = base_url($__isSlugged ? $__rt->slug : '');
?>

<style>
    .papan-detail-card {
        border-radius: 1.5rem;
        border: none;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .papan-detail-title {
        font-size: 2.15rem;
        font-weight: 700;
        line-height: 1.35;
        color: #212529;
    }
    .papan-detail-body {
        font-size: 1.05rem;
        line-height: 1.85;
        color: #333333;
    }
    .papan-detail-body p {
        margin-bottom: 1.25rem;
    }
    @media (max-width: 768px) {
        .papan-detail-title {
            font-size: 1.65rem;
        }
    }
</style>

<section class="page-section bg-light detail-page-section" id="papan-informasi-detail" style="min-height: 85vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <!-- Navigation / Back Button -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <a href="<?= $__homeUrl ?>#papan-informasi" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="fa fa-arrow-left me-1"></i> Kembali ke Papan Informasi
                    </a>
                    <?php if (!empty($papan->timestamp)): ?>
                        <span class="badge bg-white text-muted border px-3 py-2 rounded-pill small shadow-sm">
                            <i class="far fa-clock text-primary me-1"></i> <?= date('d M Y', strtotime($papan->timestamp)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Main Content Card -->
                <div class="card papan-detail-card p-4 p-md-5">
                    <!-- Badges -->
                    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-warning text-dark border px-3 py-1 rounded-pill small">
                            <i class="fas fa-bullhorn me-1"></i> Himbauan &amp; Tata Tertib
                        </span>
                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill small">
                            <?= esc($__rtNama) ?> Minomartani
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="papan-detail-title mb-4"><?= esc($papan->judul) ?></h1>

                    <!-- Announcement Body -->
                    <div class="papan-detail-body mb-4">
                        <?= $papan->isi ?>
                    </div>

                    <!-- Attachment (if any) -->
                    <?php if (!empty($papan->lampiran)): ?>
                        <div class="mt-4 pt-4 border-top">
                            <h5 class="fw-bold mb-3 small text-muted text-uppercase" style="letter-spacing: 1px;">
                                <i class="fas fa-paperclip me-2 text-primary"></i>Berkas Lampiran
                            </h5>
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center">
                                    <img src="<?= base_url('public/img/pdf.svg') ?>" width="32" class="me-3" alt="PDF">
                                    <div>
                                        <div class="fw-bold small text-dark">Unduh Dokumen</div>
                                        <div class="text-muted small">Lampiran resmi dari pengurus RT</div>
                                    </div>
                                </div>
                                <a target="_blank" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" href="<?= esc($papan->lampiran) ?>" download="">
                                    <i class="fas fa-download me-1"></i> Unduh Lampiran
                                </a>
                            </div>
                        </div>
                    <?php endif ?>

                    <!-- Bottom Nav -->
                    <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <a href="<?= $__homeUrl ?>#papan-informasi" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                            <i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda
                        </a>
                        <span class="text-muted small">
                            Diterbitkan oleh Pengurus <?= esc($__rtNama) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
