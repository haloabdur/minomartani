<div class="container-fluid">

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Panel Kiri: Export -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download mr-2"></i> Export Struktur DB Local</h3>
                </div>
                <div class="card-body">
                    <p>
                        Gunakan tombol ini saat Anda berada di <strong>Local Development</strong>.
                        Sistem akan membaca struktur tabel dan kolom (schema) yang Anda buat di local,
                        lalu mengunduhnya dalam format <code>.json</code>.
                    </p>
                    <p class="text-muted small">
                        * Data (row) warga, berita, dsb. <strong>TIDAK</strong> diikutkan. Hanya struktur tabel saja.
                    </p>
                </div>
                <div class="card-footer">
                    <a href="<?= base_url('admin/sync/export_schema') ?>" class="btn btn-primary d-block font-weight-bold">
                        <i class="fas fa-file-export mr-2"></i> Download JSON Struktur DB
                    </a>
                </div>
            </div>
        </div>

        <!-- Panel Kanan: Import -->
        <div class="col-md-6">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-upload mr-2"></i> Import & Sinkronkan ke Server</h3>
                </div>

                <?= form_open_multipart('admin/sync/import_schema') ?>
                <div class="card-body">
                    <p>
                        Gunakan panel ini saat Anda berada di <strong>Server Produksi</strong>.
                        Upload file JSON hasil export dari local. Sistem akan membandingkannya dengan database server saat ini:
                    </p>
                    <ul>
                        <li><span class="text-success"><i class="fas fa-plus"></i></span> Tabel baru akan dibuat.</li>
                        <li><span class="text-primary"><i class="fas fa-columns"></i></span> Kolom baru akan ditambahkan.</li>
                        <li><span class="text-muted"><i class="fas fa-shield-alt"></i></span> Data yang sudah ada di server TETAP AMAN (tidak terhapus).</li>
                    </ul>
                    <div class="form-group mt-4">
                        <label for="schema_json">Pilih File JSON Schema</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="schema_json" name="schema_json" accept=".json" required>
                                <label class="custom-file-label" for="schema_json">Pilih file...</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning d-block w-100 font-weight-bold" onclick="return confirm('Apakah Anda yakin ingin mensinkronkan struktur DB menggunakan file ini? Proses tidak akan menghapus data, namun akan menambah tabel/kolom baru di server.')">
                        <i class="fas fa-sync-alt mr-2"></i> Mulai Sinkronisasi
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Logs Hasil -->
    <?php if (session()->getFlashdata('sync_logs')) : ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark">
                        <h3 class="card-title"><i class="fas fa-terminal mr-2"></i> Log Hasil Migrasi Otomatis</h3>
                    </div>
                    <div class="card-body bg-light" style="font-family: monospace; font-size: 14px; max-height: 400px; overflow-y: auto;">
                        <?php
                        $logs = session()->getFlashdata('sync_logs');
                        foreach ($logs as $log) {
                            echo "<div>{$log}</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Custom file input label
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = document.getElementById("schema_json").files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
</script>