<?php
$__rt       = current_rt();
$__rtWa     = ($__rt !== null && !empty($__rt->no_wa)) ? $__rt->no_wa : '6283869281843';
$__rtNama   = $__rt !== null ? $__rt->nama : 'RT 29';
$__prefix   = empty($slug) ? '' : $slug . '/';
$__waDigits = preg_replace('/\D+/', '', $__rtWa);
?>
<?= view('includes/nav-white') ?>

<!-- Scoped Design System Styles for Layanan -->
<style>
    .section-subheading {
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #1A75CF;
        text-transform: uppercase;
    }
    .section-heading {
        font-size: 2.25rem;
        font-weight: 700;
        color: #212529;
    }

    /* Main Card */
    .layanan-main-card {
        border-radius: 1.5rem;
        border: none;
        background: #ffffff;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    /* Stepper Header */
    .layanan-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        padding-bottom: 1.5rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .stepper-step-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 2;
        background: #ffffff;
    }
    .stepper-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        color: #6c757d;
        background-color: #f1f3f5;
        border: 2px solid transparent;
        transition: all 0.25s ease;
        flex-shrink: 0;
    }
    .stepper-step-item.active .stepper-circle {
        background-color: #1A75CF;
        color: #ffffff;
        box-shadow: 0 0 0 5px rgba(26, 117, 207, 0.15);
    }
    .stepper-step-item.completed .stepper-circle {
        background-color: #198754;
        color: #ffffff;
    }
    .stepper-text {
        display: flex;
        flex-direction: column;
    }
    .stepper-step-num {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.75px;
        color: #86919B;
    }
    .stepper-step-item.active .stepper-step-num {
        color: #1A75CF;
    }
    .stepper-step-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #212529;
    }
    .stepper-step-item:not(.active):not(.completed) .stepper-step-name {
        color: #6c757d;
        font-weight: 600;
    }
    .stepper-divider {
        flex: 1;
        height: 2px;
        background-color: #e9ecef;
        margin: 0 1rem;
        min-width: 1.5rem;
        transition: background-color 0.25s ease;
    }
    .stepper-divider.completed {
        background-color: #198754;
    }

    @media (max-width: 767.98px) {
        .stepper-step-name {
            display: none;
        }
        .stepper-divider {
            margin: 0 0.5rem;
        }
        .section-heading {
            font-size: 1.75rem;
        }
    }

    /* Form Inputs */
    .layanan-input,
    .form-control,
    .form-select {
        border-radius: 0.75rem;
        border: 1px solid #d0d5dd;
        padding: 0.7rem 1rem;
        font-size: 0.95rem;
        color: #212529;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .layanan-input:focus,
    .form-control:focus,
    .form-select:focus {
        border-color: #1A75CF;
        box-shadow: 0 0 0 4px rgba(26, 117, 207, 0.12);
        outline: none;
    }
    .form-label-custom {
        font-size: 0.875rem;
        font-weight: 600;
        color: #344054;
        margin-bottom: 0.45rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Custom Address Select */
    .alamat-select-box {
        border-radius: 0.85rem !important;
        border: 1px solid #d0d5dd;
        background-color: #f8fafc;
        padding: 0.4rem;
        font-size: 0.95rem;
        outline: none;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .alamat-select-box:focus {
        border-color: #1A75CF;
        box-shadow: 0 0 0 4px rgba(26, 117, 207, 0.12);
        background-color: #ffffff;
    }
    .alamat-select-box option {
        padding: 0.7rem 1rem;
        margin-bottom: 3px;
        border-radius: 0.5rem;
        cursor: pointer;
        color: #344054;
        transition: background-color 0.15s ease;
    }
    .alamat-select-box option:hover {
        background-color: #eaf3fd;
        color: #1A75CF;
    }
    .alamat-select-box option:checked {
        background: #1A75CF linear-gradient(0deg, #1A75CF 0%, #1A75CF 100%) !important;
        color: #ffffff !important;
        font-weight: 600;
    }

    /* Action Buttons */
    .btn-action-primary {
        background-color: #1A75CF;
        border-color: #1A75CF;
        color: #ffffff;
        border-radius: 2rem;
        font-weight: 700;
        padding: 0.85rem 2rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .btn-action-primary:hover {
        background-color: #145da6;
        border-color: #145da6;
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(26, 117, 207, 0.25) !important;
    }

    /* Step 2 Resident Cards */
    .resident-card {
        border-radius: 1rem;
        border: 1px solid #e9ecef;
        background: #ffffff;
        padding: 1rem 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .resident-card:hover {
        transform: translateY(-3px);
        border-color: #1A75CF;
        box-shadow: 0 10px 24px rgba(26, 117, 207, 0.1) !important;
    }
    .resident-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: #ebf4fc;
        color: #1A75CF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    /* Step 3 Letter Document Styling */
    .letter-preview-box {
        border-radius: 1rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.75rem;
    }
    .form-group-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #f1f3f5;
    }
    .form-group-title i {
        color: #1A75CF;
    }

    /* Lampiran Row */
    .lampiran-badge {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.5rem;
        background-color: #f1f3f5;
        color: #495057;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
</style>

<section class="page-section bg-light detail-page-section" id="layanan" style="min-height: 85vh;">
    <div class="container">
        <!-- Section Header in Landing Page Style -->
        <div class="text-center pb-4">
            <h3 class="section-subheading mb-1">LAYANAN WARGA</h3>
            <h2 class="section-heading mb-2">Permohonan Surat Keterangan</h2>
            <p class="text-muted mb-0">Layanan pengajuan surat pengantar mandiri warga <?= esc($__rtNama) ?> Minomartani</p>
        </div>

        <div class="row justify-content-center">
            <div class="<?= $langkah === 3 ? 'col-lg-9 col-xl-8' : 'col-lg-7 col-md-9' ?>">

                <div class="card layanan-main-card p-4 p-md-5">
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle fa-lg me-2 flex-shrink-0"></i>
                            <div><?= esc(session()->getFlashdata('error')) ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif ?>

                    <!-- Modern Multi-Step Progress Tracker -->
                    <div class="layanan-stepper">
                        <div class="stepper-step-item <?= $langkah === 1 ? 'active' : ($langkah > 1 ? 'completed' : '') ?>">
                            <div class="stepper-circle">
                                <?= $langkah > 1 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-key"></i>' ?>
                            </div>
                            <div class="stepper-text">
                                <span class="stepper-step-num">Langkah 1</span>
                                <span class="stepper-step-name">Alamat &amp; PIN</span>
                            </div>
                        </div>

                        <div class="stepper-divider <?= $langkah > 1 ? 'completed' : '' ?>"></div>

                        <div class="stepper-step-item <?= $langkah === 2 ? 'active' : ($langkah > 2 ? 'completed' : '') ?>">
                            <div class="stepper-circle">
                                <?= $langkah > 2 ? '<i class="fas fa-check"></i>' : '<i class="fas fa-user-check"></i>' ?>
                            </div>
                            <div class="stepper-text">
                                <span class="stepper-step-num">Langkah 2</span>
                                <span class="stepper-step-name">Pilih Pemohon</span>
                            </div>
                        </div>

                        <div class="stepper-divider <?= $langkah > 2 ? 'completed' : '' ?>"></div>

                        <div class="stepper-step-item <?= $langkah === 3 ? 'active' : '' ?>">
                            <div class="stepper-circle">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="stepper-text">
                                <span class="stepper-step-num">Langkah 3</span>
                                <span class="stepper-step-name">Isi Formulir</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($langkah === 1): ?>
                        <!-- ================= LANGKAH 1: ALAMAT & PIN ================= -->
                        <div class="mb-4">
                            <h4 class="fw-bold mb-1 text-dark">Verifikasi Alamat Rumah</h4>
                            <p class="text-muted small mb-0">Pilih alamat rumah Anda dan masukkan PIN rumah untuk melanjutkan pengajuan surat.</p>
                        </div>

                        <?php if (empty($alamats)): ?>
                            <div class="alert alert-warning rounded-4 p-4 text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning d-block"></i>
                                Belum ada alamat yang bisa dipakai untuk layanan ini. Silakan hubungi pengurus <?= esc($__rtNama) ?>.
                            </div>
                        <?php else: ?>
                            <?= form_open($__prefix . 'layanan/verifikasi', ['autocomplete' => 'off']) ?>
                            <div class="mb-4">
                                <div class="form-label-custom">
                                    <span><i class="fas fa-home text-primary me-1"></i> Alamat Rumah</span>
                                    <span class="badge bg-light text-muted border rounded-pill small fw-normal"><?= count($alamats) ?> Alamat Terdaftar</span>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 0.75rem 0 0 0.75rem;">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="cari-alamat" class="form-control border-start-0 ps-0" placeholder="Ketik untuk mencari alamat..." style="border-radius: 0 0.75rem 0.75rem 0;">
                                </div>
                                <select name="id_alamat" id="id_alamat" class="form-control alamat-select-box w-100" size="6" required>
                                    <?php foreach ($alamats as $a): ?>
                                        <option value="<?= (int) $a->id_alamat ?>"><?= esc($a->alamat) ?></option>
                                    <?php endforeach ?>
                                </select>
                                <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Klik pada salah satu alamat di atas untuk memilih.</small>
                            </div>

                            <div class="mb-4">
                                <div class="form-label-custom">
                                    <span><i class="fas fa-lock text-primary me-1"></i> PIN Rumah di <?= esc($__rtNama) ?></span>
                                    <a target="_blank" href="https://wa.me/<?= esc($__waDigits) ?>" class="text-decoration-none small text-primary fw-semibold">
                                        <i class="fab fa-whatsapp me-1 text-success"></i> Lupa PIN Anda?
                                    </a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 0.75rem 0 0 0.75rem;">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" id="pin" name="pin" class="form-control border-start-0 border-end-0 px-2" placeholder="Masukkan PIN rumah Anda" inputmode="numeric" autocomplete="off" required>
                                    <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" id="toggle-pin-btn" style="border-radius: 0 0.75rem 0.75rem 0;" title="Tampilkan PIN">
                                        <i class="far fa-eye" id="toggle-pin-icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-1">PIN diberikan oleh pengurus RT untuk keamanan data keluarga Anda.</small>
                            </div>

                            <button type="submit" class="btn btn-action-primary w-100 py-3 shadow mt-2">
                                Lanjutkan <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                            <?= form_close() ?>

                            <script>
                                (function () {
                                    var select = document.getElementById('id_alamat');
                                    var search = document.getElementById('cari-alamat');
                                    var all = Array.prototype.map.call(select.options, function (o) {
                                        return {value: o.value, text: o.text};
                                    });
                                    search.addEventListener('input', function () {
                                        var q = search.value.trim().toLowerCase();
                                        select.innerHTML = '';
                                        all.forEach(function (item) {
                                            if (q === '' || item.text.toLowerCase().indexOf(q) !== -1) {
                                                var o = document.createElement('option');
                                                o.value = item.value;
                                                o.textContent = item.text;
                                                select.appendChild(o);
                                            }
                                        });
                                        if (select.options.length === 1) {
                                            select.selectedIndex = 0;
                                        }
                                    });

                                    // Toggle PIN Visibility
                                    var pinInput = document.getElementById('pin');
                                    var toggleBtn = document.getElementById('toggle-pin-btn');
                                    var toggleIcon = document.getElementById('toggle-pin-icon');
                                    if (toggleBtn && pinInput && toggleIcon) {
                                        toggleBtn.addEventListener('click', function () {
                                            if (pinInput.type === 'password') {
                                                pinInput.type = 'text';
                                                toggleIcon.classList.remove('fa-eye');
                                                toggleIcon.classList.add('fa-eye-slash');
                                            } else {
                                                pinInput.type = 'password';
                                                toggleIcon.classList.remove('fa-eye-slash');
                                                toggleIcon.classList.add('fa-eye');
                                            }
                                        });
                                    }
                                })();
                            </script>
                        <?php endif ?>

                    <?php elseif ($langkah === 2): ?>
                        <!-- ================= LANGKAH 2: PILIH PEMOHON ================= -->
                        <div class="mb-4">
                            <h4 class="fw-bold mb-1 text-dark">Pilih Pemohon Surat</h4>
                            <p class="text-muted small mb-0">Pilih salah satu anggota keluarga yang mengajukan permohonan surat keterangan.</p>
                        </div>

                        <!-- Info Alamat Terpilih -->
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-light border mb-4">
                            <div class="d-flex align-items-center">
                                <div class="resident-avatar me-3" style="background-color: #ebf4fc; color: #1A75CF;">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Alamat Terverifikasi:</small>
                                    <strong class="text-dark fs-6"><?= esc($alamat->alamat) ?></strong>
                                </div>
                            </div>
                            <a href="<?= base_url($__prefix . 'layanan') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="fas fa-sync-alt me-1"></i> Ganti Alamat
                            </a>
                        </div>

                        <?php if (empty($anggota)): ?>
                            <div class="alert alert-warning rounded-4 p-4 text-center">
                                <i class="fas fa-users-slash fa-2x mb-2 text-warning d-block"></i>
                                Belum ada warga aktif yang terdaftar di alamat ini. Silakan hubungi pengurus <?= esc($__rtNama) ?>.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3 mb-4">
                                <?php foreach ($anggota as $w): ?>
                                    <a class="resident-card shadow-sm" href="<?= base_url($__prefix . 'layanan/form/' . (int) $w->id_warga) ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="resident-avatar me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block fs-6"><?= esc($w->nama_warga) ?></span>
                                                <?php if (!empty($w->status_keluarga)): ?>
                                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill small mt-1">
                                                        <?= esc($w->status_keluarga) ?>
                                                    </span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                        <span class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                            Pilih <i class="fas fa-chevron-right ms-1"></i>
                                        </span>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>

                        <div class="pt-2 border-top">
                            <a class="btn btn-link text-muted p-0 text-decoration-none small" href="<?= base_url($__prefix . 'layanan') ?>">
                                <i class="fas fa-arrow-left me-1"></i> Kembali &amp; Ganti Alamat
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- ================= LANGKAH 3: FORMULIR ================= -->
                        <div class="mb-4">
                            <h4 class="fw-bold mb-1 text-dark">Lengkapi Formulir Permohonan</h4>
                            <p class="text-muted small mb-0">Periksa dan sesuaikan data di bawah ini. Data awal telah diisi otomatis berdasarkan arsip kependudukan RT.</p>
                        </div>

                        <!-- Formal Letter Head Preview -->
                        <div class="letter-preview-box">
                            <div class="row align-items-start g-2">
                                <div class="col-md-6 small">
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 mb-2 d-inline-block">Surat Keterangan Pengantar</span>
                                    <div class="fw-semibold text-dark">Hal : Permohonan serta Pernyataan Kebenaran &amp; Keabsahan Dokumen</div>
                                </div>
                                <div class="col-md-6 small text-md-end text-muted">
                                    <div>Sleman, <strong><?= date('d-m-Y') ?></strong></div>
                                    <div>Kepada Yth. <strong>Lurah Minomartani</strong></div>
                                    <div>di Minomartani</div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $tgl = (string) $warga->tanggal_lahir;
                        $tgl = (str_starts_with($tgl, '0000') || $tgl === '') ? '' : date('Y-m-d', strtotime($tgl));
                        $hp  = trim((string) $warga->no_hp) === '' ? '' : '0' . ltrim(preg_replace('/\D+/', '', (string) $warga->no_hp), '0');
                        $agamaList = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
                        $agamaVal  = old('agama', trim((string) $warga->agama));
                        if ($agamaVal !== '' && !in_array($agamaVal, $agamaList, true)) {
                            $agamaList[] = $agamaVal;
                        }
                        ?>

                        <?= form_open($__prefix . 'layanan/store', ['autocomplete' => 'off']) ?>
                        <input type="hidden" name="id_warga" value="<?= (int) $warga->id_warga ?>">

                        <!-- Bagian 1: Identitas Pemohon -->
                        <div class="mb-4">
                            <div class="form-group-title">
                                <i class="fas fa-id-card"></i> 1. Identitas Pemohon
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="nama_pemohon">Nama Lengkap</label>
                                    <input type="text" id="nama_pemohon" name="nama_pemohon" class="form-control" maxlength="255" value="<?= esc(old('nama_pemohon', $warga->nama_warga)) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="nik_pemohon">No. KTP / NIK</label>
                                    <input type="text" id="nik_pemohon" name="nik_pemohon" class="form-control" maxlength="50" inputmode="numeric" value="<?= esc(old('nik_pemohon', $warga->nik)) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom" for="alamat_pemohon">Alamat Rumah</label>
                                    <input type="text" id="alamat_pemohon" name="alamat_pemohon" class="form-control" maxlength="255" value="<?= esc(old('alamat_pemohon', $alamat)) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" maxlength="50" value="<?= esc(old('tempat_lahir', $warga->tempat_lahir)) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="<?= esc(old('tanggal_lahir', $tgl)) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="agama">Agama</label>
                                    <select id="agama" name="agama" class="form-select" required>
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($agamaList as $ag): ?>
                                            <option value="<?= esc($ag) ?>" <?= $agamaVal === $ag ? 'selected' : '' ?>><?= esc($ag) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom" for="no_hp">No. Telp / WhatsApp</label>
                                    <input type="tel" id="no_hp" name="no_hp" class="form-control" maxlength="20" placeholder="08xxxxxxxxxx" value="<?= esc(old('no_hp', $hp)) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 2: Maksud & Keperluan -->
                        <div class="mb-4">
                            <div class="form-group-title">
                                <i class="fas fa-file-alt"></i> 2. Maksud &amp; Keperluan Permohonan
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label-custom" for="maksut">Maksud Permohonan</label>
                                    <input type="text" id="maksut" name="maksut" class="form-control" maxlength="100" placeholder="Contoh: Permohonan Pembuatan KTP / SKCK / Keterangan Domisili" value="<?= esc(old('maksut')) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom" for="perlu">Untuk Keperluan</label>
                                    <input type="text" id="perlu" name="perlu" class="form-control" maxlength="100" placeholder="Contoh: Persyaratan melamar pekerjaan / Pendaftaran sekolah" value="<?= esc(old('perlu')) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 3: Berkas Lampiran Pendukung -->
                        <div class="mb-4">
                            <div class="form-group-title">
                                <i class="fas fa-paperclip"></i> 3. Berkas Kelengkapan Pendukung
                            </div>
                            <p class="text-muted small mb-3">Tuliskan nama dokumen/berkas fotokopi yang akan dilampirkan (minimal 1 berkas, kosongkan baris yang tidak digunakan):</p>

                            <?php $oldLampiran = old('lampiran') ?: []; ?>
                            <div class="d-flex flex-column gap-2" id="lampiran-container">
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <div class="d-flex align-items-center gap-2 lampiran-row" id="lampiran-row-<?= $i ?>" style="<?= ($i > 2 && empty($oldLampiran[$i])) ? 'display: none;' : '' ?>">
                                        <div class="lampiran-badge"><?= $i + 1 ?></div>
                                        <input type="text" name="lampiran[]" class="form-control" maxlength="100" placeholder="<?= $i === 0 ? 'Contoh: FC KTP' : ($i === 1 ? 'Contoh: FC Kartu Keluarga' : 'Nama berkas lampiran pendukung...') ?>" value="<?= esc($oldLampiran[$i] ?? '') ?>" <?= $i === 0 ? 'required' : '' ?>>
                                    </div>
                                <?php endfor ?>
                            </div>

                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" id="btn-tambah-lampiran">
                                    <i class="fas fa-plus me-1"></i> Tambah Baris Lampiran
                                </button>
                                <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i>Berkas fisik silakan dibawa saat surat diambil atau ditandatangani.</small>
                            </div>

                            <script>
                                (function () {
                                    var btn = document.getElementById('btn-tambah-lampiran');
                                    if (btn) {
                                        btn.addEventListener('click', function () {
                                            var hiddenRows = document.querySelectorAll('.lampiran-row[style*="display: none"]');
                                            if (hiddenRows.length > 0) {
                                                hiddenRows[0].style.display = 'flex';
                                            }
                                            if (hiddenRows.length <= 1) {
                                                btn.style.display = 'none';
                                            }
                                        });
                                    }
                                })();
                            </script>
                        </div>

                        <!-- Bagian 4: Pernyataan & Keabsahan -->
                        <div class="card bg-light border-0 rounded-4 p-4 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle bg-warning bg-opacity-25 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                    <i class="fas fa-shield-alt text-dark"></i>
                                </div>
                                <div class="small">
                                    <h6 class="fw-bold mb-1 text-dark">Pernyataan Kebenaran &amp; Keabsahan Dokumen</h6>
                                    <p class="mb-2 text-muted" style="line-height: 1.6;">
                                        Data yang terdapat dalam formulir dan lampiran dokumen permohonan ini adalah <strong>Benar dan Sah</strong>. Apabila di kemudian hari ditemukan bahwa dokumen yang diberikan tidak benar, saya bersedia dikenakan sanksi sesuai ketentuan yang berlaku.
                                    </p>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="pernyataan-setuju" required checked>
                                        <label class="form-check-label fw-semibold text-dark" for="pernyataan-setuju">
                                            Saya menyatakan permohonan ini dibuat dengan sebenar-benarnya tanpa paksaan.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7 order-md-2">
                                <button type="submit" class="btn btn-action-primary w-100 py-3 shadow">
                                    <i class="fas fa-paper-plane me-2"></i> Ajukan Surat Pernyataan
                                </button>
                            </div>
                            <div class="col-md-5 order-md-1">
                                <a class="btn btn-outline-secondary rounded-pill w-100 py-3" href="<?= base_url($__prefix . 'layanan/pilih') ?>">
                                    <i class="fas fa-arrow-left me-1"></i> Pilih Orang Lain
                                </a>
                            </div>
                        </div>
                        <?= form_close() ?>
                    <?php endif ?>

                </div>
            </div>
        </div>
    </div>
</section>
