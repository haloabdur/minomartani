<?php
$__rtNama = (isset($rt) && $rt !== null) ? $rt->nama : 'RT29';
$__rtRw   = (isset($rw) && $rw !== null) ? $rw->nama : null;
$__rtAlamat = (isset($rt) && $rt !== null && !empty($rt->alamat)) ? $rt->alamat : 'Ngaglik, Sleman, Daerah Istimewa Yogyakarta';
$__rtDeskripsi = (isset($rt) && $rt !== null && !empty($rt->deskripsi))
    ? $rt->deskripsi
    : "{$__rtNama} Minomartani adalah bagian dari Desa Minomartani, Kecamatan Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta.";
$__rtWa   = (isset($rt) && $rt !== null && !empty($rt->no_wa)) ? $rt->no_wa : '6283869281843';
$__rtHero = (isset($rt) && $rt !== null && !empty($rt->foto_hero))
    ? base_url('public/rt/' . $rt->foto_hero)
    : base_url('public/home/') . 'assets/img/tentang-kami.png';
?>
<!-- Navigation-->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand" href="#page-top"><img src="<?= base_url('public/home/') ?>assets/img/logo-white.png" alt="..." /></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            Menu
            <i class="fas fa-bars ms-1"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav text-uppercase ms-auto py-4 py-lg-0">
                <li class="nav-item"><a class="nav-link" href="#tentang-kami">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="#berita">Berita RT</a></li>
                <li class="nav-item"><a class="nav-link" href="#ketua-rt">Ketua RT</a></li>
                <li class="nav-item"><a class="nav-link" href="#hubungi-kami">Hubungi Kami</a></li>
            </ul>
        </div>
    </div>
</nav>
<!-- Masthead-->
<header class="masthead">
    <div class="container">
        <div class="masthead-heading">Situs Resmi <br> <?= esc($__rtNama) ?> Minomartani</div>
        <div class="masthead-subheading"><?= esc($__rtAlamat) ?></div>
        <a class="btn btn-primary btn-xl my-3" href="#tentang-kami">Profil Kami</a>
    </div>
</header>

<!-- Layanan -->
<section class="page-section" id="berita">
    <div class="container">
        <div class="row justify-content-center">
            <h2 class="section-heading mb-5 text-center">Berita <?= esc($__rtNama) ?></h2>
            <style>
                .card-img-bg {
                    width: 100%;
                    height: 200px;
                    background-repeat: no-repeat;
                    background-position: center;
                    background-size: cover;
                    border-radius: 1.5rem 1.5rem 0 0;
                }
            </style>
            <?php if (empty($beritas)): ?>
                <p class="text-center text-muted">Belum ada berita.</p>
            <?php endif; ?>
            <?php foreach ($beritas as $berita): ?>
                <div style="border-radius: 1.5rem" class="card col-md-3 mx-2 px-0 shadow-lg">
                    <div class="card-img-bg" style="background-image: url('<?= base_url('public/berita/' . $berita->foto) ?>');"></div>
                    <div class="card-body">
                        <h4 class="py-2"><?= $berita->judul ?></h4>
                        <a class="stretched-link" href="<?= base_url('berita/' . $berita->slug) ?>"></a>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- Tentang Kami -->
<section class="page-section" id="tentang-kami">
    <div class="container">
        <div class="row">
            <div class="col-md-6 py-5">
                <h3 class="section-subheading mb-2 text-muted">TENTANG KAMI</h3>
                <h2 class="section-heading mb-5"><?= esc($__rtNama) ?> Minomartani</h2>

                <p class="text-muted"><?= esc($__rtDeskripsi) ?></p>
                <br>
                <div class="row">
                    <div class="col-12 col-md-5">
                        <a class="btn btn-primary p-3 px-5 d-block" style="border-radius: 3rem" href="https://wa.me/<?= esc($__rtWa) ?>">Hubungi Kami</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <img style="border-radius: 1.5rem" class="img-fluid" src="<?= esc($__rtHero) ?>" alt="<?= esc($__rtNama) ?>" />
            </div>
        </div>
    </div>
</section>
<!-- Statistik -->
<section class="page-section bg-light py-5" id="statistik">
    <div class="container">
        <div class="row">
            <div class="col-md-3 justify-content-center d-flex pb-3">
                <img src="<?= base_url('public/home/') ?>assets/kk-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon">
                <div class="d-flex align-self-center">
                    <h3><?= $kk ?></h3><span class="align-self-center ms-2">KK</span>
                </div>
            </div>

            <div class="col-md-3 justify-content-center d-flex pb-3">
                <img src="<?= base_url('public/home/') ?>assets/pria-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon">
                <div class="d-flex align-self-center">
                    <h3><?= $laki ?></h3><span class="align-self-center ms-2">Pria</span>
                </div>
            </div>

            <div class="col-md-3 justify-content-center d-flex pb-3">
                <img src="<?= base_url('public/home/') ?>assets/wanita-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon">
                <div class="d-flex align-self-center">
                    <h3><?= $perempuan ?></h3><span class="align-self-center ms-2">Wanita</span>
                </div>
            </div>

            <div class="col-md-3 justify-content-center d-flex pb-3">
                <img src="<?= base_url('public/home/') ?>assets/lokasi-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon">
                <div class="d-flex align-self-center">
                    <h3><?= $jml_alamat ?></h3><span class="align-self-center ms-2">Alamat</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Wilayah -->
<section class="page-section" id="lokasi">
    <div class="container">
        <div class="row">
            <div class="col-md-6 py-5">
                <h3 class="section-subheading mb-2 text-muted">WILAYAH KAMI</h3>
                <h2 class="section-heading mb-5">Lokasi <?= esc($__rtNama) ?><?= $__rtRw !== null ? ' / ' . esc($__rtRw) : '' ?></h2>

                <p class="text-muted"><?= esc($__rtAlamat) ?>. Wilayah ini merupakan bagian dari Desa Minomartani, Kecamatan Ngaglik, Kabupaten Sleman.</p>

                <a class="text-primary py-3" target="_blank" href="https://www.google.com/maps/place/Minomartani,+Ngaglik,+Sleman+Regency,+Special+Region+of+Yogyakarta/@-7.7393059,110.4079884,18.75z/data=!4m5!3m4!1s0x2e7a596c429c827f:0x1d71fac6900f38d2!8m2!3d-7.7349434!4d110.405355">Lihat di Google Maps</a>
            </div>
            <div class="col-md-6">
                <img style="border-radius: 1.5rem" class="img-fluid" src="<?= base_url('public/home/') ?>assets/img/wilayah.png" alt="..." />
            </div>
        </div>
    </div>
</section>

<!-- Team-->
<section class="page-section bg-light" id="ketua-rt">
    <div class="container">
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-2 text-muted">KETUA RT</h3>
            <h2 class="section-heading mb-5">Ketua <?= esc($__rtNama) ?></h2>
        </div>

        <div class="row">
            <?php if (empty($ketuas)): ?>
                <p class="text-center text-muted">Data ketua RT belum tersedia.</p>
            <?php endif; ?>
            <?php foreach ($ketuas as $ketua): ?>
                <div class="col-lg-3">
                    <div class="team-member">
                        <img class="rounded-circle" src="<?= base_url('public/home/') ?>assets/img/profile.png" alt="<?= $ketua->nama_ketua ?>" />
                        <h4><?= $ketua->nama_ketua ?></h4>
                        <p class="text-muted"><?= $ketua->mulai ?> - <?= $ketua->selesai ?></p>
                    </div>
                </div>
            <?php endforeach ?>

        </div>

        <?php if (! empty($ketuas)): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <p class="large text-muted">Berikut rekam jejak jajaran Ketua <?= esc($__rtNama) ?> dari waktu ke waktu.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
