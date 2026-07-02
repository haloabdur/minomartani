<?= view('includes/nav-white') ?>

<section class="page-section" id="tentang-kami">
    <div class="container">
        <div class="row justify-content-center">
            <div style="border-radius: 2rem" class="card col-md-6 p-5 shadow">
                <h3 class="section-subheading mb-2 text-muted">INFO ALAMAT</h3>
                <h2 class="section-heading"><?= $alamat->alamat ?></h2>

                <hr class="dot py-2" />
                <dl class="row">
                    <dd class="col-sm-4">Kepala Keluarga</dd>
                    <dt class="col-sm-8"><?= $alamat->nama_warga ?></dt>
                </dl>

                <dl class="row">
                    <dd class="col-sm-4">Alamat</dd>
                    <dt class="col-sm-8">Jl. <?= $alamat->alamat ?> Minomartani</dt>
                </dl>

                <dl class="row">
                    <dd class="col-sm-4">RT / RW</dd>
                    <dt class="col-sm-8">029 / 006</dt>
                </dl>

                <p class="small pt-4"><i class="fa fa-check-circle text-success"></i> Data alamat ini telah <strong>terverifikasi</strong> pihak RT.</p>
                <p class="small text-muted"><i class="fa fa-info-circle"></i> Apabila ada kesalahan data silahkan laporkan kepada pihak RT.</p>


            </div>
        </div>
    </div>
</section>