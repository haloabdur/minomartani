<?php
$__rt           = (isset($rt) && $rt !== null) ? $rt : current_rt();
$uri            = service('uri');
$__isSlugged    = ($uri->getTotalSegments() > 0 && isset($__rt->slug) && $uri->getSegment(1) === $__rt->slug);
$__homeUrl      = base_url($__isSlugged ? $__rt->slug : '');
?>

<!-- Fixed Navigation with Solid White Background for Detail/Secondary Pages -->
<style>
    #mainNav {
        background-color: #ffffff !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    #mainNav .navbar-brand {
        color: #212529 !important;
    }
    #mainNav .nav-link {
        color: #495057 !important;
        font-weight: 600;
    }
    #mainNav .nav-link:hover {
        color: #1A75CF !important;
    }
    #mainNav .navbar-toggler {
        color: #212529 !important;
        border-color: rgba(0, 0, 0, 0.15) !important;
    }

    /* Global spacing rule for all detail and secondary pages under fixed header */
    .detail-page-section,
    .page-section {
        padding-top: 9rem !important;
        padding-bottom: 5rem !important;
    }
    @media (max-width: 991px) {
        .detail-page-section,
        .page-section {
            padding-top: 7rem !important;
            padding-bottom: 3.5rem !important;
        }
    }
    @media (max-width: 576px) {
        .detail-page-section,
        .page-section {
            padding-top: 6.25rem !important;
            padding-bottom: 3rem !important;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="<?= $__homeUrl ?>"><img src="<?= base_url('public/home/') ?>assets/img/logo-dark.png" alt="Logo" /></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            Menu
            <i class="fas fa-bars ms-1"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav text-uppercase ms-auto py-4 py-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= $__homeUrl ?>#tentang-kami">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $__homeUrl ?>#berita">Berita</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $__homeUrl ?>#papan-informasi">Pengumuman</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $__homeUrl ?>#hubungi-kami">Hubungi Kami</a></li>
            </ul>
        </div>
    </div>
</nav>