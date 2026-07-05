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
            </ul>
        </div>
    </div>
</nav>

<!-- Masthead-->
<header class="masthead">
    <div class="container">
        <div class="masthead-heading">Situs Resmi <br><?= esc($rw->nama) ?></div>
        <div class="masthead-subheading">Ngaglik, Sleman, Daerah Istimewa Yogyakarta</div>
        <a class="btn btn-primary btn-xl my-3" href="#daftar-rt">Lihat RT</a>
    </div>
</header>

<!-- Daftar RT -->
<section class="page-section" id="daftar-rt">
    <div class="container">
        <div class="row justify-content-center">
            <h2 class="section-heading mb-5 text-center">Daftar RT</h2>
            <?php if (! empty($rts)): ?>
                <?php foreach ($rts as $rt): ?>
                    <div class="col-md-4 mb-4">
                        <div style="border-radius: 1.5rem" class="card shadow-lg h-100">
                            <div class="card-body">
                                <h4 class="card-title"><?= esc($rt->nama) ?></h4>
                                <?php if (! empty($rt->subdomain)): ?>
                                    <a href="https://<?= esc($rt->subdomain) ?>.minomartani.com/" class="btn btn-primary btn-sm">Kunjungi Situs</a>
                                <?php else: ?>
                                    <p class="text-muted text-sm">Subdomain belum dikonfigurasi</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">Tidak ada RT terdaftar.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
