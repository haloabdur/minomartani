<!-- Scoped Design System Styles -->
<style>
    .section-subheading {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #6c757d;
        text-transform: uppercase;
    }
    .section-heading {
        font-size: 2.25rem;
        font-weight: 700;
        color: #212529;
    }

    /* RT Card Styles */
    .rt-card {
        border-radius: 1.25rem;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: none;
        background: #ffffff;
    }
    .rt-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 32px rgba(26, 117, 207, 0.12) !important;
    }
    .rt-img-wrapper {
        position: relative;
        height: 190px;
        overflow: hidden;
        background-color: #f1f3f5;
    }
    .rt-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .rt-card:hover .rt-img {
        transform: scale(1.05);
    }
    .rt-card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #212529;
    }

    /* Stat Cards */
    .stat-card {
        border-radius: 1.25rem;
        border: none;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06) !important;
    }
</style>

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
                <li class="nav-item"><a class="nav-link" href="#daftar-rt">Daftar RT</a></li>
                <li class="nav-item"><a class="nav-link" href="#wilayah">Wilayah</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Masthead-->
<header class="masthead">
    <div class="container">
        <div class="masthead-heading">Situs Resmi <br><?= esc($rw->nama) ?></div>
        <div class="masthead-subheading">Kelurahan Minomartani, Kapanewon Ngaglik, Kabupaten Sleman, D.I. Yogyakarta</div>
        <a class="btn btn-primary btn-xl my-3 shadow" href="#daftar-rt">Lihat Daftar RT</a>
    </div>
</header>

<!-- Profil RW / Tentang Kami -->
<section class="page-section" id="tentang-kami">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h3 class="section-subheading mb-1">PROFIL WILAYAH</h3>
                <h2 class="section-heading mb-4"><?= esc($rw->nama) ?> Minomartani</h2>

                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                    <?= esc($rw->nama) ?> merupakan bagian integral dari Desa Minomartani, Kecamatan Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta. Membawahi <strong><?= count($rts) ?> Rukun Tetangga (RT)</strong> yang aktif, rukun, dan berdaya guna mewujudkan lingkungan warga yang guyub, aman, dan sejahtera.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary rounded-pill px-5 py-3 shadow-sm fw-bold" href="#daftar-rt">
                        <i class="fas fa-sitemap me-2"></i> Jelajahi Rukun Tetangga
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img style="border-radius: 1.5rem; max-height: 380px; width: 100%; object-fit: cover; box-shadow: 0 16px 36px rgba(0,0,0,0.12);" class="img-fluid" src="<?= base_url('public/home/') ?>assets/img/wilayah.png" alt="Wilayah <?= esc($rw->nama) ?>" />
            </div>
        </div>
    </div>
</section>

<!-- Statistik RW -->
<section class="page-section bg-light py-5" id="statistik">
    <div class="container">
        <div class="row g-3">
            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/kk-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="RT">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark"><?= count($rts) ?></h3>
                            <span class="text-muted small fw-semibold">Rukun Tetangga</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/lokasi-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Kelurahan">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.25rem;">Minomartani</h3>
                            <span class="text-muted small fw-semibold">Desa / Kelurahan</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/lokasi-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Kecamatan">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.25rem;">Ngaglik</h3>
                            <span class="text-muted small fw-semibold">Kapanewon / Kec.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/lokasi-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Kabupaten">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.25rem;">Sleman</h3>
                            <span class="text-muted small fw-semibold">Kabupaten / DIY</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Daftar RT -->
<section class="page-section" id="daftar-rt">
    <div class="container">
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-1">LINGKUNGAN WARGA</h3>
            <h2 class="section-heading mb-2">Daftar Rukun Tetangga (RT)</h2>
            <p class="text-muted mb-0">Pilih salah satu RT di bawah untuk membuka situs resmi dan informasi kegiatan</p>
        </div>

        <div class="row g-4 justify-content-center mt-2">
            <?php if (!empty($rts)): ?>
                <?php foreach ($rts as $rt): ?>
                    <?php
                    $targetUrl = !empty($rt->subdomain)
                        ? 'https://' . esc($rt->subdomain) . '.minomartani.com/'
                        : base_url(esc($rt->slug));
                    $fotoCover = !empty($rt->foto_hero)
                        ? base_url('public/rt/' . $rt->foto_hero)
                        : base_url('public/home/assets/img/tentang-kami.png');
                    ?>
                    <div class="col-lg-4 col-md-6 d-flex">
                        <div class="card w-100 shadow-sm rt-card d-flex flex-column">
                            <div class="rt-img-wrapper">
                                <img src="<?= $fotoCover ?>" alt="<?= esc($rt->nama) ?>" class="rt-img" loading="lazy">
                            </div>
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                                        <?= esc($rw->nama) ?>
                                    </span>
                                </div>
                                <h3 class="rt-card-title mb-2"><?= esc($rt->nama) ?></h3>
                                <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                                    <?= esc(!empty($rt->alamat) ? $rt->alamat : 'Kelurahan Minomartani, Kapanewon Ngaglik, Sleman') ?>
                                </p>
                                <div class="pt-3 border-top mt-auto">
                                    <div class="btn btn-primary rounded-pill w-100 py-2 small fw-bold">
                                        Kunjungi Situs RT <i class="fas fa-arrow-right ms-1"></i>
                                    </div>
                                </div>
                                <a class="stretched-link" href="<?= $targetUrl ?>" aria-label="Buka situs <?= esc($rt->nama) ?>"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted"><i class="fas fa-info-circle fa-2x mb-2 d-block opacity-50"></i>Belum ada data RT yang terdaftar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Wilayah / Peta Lokasi -->
<section class="page-section bg-light" id="wilayah">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h3 class="section-subheading mb-1">PETA WILAYAH</h3>
                <h2 class="section-heading mb-4">Lokasi <?= esc($rw->nama) ?></h2>

                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                    Wilayah <?= esc($rw->nama) ?> terletak di Desa Minomartani, Kapanewon Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta. Lokasi strategis dengan kemudahan akses menuju pusat layanan publik, fasilitas kesehatan, dan pendidikan di kawasan Sleman dan Yogyakarta.
                </p>

                <a class="btn btn-outline-primary rounded-pill px-4 py-3 fw-bold" target="_blank" href="https://www.google.com/maps/place/Minomartani,+Ngaglik,+Sleman+Regency,+Special+Region+of+Yogyakarta/@-7.7393059,110.4079884,18.75z">
                    <i class="fas fa-map-marker-alt me-2 text-danger"></i> Buka di Google Maps <i class="fas fa-external-link-alt ms-1 small"></i>
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <img style="border-radius: 1.5rem; max-height: 400px; width: 100%; object-fit: cover; box-shadow: 0 16px 36px rgba(0,0,0,0.12);" class="img-fluid" src="<?= base_url('public/home/') ?>assets/img/wilayah.png" alt="Peta Lokasi" />
            </div>
        </div>
    </div>
</section>
