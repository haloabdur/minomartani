<?php
$__rtNama     = (isset($rt) && $rt !== null) ? $rt->nama : 'RT 29';
$__rtRw       = (isset($rw) && $rw !== null) ? $rw->nama : null;
$__rtAlamat   = (isset($rt) && $rt !== null && !empty($rt->alamat)) ? $rt->alamat : 'Ngaglik, Sleman, Daerah Istimewa Yogyakarta';
$__rtDeskripsi = (isset($rt) && $rt !== null && !empty($rt->deskripsi))
    ? $rt->deskripsi
    : "{$__rtNama} Minomartani adalah bagian dari Desa Minomartani, Kecamatan Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta.";
$__rtWa       = (isset($rt) && $rt !== null && !empty($rt->no_wa)) ? $rt->no_wa : '6283869281843';
$__rtHero     = (isset($rt) && $rt !== null && !empty($rt->foto_hero))
    ? base_url('public/rt/' . $rt->foto_hero)
    : base_url('public/home/') . 'assets/img/tentang-kami.png';
$uri            = service('uri');
$__isSlugged    = ($uri->getTotalSegments() > 0 && isset($rt->slug) && $uri->getSegment(1) === $rt->slug);
$__tenantPrefix = $__isSlugged ? $rt->slug . '/' : '';
?>

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

    /* Berita Cards */
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
        height: 200px;
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
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Papan Informasi Cards */
    .papan-card {
        border-radius: 1.25rem;
        border: none;
        background: #ffffff;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .papan-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.08) !important;
    }
    .papan-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #212529;
        line-height: 1.4;
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

    /* Team Member */
    .team-member img {
        width: 13rem;
        height: 13rem;
        object-fit: cover;
        border: 4px solid #ffffff;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transition: transform 0.25s ease;
    }
    .team-member:hover img {
        transform: scale(1.04);
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
                <li class="nav-item"><a class="nav-link" href="#berita">Berita RT</a></li>
                <li class="nav-item"><a class="nav-link" href="#papan-informasi">Papan Informasi</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url($__tenantPrefix . 'layanan') ?>">Layanan</a></li>
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
        <div class="d-flex flex-wrap justify-content-center gap-3 my-3">
            <a class="btn btn-primary btn-xl shadow" href="#tentang-kami">Profil Kami</a>
            <a class="btn btn-outline-light btn-xl shadow" href="<?= base_url($__tenantPrefix . 'layanan') ?>">
                <i class="fas fa-file-alt me-2"></i> Pengajuan Surat
            </a>
        </div>
    </div>
</header>

<!-- Berita RT -->
<section class="page-section" id="berita">
    <div class="container">
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-1">KABAR &amp; KEGIATAN</h3>
            <h2 class="section-heading mb-2">Berita <?= esc($__rtNama) ?></h2>
            <p class="text-muted mb-0">Informasi dan berita seputar kegiatan warga di lingkungan <?= esc($__rtNama) ?></p>
        </div>

        <div class="row g-4 justify-content-center mt-2">
            <?php if (empty($beritas)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted"><i class="far fa-newspaper fa-2x mb-2 d-block opacity-50"></i>Belum ada berita yang dipublikasikan.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($beritas as $berita): ?>
                <div class="col-lg-4 col-md-6 d-flex">
                    <div class="card w-100 shadow-sm berita-card d-flex flex-column">
                        <div class="berita-img-wrapper">
                            <?php
                            $fotoCover = !empty($berita->foto)
                                ? foto_url($berita->foto)
                                : base_url('public/home/assets/img/tentang-kami.png');
                            ?>
                            <img src="<?= $fotoCover ?>" alt="<?= esc($berita->judul) ?>" class="berita-img" loading="lazy">
                        </div>
                        <div class="card-body p-4 d-flex flex-column flex-grow-1">
                            <div class="text-muted small mb-2 d-flex align-items-center">
                                <i class="far fa-calendar-alt me-1 text-primary"></i>
                                <span><?= !empty($berita->created_time) ? date('d M Y', strtotime($berita->created_time)) : '-' ?></span>
                            </div>
                            <h3 class="berita-title mb-2">
                                <?= esc($berita->judul) ?>
                            </h3>
                            <?php
                            $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags($berita->deskripsi ?? '')));
                            $excerpt   = mb_strlen($cleanText) > 110 ? mb_substr($cleanText, 0, 107) . '...' : $cleanText;
                            ?>
                            <p class="berita-excerpt mb-4 flex-grow-1">
                                <?= esc($excerpt) ?>
                            </p>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                                <span class="text-primary fw-bold small">
                                    Baca Selengkapnya <i class="fas fa-arrow-right ms-1"></i>
                                </span>
                            </div>
                            <a class="stretched-link" href="<?= base_url($__tenantPrefix . 'berita/' . $berita->slug) ?>" aria-label="Baca berita <?= esc($berita->judul) ?>"></a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>

            <?php if (!empty($beritas)): ?>
                <div class="col-12 text-center mt-5">
                    <a href="<?= base_url($__tenantPrefix . 'berita') ?>" class="btn btn-primary btn-xl shadow" style="min-width: 250px; width: auto; padding: 0.85rem 2.5rem;">
                        Lihat Semua Berita <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Papan Informasi -->
<section class="page-section bg-light" id="papan-informasi">
    <div class="container">
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-1">HIMBAUAN &amp; TATA TERTIB</h3>
            <h2 class="section-heading mb-2">Papan Informasi <?= esc($__rtNama) ?></h2>
            <p class="text-muted mb-0">Pengumuman penting, aturan, dan ketertiban lingkungan untuk seluruh warga</p>
        </div>
        <div class="row justify-content-center mt-2">
            <div class="col-lg-8 col-md-10">
                <?php if (empty($papanInformasis)): ?>
                    <div class="card border-0 shadow-sm p-4 text-center rounded-4 bg-white">
                        <p class="text-muted mb-0"><i class="fas fa-bullhorn fa-2x mb-2 d-block opacity-50"></i>Belum ada informasi yang berlaku saat ini.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($papanInformasis as $papan): ?>
                    <div class="card shadow-sm mb-3 papan-card position-relative">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small rounded-pill">
                                    <i class="fas fa-bullhorn me-1"></i> Pengumuman
                                </span>
                                <?php if (!empty($papan->timestamp)): ?>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d M Y', strtotime($papan->timestamp)) ?></small>
                                <?php endif; ?>
                            </div>
                            <h3 class="papan-title mb-2"><?= esc($papan->judul) ?></h3>
                            <p class="text-muted mb-3" style="line-height: 1.6;"><?= esc(substr(strip_tags($papan->isi), 0, 200)) ?><?= strlen(strip_tags($papan->isi)) > 200 ? '...' : '' ?></p>
                            <div class="d-flex align-items-center text-primary fw-bold small">
                                <span>Lihat Selengkapnya</span>
                                <i class="fas fa-arrow-right ms-2"></i>
                            </div>
                            <a class="stretched-link" href="<?= base_url($__tenantPrefix . 'papan-informasi/' . $papan->id_papan) ?>" aria-label="Lihat informasi <?= esc($papan->judul) ?>"></a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</section>

<!-- Layanan Warga Online -->
<section class="page-section" id="layanan-surat">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <h3 class="section-subheading mb-1">LAYANAN MANDIRI</h3>
                <h2 class="section-heading mb-3">Pengajuan Surat Keterangan Online</h2>
                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                    Kini warga <?= esc($__rtNama) ?> dapat mengajukan permohonan Surat Keterangan / Pengantar RT secara mandiri, cepat, dan praktis tanpa antre. Cukup verifikasi alamat dengan PIN rumah, pilih anggota keluarga yang memohon, dan ajukan secara online.
                </p>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-4 bg-light h-100 border border-light">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    <i class="fas fa-lock small"></i>
                                </div>
                                <strong class="text-dark small">Aman &amp; Terverifikasi</strong>
                            </div>
                            <small class="text-muted">Dilindungi PIN unik tiap rumah tangga untuk menjamin keamanan &amp; privasi data.</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-4 bg-light h-100 border border-light">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0;">
                                    <i class="fas fa-bolt small"></i>
                                </div>
                                <strong class="text-dark small">Praktis &amp; Terdata</strong>
                            </div>
                            <small class="text-muted">Langsung terhubung ke sistem RT untuk ditinjau dan disetujui pengurus.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary rounded-pill px-5 py-3 shadow fw-bold" href="<?= base_url($__tenantPrefix . 'layanan') ?>">
                        <i class="fas fa-file-signature me-2"></i> Ajukan Surat Sekarang
                    </a>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1 text-center">
                <img style="max-height: 380px; width: auto; max-width: 100%;" class="img-fluid" src="<?= base_url('public/home/assets/layanan.svg') ?>" alt="Layanan Surat Pengantar <?= esc($__rtNama) ?>" />
            </div>
        </div>
    </div>
</section>

<!-- Tentang Kami -->
<section class="page-section" id="tentang-kami">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h3 class="section-subheading mb-1">PROFIL LINGKUNGAN</h3>
                <h2 class="section-heading mb-4"><?= esc($__rtNama) ?> Minomartani</h2>

                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem;"><?= esc($__rtDeskripsi) ?></p>

                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary rounded-pill px-5 py-3 shadow-sm fw-bold" href="https://wa.me/<?= esc($__rtWa) ?>" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i> Hubungi Kami
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img style="border-radius: 1.5rem; max-height: 400px; width: 100%; object-fit: cover; box-shadow: 0 16px 36px rgba(0,0,0,0.12);" class="img-fluid" src="<?= esc($__rtHero) ?>" alt="<?= esc($__rtNama) ?>" />
            </div>
        </div>
    </div>
</section>

<!-- Statistik -->
<section class="page-section bg-light py-5" id="statistik">
    <div class="container">
        <div class="row g-3">
            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/kk-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="KK">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark"><?= number_format($kk) ?></h3>
                            <span class="text-muted small fw-semibold">Kepala Keluarga</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/pria-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Pria">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark"><?= number_format($laki) ?></h3>
                            <span class="text-muted small fw-semibold">Warga Pria</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/wanita-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Wanita">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark"><?= number_format($perempuan) ?></h3>
                            <span class="text-muted small fw-semibold">Warga Wanita</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="card shadow-sm p-3 h-100 stat-card">
                    <div class="d-flex align-items-center">
                        <img src="<?= base_url('public/home/') ?>assets/lokasi-icon.svg" width="48px" class="me-3 rounded-circle shadow-icon" alt="Alamat">
                        <div>
                            <h3 class="mb-0 fw-bold text-dark"><?= number_format($jml_alamat) ?></h3>
                            <span class="text-muted small fw-semibold">Total Alamat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Wilayah -->
<section class="page-section" id="lokasi">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h3 class="section-subheading mb-1">WILAYAH KAMI</h3>
                <h2 class="section-heading mb-4">Lokasi <?= esc($__rtNama) ?><?= $__rtRw !== null ? ' / ' . esc($__rtRw) : '' ?></h2>

                <p class="text-muted mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                    <?= esc($__rtAlamat) ?>. Wilayah ini merupakan bagian dari Desa Minomartani, Kecamatan Ngaglik, Kabupaten Sleman, Daerah Istimewa Yogyakarta.
                </p>

                <a class="btn btn-outline-primary rounded-pill px-4 py-3 fw-bold" target="_blank" href="https://www.google.com/maps/place/Minomartani,+Ngaglik,+Sleman+Regency,+Special+Region+of+Yogyakarta/@-7.7393059,110.4079884,18.75z/data=!4m5!3m4!1s0x2e7a596c429c827f:0x1d71fac6900f38d2!8m2!3d-7.7349434!4d110.405355">
                    <i class="fas fa-map-marker-alt me-2 text-danger"></i> Buka di Google Maps <i class="fas fa-external-link-alt ms-1 small"></i>
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <img style="border-radius: 1.5rem; max-height: 400px; width: 100%; object-fit: cover; box-shadow: 0 16px 36px rgba(0,0,0,0.12);" class="img-fluid" src="<?= base_url('public/home/') ?>assets/img/wilayah.png" alt="Wilayah <?= esc($__rtNama) ?>" />
            </div>
        </div>
    </div>
</section>

<!-- Team / Ketua RT -->
<section class="page-section bg-light" id="ketua-rt">
    <div class="container">
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-1">REKAM JEJAK</h3>
            <h2 class="section-heading mb-2">Ketua <?= esc($__rtNama) ?></h2>
            <p class="text-muted mb-0">Jajaran kepemimpinan <?= esc($__rtNama) ?> dari masa ke masa</p>
        </div>

        <div class="row g-4 justify-content-center mt-2">
            <?php if (empty($ketuas)): ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Data ketua RT belum tersedia.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($ketuas as $ketua): ?>
                <div class="col-lg-3 col-md-6 d-flex justify-content-center">
                    <div class="team-member text-center">
                        <img class="rounded-circle" src="<?= !empty($ketua->foto_ketua) ? esc(foto_url($ketua->foto_ketua, 'ketua')) : base_url('public/home/') . 'assets/img/profile.png' ?>" alt="<?= esc($ketua->nama_ketua) ?>" />
                        <h4 class="fw-bold mt-3 mb-1"><?= esc($ketua->nama_ketua) ?></h4>
                        <span class="badge bg-white text-muted border px-3 py-1 rounded-pill small">
                            <i class="far fa-clock me-1 text-primary"></i> <?= esc($ketua->mulai) ?> - <?= esc($ketua->selesai) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <?php if (!empty($ketuas)): ?>
            <div class="row mt-4">
                <div class="col-lg-8 mx-auto text-center">
                    <p class="text-muted small">Berikut rekam jejak jajaran Ketua <?= esc($__rtNama) ?> yang telah mendedikasikan waktu dan tenaga untuk kemajuan lingkungan.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
