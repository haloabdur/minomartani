<?= view('includes/nav-white') ?>

<section class="page-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">

                <!-- general form elements -->
                <div class="card card-primary border-0 shadow-lg p-3">
                    <!-- form start -->
                    <div class="card-body">
                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= session()->getFlashdata('error') ?>
                            </div>
                        <?php endif ?>
                        <h3 class="my-4">Surat Keterangan</h3>

                        <div class="row">
                            <div class="col-md-6 small">
                                Hal : Permohonan Surat Keterangan serta Pernyataan Kebenaran & Keabsahan Dokumen.
                            </div>
                            <div style="text-align: right" class="col small">
                                Sleman, <?= date('d-m-Y') ?><br>
                                Kepada, Yth. Lurah <br>
                                di Minomartani
                            </div>
                        </div>

                        <div class="row my-4">
                            <div class="col-md-6">
                                Dengan hormat, <br>
                                Yang bertanda tangan dibawah ini,
                            </div>
                        </div>

                        <?= form_open('layanan/store') ?>
                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label class="mb-2 small">No. KTP/NIK</label>
                                    <input type="text" name="nik" class="form-control" placeholder="No. NIK/KTP" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label class="mb-2 small">Dengan ini bermaksud mengajukan permohonan Surat Keterangan :</label>
                                    <input type="text" name="maksut" class="form-control" placeholder="Tulis maksut Anda" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label class="mb-2 small">Untuk Keperluan</label>
                                    <input type="text" name="perlu" class="form-control" placeholder="Tulis keperluan Anda" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label class="mb-2 small">Sehubungan dengan hal tersebut di atas, berikut Saya akan lampirkan berkas-berkas sebagai kelengkapan pendukung permohonan : (Pisahkan dengan koma)</label>
                                    <input type="text" name="lampiran" class="form-control mb-1" placeholder="Tulis berkas yang akan dilampirkan. Contoh : FC KTP, FC KK" required>
                                    <small class="text-muted">* Berkas silahkan dibawa pada saat Surat sudah di ACC</small>
                                </div>
                            </div>
                        </div>
                        <hr class="dot py-1" />
                        <p><strong>Data yang terdapat dalam lampiran dokumen permohonan ini adalah Benar dan Sah.</strong></p>
                        <p class="text-danger">Apabila dikemudian hari ditemukan bahwa dokumen yang telah saya berikan tidak benar, maka saya bersedia dikenakan sanksi sesuai dengan peraturan dan ketentuan yang berlaku.</p>
                        <p>Demikian permohonan dan pernyataan ini saya buat dengan sebenar-benarnya, tanpa ada paksaan dari pihak manapun.</p>
                        <p>Atas perkenan Bapak / Ibu, kami ucapkan terima kasih.</p>

                        <div class="row mt-3">
                            <div class="col">
                                <div class="form-group">
                                    <label class="mb-2 small">PIN Anda di RT 29 &nbsp; <a target="_blank" href="https://wa.me/6283869281843" class="small">Lupa PIN Anda?</a></label>
                                    <input type="text" name="pin" class="form-control" placeholder="PIN Anda" required>
                                </div>
                            </div>
                        </div>

                        <hr class="dot" />

                        <div class="row mt-4">
                            <div class="col">
                                <button type="submit" style="border-radius: 2rem" class="btn btn-primary py-3 w-100">Ajukan Surat Pernyataan</button>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
    </div>
</section>