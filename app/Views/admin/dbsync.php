<div class="container-fluid">
    <div class="row">
        <!-- Configuration Status -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i> Status & Konfigurasi</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Lingkungan (Environment):</label>
                        <div>
                            <?php if ($env === 'production'): ?>
                                <span class="badge badge-danger px-3 py-2"><i class="fas fa-server mr-1"></i> Production</span>
                            <?php else: ?>
                                <span class="badge badge-success px-3 py-2"><i class="fas fa-laptop mr-1"></i> Local (Development)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>DB Sync Token:</label>
                        <?php if (empty($token)): ?>
                            <div class="text-danger small mb-2"><i class="fas fa-exclamation-triangle"></i> Token belum diatur di <code>.env</code></div>
                            <button type="button" class="btn btn-xs btn-outline-primary" id="btn-generate-token">
                                <i class="fas fa-magic"></i> Rekomendasikan Token Baru
                            </button>
                        <?php else: ?>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="sync-token-field" value="<?= esc($token) ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyToken()">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>URL Produksi (Target):</label>
                        <?php if (empty($productionURL)): ?>
                            <div class="text-muted small"><i class="fas fa-info-circle"></i> URL target belum diatur di <code>.env</code>. Hanya dibutuhkan di server lokal.</div>
                        <?php else: ?>
                            <input type="text" class="form-control form-control-sm" value="<?= esc($productionURL) ?>" readonly>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85rem;">
                        <h5><i class="icon fas fa-info-circle"></i> Petunjuk</h5>
                        Untuk sinkronisasi otomatis, tambahkan baris berikut di file <code>.env</code> Anda:
                        <pre class="bg-dark text-white p-2 rounded mt-2 mb-0" style="font-size: 0.75rem; white-space: pre-wrap; word-break: break-all;">
dbsync.token = '<?= $token ?: 'TOKEN_RAHASIA_ANDA_DISINI' ?>'
dbsync.productionURL = 'https://nama-domain-anda.com/'
                        </pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- DB Sync Actions -->
        <div class="col-md-8">
            <!-- Direct Push/Pull Sync Card -->
            <div class="card card-indigo card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync mr-2"></i> Sinkronisasi Langsung (Local <--> Prod)</h3>
                </div>
                <div class="card-body">
                    <?php if ($env === 'production'): ?>
                        <div class="alert alert-warning mb-0">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Mode Server Produksi</h5>
                            Proses <strong>Push</strong> dan <strong>Pull</strong> langsung harus diinisiasi dari server <strong>Lokal (Development)</strong> demi alasan keamanan dan konektivitas. Server produksi ini hanya bertindak sebagai penerima data.
                        </div>
                    <?php elseif (empty($token) || empty($productionURL)): ?>
                        <div class="alert alert-warning mb-0">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Konfigurasi Kurang</h5>
                            Silakan atur <code>dbsync.token</code> dan <code>dbsync.productionURL</code> di file <code>.env</code> Anda terlebih dahulu untuk mengaktifkan fitur ini.
                        </div>
                    <?php else: ?>
                        <p>Fitur ini memungkinkan Anda mengirimkan atau menarik data langsung ke/dari server produksi tanpa perlu terminal.</p>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card card-body bg-light border-warning py-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="sync-structure-only" value="1">
                                        <label class="custom-control-label text-warning font-weight-bold" for="sync-structure-only" style="cursor: pointer;">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Hanya sinkronkan struktur database saja (kosongkan semua data di server target)
                                        </label>
                                    </div>
                                    <small class="text-muted mt-1 ml-4">Gunakan ini jika Anda hanya ingin membuat ulang tabel-tabel kosong di database produksi tanpa menyalin data uji coba dari lokal Anda.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body text-center d-flex flex-column justify-content-between">
                                        <div>
                                            <i class="fas fa-cloud-upload-alt fa-3x text-danger mb-3"></i>
                                            <h5>Push ke Produksi</h5>
                                            <p class="text-muted small">Kirim database lokal Anda ke produksi. Menimpa database produksi Anda sesuai pilihan opsi struktur di atas.</p>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-block mt-3" id="btn-push-db">
                                            <i class="fas fa-upload mr-1"></i> Push ke Production
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body text-center d-flex flex-column justify-content-between">
                                        <div>
                                            <i class="fas fa-cloud-download-alt fa-3x text-info mb-3"></i>
                                            <h5>Pull dari Produksi</h5>
                                            <p class="text-muted small">Tarik database dari produksi ke komputer lokal Anda. Menimpa database lokal Anda sesuai pilihan opsi struktur di atas.</p>
                                        </div>
                                        <button type="button" class="btn btn-info btn-block mt-3" id="btn-pull-db">
                                            <i class="fas fa-download mr-1"></i> Pull dari Production
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Display -->
                        <div class="card card-dark card-outline mt-3 d-none" id="sync-progress-card">
                            <div class="card-header py-2">
                                <h4 class="card-title text-sm"><i class="fas fa-spinner fa-spin mr-2 text-primary" id="progress-spinner"></i> Status Proses</h4>
                            </div>
                            <div class="card-body py-3">
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="sync-progress-bar"></div>
                                </div>
                                <div class="text-muted text-sm font-weight-bold" id="sync-progress-text">Menyiapkan...</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Database Migrations Card -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database mr-2"></i> Jalankan Migrasi Database (Aman untuk Produksi)</h3>
                </div>
                <div class="card-body">
                    <p>Fitur ini akan menjalankan file migrasi database baru yang ada di folder <code>app/Database/Migrations/</code>. Ini adalah cara yang <strong>sangat aman</strong> untuk memperbarui struktur database di server produksi tanpa menghapus atau mengubah data transaksi/warga yang sudah ada.</p>
                    
                    <button type="button" class="btn btn-outline-success mb-3" id="btn-check-migrations">
                        <i class="fas fa-search-plus mr-1"></i> Cek Antrean Migrasi Baru
                    </button>

                    <div id="migrations-check-container" class="d-none">
                        <div class="alert alert-info py-2" id="migrations-info-text">Menghubungi server...</div>
                        <div id="migrations-list-group" class="mb-3"></div>
                        
                        <form action="<?= base_url('admin/dbsync/migrate') ?>" method="post" id="form-run-migrations" class="d-none" onsubmit="return confirm('Apakah Anda yakin ingin menjalankan migrasi database baru?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-play mr-1"></i> Terapkan Perubahan (Jalankan Migrasi)
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Manual Backup/Restore Card -->
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i> Pencadangan Manual (Ekspor / Impor File .sql)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Export -->
                        <div class="col-md-6 border-right">
                            <h5>Ekspor Database</h5>
                            <p class="text-muted small">Unduh file database (<code>.sql</code>) server ini ke komputer Anda.</p>
                            
                            <div class="btn-group mt-2">
                                <a href="<?= base_url('admin/dbsync/export') ?>" class="btn btn-secondary">
                                    <i class="fas fa-file-download mr-1"></i> Struktur & Data
                                </a>
                                <a href="<?= base_url('admin/dbsync/export?structure_only=1') ?>" class="btn btn-outline-secondary">
                                    Struktur Saja
                                </a>
                            </div>
                        </div>
                        
                        <!-- Import -->
                        <div class="col-md-6">
                            <h5>Impor Database</h5>
                            <p class="text-muted small text-danger"><strong>Peringatan:</strong> Ini akan menghapus struktur & data tabel yang ada dan menggantinya dengan file SQL yang diunggah.</p>
                            
                            <form action="<?= base_url('admin/dbsync/import') ?>" method="post" enctype="multipart/form-data" class="mt-2" onsubmit="return confirm('Apakah Anda yakin ingin menimpa database ini dengan file SQL tersebut?')">
                                <?= csrf_field() ?>
                                <div class="form-group mb-2">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="sql_file" id="sql_file" accept=".sql" required>
                                        <label class="custom-file-label" for="sql_file">Pilih file .sql...</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm mt-2">
                                    <i class="fas fa-file-upload mr-1"></i> Unggah & Pulihkan Database
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Token Recommendation -->
<div class="modal fade" id="tokenModal" tabindex="-1" role="dialog" aria-labelledby="tokenModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tokenModalLabel"><i class="fas fa-magic"></i> Rekomendasi Token Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Salin token rahasia ini dan tempelkan di file <code>.env</code> pada lingkungan <strong>Lokal</strong> dan <strong>Produksi</strong>:</p>
                <div class="input-group">
                    <input type="text" class="form-control bg-light" id="new-token-field" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyNewToken()">
                            <i class="far fa-copy"></i> Salin
                        </button>
                    </div>
                </div>
                <small class="form-text text-danger mt-2">
                    <i class="fas fa-exclamation-triangle"></i> Token ini sangat rahasia. Siapa pun yang memiliki token ini dapat menghapus dan memanipulasi database Anda.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Custom file input label update
    if (document.getElementById('sql_file')) {
        document.getElementById('sql_file').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    }

    // Modal Token Generator
    var btnGen = document.getElementById('btn-generate-token');
    if (btnGen) {
        btnGen.addEventListener('click', function() {
            var token = generateToken(48);
            document.getElementById('new-token-field').value = token;
            $('#tokenModal').modal('show');
        });
    }

    // Direct Push Action
    var btnPush = document.getElementById('btn-push-db');
    if (btnPush) {
        btnPush.addEventListener('click', function() {
            var structureOnly = document.getElementById('sync-structure-only') && document.getElementById('sync-structure-only').checked;
            
            var msg = 'PENTING!\n\nApakah Anda benar-benar yakin ingin menimpa database PRODUCTION dengan data dari LOKAL?\n\nSemua data di server produksi akan hilang.';
            if (structureOnly) {
                msg = 'PENTING!\n\nApakah Anda yakin ingin menimpa struktur database PRODUCTION?\n\nSemua data lama di produksi akan hilang dan digantikan oleh tabel kosong!';
            }
            
            if (!confirm(msg)) {
                return;
            }

            runSyncAction('<?= base_url('admin/dbsync/push') ?>', 'Push');
        });
    }

    // Direct Pull Action
    var btnPull = document.getElementById('btn-pull-db');
    if (btnPull) {
        btnPull.addEventListener('click', function() {
            var structureOnly = document.getElementById('sync-structure-only') && document.getElementById('sync-structure-only').checked;
            
            var msg = 'PENTING!\n\nApakah Anda yakin ingin menimpa database LOKAL Anda dengan data dari PRODUCTION?\n\nSemua data lokal saat ini akan hilang.';
            if (structureOnly) {
                msg = 'PENTING!\n\nApakah Anda yakin ingin menarik STRUKTUR database PRODUCTION saja?\n\nSemua data lokal Anda akan hilang dan digantikan oleh tabel kosong dari produksi.';
            }

            if (!confirm(msg)) {
                return;
            }

            runSyncAction('<?= base_url('admin/dbsync/pull') ?>', 'Pull');
        });
    }

    // Check Migrations Action
    var btnCheckMig = document.getElementById('btn-check-migrations');
    if (btnCheckMig) {
        btnCheckMig.addEventListener('click', function() {
            var container = document.getElementById('migrations-check-container');
            var infoText = document.getElementById('migrations-info-text');
            var listGroup = document.getElementById('migrations-list-group');
            var formMigrate = document.getElementById('form-run-migrations');

            container.classList.remove('d-none');
            infoText.className = 'alert alert-info py-2';
            infoText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sedang memeriksa file migrasi baru...';
            listGroup.innerHTML = '';
            formMigrate.classList.add('d-none');
            btnCheckMig.disabled = true;

            $.ajax({
                url: '<?= base_url('admin/dbsync/check-migrations') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                success: function(response) {
                    btnCheckMig.disabled = false;
                    if (response.status === 'success') {
                        var pending = response.pending;
                        if (pending.length === 0) {
                            infoText.className = 'alert alert-success py-2';
                            infoText.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Semua migrasi sudah up-to-date! Tidak ada perubahan struktur database baru.';
                        } else {
                            infoText.className = 'alert alert-warning py-2';
                            infoText.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Ditemukan <strong>' + pending.length + '</strong> migrasi baru yang belum dijalankan. Silakan tinjau perbedaan di bawah sebelum mengeksekusi:';
                            
                            var html = '';
                            pending.forEach(function(mig, idx) {
                                html += '<div class="card card-warning card-outline mb-2">';
                                html += '  <div class="card-header py-2" style="cursor: pointer;" data-toggle="collapse" data-target="#mig-code-' + idx + '">';
                                html += '    <h5 class="card-title text-sm font-weight-bold text-dark mb-0">';
                                html += '      <i class="fas fa-chevron-down mr-2 text-muted"></i> ' + mig.file;
                                html += '    </h5>';
                                html += '    <span class="badge badge-warning float-right" style="margin-top: -18px;">Pending</span>';
                                html += '  </div>';
                                html += '  <div id="mig-code-' + idx + '" class="collapse show">';
                                html += '    <div class="card-body p-0">';
                                html += '      <pre class="bg-light p-3 m-0 rounded-bottom" style="font-size: 0.8rem; border-left: 4px solid #ffc107; max-height: 250px; overflow-y: auto;"><code class="language-php">' + escapeHtml(mig.code) + '</code></pre>';
                                html += '    </div>';
                                html += '  </div>';
                                html += '</div>';
                            });
                            
                            listGroup.innerHTML = html;
                            formMigrate.classList.remove('d-none');
                        }
                    } else {
                        infoText.className = 'alert alert-danger py-2';
                        infoText.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Gagal memeriksa migrasi: ' + (response.message || 'Respons tidak valid.');
                    }
                },
                error: function() {
                    btnCheckMig.disabled = false;
                    infoText.className = 'alert alert-danger py-2';
                    infoText.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Gagal terhubung ke server untuk memeriksa migrasi.';
                }
            });
        });
    }
});

function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function generateToken(length) {
    var result           = '';
    var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for ( var i = 0; i < length; i++ ) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

function copyToken() {
    var copyText = document.getElementById("sync-token-field");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Token berhasil disalin ke clipboard!");
}

function copyNewToken() {
    var copyText = document.getElementById("new-token-field");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Token berhasil disalin ke clipboard!");
}

function runSyncAction(url, mode) {
    var progressCard = document.getElementById('sync-progress-card');
    var progressBar = document.getElementById('sync-progress-bar');
    var progressText = document.getElementById('sync-progress-text');
    var spinner = document.getElementById('progress-spinner');
    
    var structureOnly = document.getElementById('sync-structure-only') && document.getElementById('sync-structure-only').checked ? 1 : 0;

    progressCard.classList.remove('d-none');
    progressBar.style.width = '20%';
    progressBar.className = 'progress-bar bg-primary progress-bar-striped progress-bar-animated';
    progressText.innerText = mode + ' sedang berjalan: Mengekspor dan memproses data database...';

    // Disable buttons
    toggleButtons(true);

    // Call AJAX
    $.ajax({
        url: url,
        method: 'POST',
        dataType: 'json',
        data: {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            'structure_only': structureOnly
        },
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            var val = 20;
            var interval = setInterval(function() {
                if (val < 90) {
                    val += 10;
                    progressBar.style.width = val + '%';
                    if (val === 50) {
                        progressText.innerText = mode + ' sedang berjalan: Menghubungi server target...';
                    } else if (val === 80) {
                        progressText.innerText = mode + ' sedang berjalan: Menerapkan struktur & data SQL...';
                    }
                } else {
                    clearInterval(interval);
                }
            }, 1500);
            return xhr;
        },
        success: function(response) {
            if (response.status === 'success') {
                progressBar.style.width = '100%';
                progressBar.className = 'progress-bar bg-success';
                progressText.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Sukses! ' + response.message + '</span>';
                spinner.className = 'fas fa-check-circle text-success';
                alert('Sinkronisasi sukses!\n\n' + response.message);
                
                // If it's pull or structure-only reset, reload after 2s to reflect new state
                if (mode === 'Pull' || structureOnly) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            } else {
                showError(response.message || 'Respons gagal dari server.');
            }
        },
        error: function(xhr, status, error) {
            var errMsg = 'Terjadi kesalahan jaringan atau server.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errMsg = xhr.responseJSON.message;
            }
            showError(errMsg);
        },
        complete: function() {
            toggleButtons(false);
        }
    });

    function showError(message) {
        progressBar.style.width = '100%';
        progressBar.className = 'progress-bar bg-danger';
        progressText.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Gagal! ' + message + '</span>';
        spinner.className = 'fas fa-times-circle text-danger';
        alert('Gagal melakukan sinkronisasi:\n\n' + message);
    }
}

function toggleButtons(disabled) {
    var btnPush = document.getElementById('btn-push-db');
    var btnPull = document.getElementById('btn-pull-db');
    if (btnPush) btnPush.disabled = disabled;
    if (btnPull) btnPull.disabled = disabled;
}
</script>
