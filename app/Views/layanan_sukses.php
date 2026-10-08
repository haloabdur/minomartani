<?php
$__rt     = current_rt();
$__rtWa   = ($__rt !== null && !empty($__rt->no_wa)) ? $__rt->no_wa : '6283869281843';
$__rtNama = $__rt !== null ? $__rt->nama : 'RT 29';
$__no     = session()->getFlashdata('no_pengajuan');
$__pesan  = 'Halo pengurus ' . $__rtNama . ', saya sudah mengajukan Surat Keterangan' . ($__no ? ' (nomor pengajuan #' . $__no . ')' : '') . '.';
?>
<?= view('includes/nav-white') ?>

<section class="page-section bg-light detail-page-section" id="layanan-sukses" style="min-height: 85vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div style="border-radius: 2rem" class="card card-primary text-center border-0 shadow-lg p-4">
                    <center class="py-4"><img src="<?= base_url('public/home/assets/check.svg') ?>" width="250"></center>
                    <h3 class="py-3">Terima kasih! <br> Pengajuan Anda sudah kami terima.</h3>
                    <?php if ($__no): ?>
                        <p class="mb-1">Nomor pengajuan: <strong>#<?= esc($__no) ?></strong></p>
                    <?php endif ?>
                    <p class="px-4">Pengurus <?= esc($__rtNama) ?> akan memeriksa pengajuan Anda. Untuk menanyakan status, hubungi RT lewat WhatsApp.</p>
                    <p><a class="btn btn-success" target="_blank" href="https://wa.me/<?= esc(preg_replace('/\D+/', '', $__rtWa)) ?>?text=<?= rawurlencode($__pesan) ?>">Hubungi RT via WhatsApp</a></p>
                    <a class="pb-4" href="<?= base_url() ?>" alt="Kembali Beranda">Kembali ke Home</a>
                </div>
            </div>
        </div>
    </div>
</section>
