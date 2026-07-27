<?php $__rtNama = (isset($rt) && $rt !== null) ? $rt->nama : 'RT29'; ?>
<?php $__rtWa   = (isset($rt) && $rt !== null && !empty($rt->no_wa)) ? $rt->no_wa : '6283869281843'; ?>
<!-- Clients-->
<div class="py-5 my-5" id="hubungi-kami">
    <div class="container">
        <div class="row text-center">
            <h1>Ada Pertanyaan / Keluhan?</h1>
            <div class="masthead-subheading text-muted">Hubungi Kami lewat klik tombol whatsapp di bawah ya</div>
            <center><a style="background-color: #00d05f; box-shadow: 0 10px 16px 0 #00d05f3d" class="btn btn-xl ms-3 mt-4 text-white" href="https://wa.me/<?= esc($__rtWa) ?>"><img src="<?= base_url('public/home/assets/') ?>whatsapp-icon.svg"> Hubungi Kami</a></center>
        </div>
    </div>
</div>

<!-- Footer-->
<footer class="footer py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="text-center">Dibuat oleh tim <?= esc($__rtNama) ?> Minomartani dengan penuh cinta <img src="<?= base_url('public/home/assets/') ?>heart-icon.svg"> dan ☕</div>
            <div class="text-center text-muted">Copyright &copy; <?= esc($__rtNama) ?> Minomartani <?= date('Y') ?></div>
        </div>
    </div>
</footer>
<!-- Bootstrap core JS-->
<script src="<?= base_url('public/dist/js/') ?>bootstrap.bundle.min.js"></script>
<!-- Core theme JS-->
<script src="<?= base_url('public/home/') ?>js/scripts.js"></script>
</body>

</html>