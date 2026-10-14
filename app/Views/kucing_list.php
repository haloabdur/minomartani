<?= view('includes/nav-white') ?>

<?php
$__rtNama    = (isset($rt) && $rt !== null) ? $rt->nama : 'RT 29';
$uri         = service('uri');
$__isSlugged = ($uri->getTotalSegments() > 0 && isset($rt->slug) && $uri->getSegment(1) === $rt->slug);
$__homeUrl   = base_url($__isSlugged ? $rt->slug : '');

// Count cats per owner to immediately display how many cats each owner has
$ownerCountMap = [];
if (!empty($kucings)) {
    foreach ($kucings as $k) {
        $ownerKey = !empty($k->nama_warga) ? trim($k->nama_warga) : 'Tanpa Pemilik';
        $ownerCountMap[$ownerKey] = ($ownerCountMap[$ownerKey] ?? 0) + 1;
    }
}

// Build serialized array for lightbox modal gallery
$modalKucings = [];
if (!empty($kucings)) {
    foreach ($kucings as $idx => $k) {
        $ownerKey = !empty($k->nama_warga) ? trim($k->nama_warga) : 'Tanpa Pemilik';
        $modalKucings[] = [
            'id'          => $k->id_kucing,
            'nama'        => $k->nama,
            'foto'        => !empty($k->foto) ? foto_url($k->foto, 'kucing') : '',
            'pemilik'     => $ownerKey,
            'owner_total' => $ownerCountMap[$ownerKey] ?? 1,
            'jalan'       => !empty($k->jalan) ? $k->jalan : '-',
            'warna_ciri'  => !empty($k->warna_ciri) ? $k->warna_ciri : '-',
            'gender'      => $k->jenis_kelamin ?? '',
            'steril'      => (int) ($k->steril ?? 0),
            'vaksin'      => (int) ($k->vaksin ?? 0),
        ];
    }
}
?>

<style>
    .kucing-card {
        border-radius: 1.25rem;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.07);
        background: #fff;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }
    .kucing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 28px rgba(0, 0, 0, 0.1) !important;
    }
    .kucing-img-wrapper {
        position: relative;
        height: 230px;
        background-color: #f1f5f9;
        overflow: hidden;
    }
    .kucing-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .kucing-card:hover .kucing-img {
        transform: scale(1.05);
    }
    .kucing-img-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.1) 60%, transparent 100%);
        opacity: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.25s ease;
    }
    .kucing-card:hover .kucing-img-overlay {
        opacity: 1;
    }
    .kucing-zoom-badge {
        background: rgba(255, 255, 255, 0.9);
        color: #1e293b;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .kucing-modal-stage {
        position: relative;
        background: #0b0f19;
        min-height: 280px;
        max-height: 56vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .kucing-modal-img {
        max-height: 54vh;
        max-width: 100%;
        object-fit: contain;
        transition: opacity 0.2s ease-in-out;
    }
    .kucing-modal-nav-btn {
        width: 46px;
        height: 46px;
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
        z-index: 10;
    }
    .kucing-modal-nav-btn:hover {
        background: rgba(15, 23, 42, 0.95);
        color: #fff;
        transform: translateY(-50%) scale(1.1) !important;
    }
    .badge-soft-primary { background-color: #e0f2fe; color: #0284c7; }
    .badge-soft-danger { background-color: #fee2e2; color: #dc2626; }
    .badge-soft-success { background-color: #dcfce7; color: #16a34a; }
    .badge-soft-secondary { background-color: #f1f5f9; color: #475569; }
</style>

<section class="page-section bg-light detail-page-section" id="daftar-kucing" style="min-height: 85vh;">
    <div class="container">
        <!-- Back Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?= $__homeUrl ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <!-- Header, Counter & Search -->
        <div class="row align-items-end mb-4 g-3">
            <div class="col-lg-7 col-md-6">
                <h3 class="section-subheading mb-1 text-muted text-uppercase" style="letter-spacing: 1px;">PENDATAAN</h3>
                <h2 class="section-heading mb-1">Kucing Terdaftar <?= esc($__rtNama) ?></h2>
                <p class="text-muted mb-2">Kucing yang tidak ada dalam daftar ini dianggap kucing liar.</p>

                <!-- Total Kucing Info -->
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6 shadow-sm">
                        <i class="fas fa-cat me-1"></i> Total Kucing: <strong><?= (int)($totalKucing ?? count($kucings)) ?></strong> Ekor
                    </span>
                    <?php if (!empty($cari)): ?>
                        <span class="badge bg-dark rounded-pill px-3 py-2 fs-6 shadow-sm">
                            <i class="fas fa-search me-1"></i> Hasil: <strong><?= count($kucings) ?></strong> Kucing
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                <form method="get" action="<?= current_url() ?>">
                    <div class="input-group shadow-sm" style="border-radius: 2rem; overflow: hidden; background: #fff;">
                        <input type="text" name="cari" class="form-control border-0 px-4 py-2" placeholder="Cari nama kucing atau pemilik..." value="<?= esc($cari ?? '') ?>" aria-label="Cari nama kucing atau pemilik">
                        <button class="btn btn-primary px-4" type="submit" aria-label="Cari"><i class="fas fa-search"></i></button>
                        <?php if (!empty($cari)): ?>
                            <a href="<?= current_url() ?>" class="btn btn-light px-3 d-flex align-items-center" title="Reset pencarian" aria-label="Reset"><i class="fas fa-times text-muted"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="row g-4">
            <?php if (empty($kucings)): ?>
                <div class="col-12 text-center py-5">
                    <div class="card border-0 shadow-sm p-5 rounded-4 bg-white">
                        <h4 class="text-secondary fw-bold">Belum Ada Kucing Terdaftar</h4>
                        <?php if (!empty($cari)): ?>
                            <p class="text-muted">Tidak ada kucing dengan pencarian "<strong><?= esc($cari) ?></strong>".</p>
                            <div>
                                <a href="<?= current_url() ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Tampilkan Semua Kucing</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($kucings as $i => $k): ?>
                    <?php
                    $ownerName  = !empty($k->nama_warga) ? trim($k->nama_warga) : 'Tanpa Pemilik';
                    $ownerCount = $ownerCountMap[$ownerName] ?? 1;
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex">
                        <div class="card w-100 shadow-sm kucing-card" onclick="openKucingModal(<?= $i ?>)" role="button" tabindex="0" aria-label="Lihat foto dan detail <?= esc($k->nama) ?>">
                            <div class="kucing-img-wrapper">
                                <?php if (!empty($k->foto)): ?>
                                    <img src="<?= foto_url($k->foto, 'kucing') ?>" alt="<?= esc($k->nama) ?>" class="kucing-img" loading="lazy">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                        <i class="fas fa-cat fa-3x mb-2 text-secondary opacity-50"></i>
                                        <span class="small">Belum ada foto</span>
                                    </div>
                                <?php endif; ?>
                                <div class="kucing-img-overlay">
                                    <span class="kucing-zoom-badge">
                                        <i class="fas fa-search-plus me-1 text-primary"></i> Perbesar Foto
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h5 class="fw-bold mb-0 text-dark"><?= esc($k->nama) ?></h5>
                                    <?php if (($k->jenis_kelamin ?? '') === 'jantan'): ?>
                                        <span class="badge badge-soft-primary rounded-pill small" title="Jantan"><i class="fas fa-mars me-1"></i>Jantan</span>
                                    <?php elseif (($k->jenis_kelamin ?? '') === 'betina'): ?>
                                        <span class="badge badge-soft-danger rounded-pill small" title="Betina"><i class="fas fa-venus me-1"></i>Betina</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Owner Info & Count -->
                                <div class="mt-2 mb-2 p-2 rounded bg-light border border-light">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                                        <span class="text-dark small fw-semibold text-truncate" title="<?= esc($ownerName) ?>">
                                            <i class="fas fa-user-circle text-primary me-1"></i><?= esc($ownerName) ?>
                                        </span>
                                        <span class="badge bg-white text-primary border rounded-pill shadow-xs" title="Pemilik ini memiliki <?= $ownerCount ?> kucing terdaftar">
                                            <i class="fas fa-paw me-1"></i><?= $ownerCount ?> Kucing
                                        </span>
                                    </div>
                                    <?php if (!empty($k->jalan)): ?>
                                        <div class="text-muted small mt-1">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i><?= esc($k->jalan) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($k->warna_ciri)): ?>
                                    <p class="text-muted small mb-2 text-truncate" title="<?= esc($k->warna_ciri) ?>">
                                        <i class="fas fa-palette me-1 text-secondary"></i><?= esc($k->warna_ciri) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                                    <div class="d-flex gap-1">
                                        <?php if (!empty($k->steril)): ?>
                                            <span class="badge badge-soft-success rounded-pill" title="Sudah Disteril"><i class="fas fa-check me-1"></i>Steril</span>
                                        <?php endif; ?>
                                        <?php if (!empty($k->vaksin)): ?>
                                            <span class="badge badge-soft-primary rounded-pill" title="Sudah Divaksin"><i class="fas fa-shield-alt me-1"></i>Vaksin</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-primary small fw-semibold">
                                        Detail <i class="fas fa-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Lightbox Modal for Cat Details and Photo Preview -->
<div class="modal fade" id="kucingModal" tabindex="-1" aria-labelledby="kucingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fas fa-cat text-primary fa-lg me-2"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="kucingModalLabel">Detail & Foto Kucing</h5>
                    <span class="badge bg-light text-secondary border rounded-pill ms-2" id="modalKucingCounterBadge">1 / 1</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <!-- Modal Stage: Enlarged Image with Floating Next & Previous -->
            <div class="kucing-modal-stage position-relative" id="kucingModalStage">
                <!-- Floating Previous Button -->
                <button type="button" class="kucing-modal-nav-btn position-absolute top-50 start-0 translate-middle-y ms-3" id="kucingFloatPrevBtn" title="Kucing Sebelumnya (Keyboard: ←)" aria-label="Sebelumnya">
                    <i class="fas fa-chevron-left fa-lg"></i>
                </button>

                <!-- Cat Image -->
                <img src="" alt="Foto Kucing" class="kucing-modal-img" id="modalKucingImg">

                <!-- Placeholder when no photo -->
                <div id="modalKucingPlaceholder" class="text-center py-5 text-light" style="display: none;">
                    <i class="fas fa-cat fa-4x mb-3 text-secondary opacity-50"></i>
                    <p class="text-muted mb-0">Kucing ini belum memiliki foto.</p>
                </div>

                <!-- Floating Next Button -->
                <button type="button" class="kucing-modal-nav-btn position-absolute top-50 end-0 translate-middle-y me-3" id="kucingFloatNextBtn" title="Kucing Selanjutnya (Keyboard: →)" aria-label="Selanjutnya">
                    <i class="fas fa-chevron-right fa-lg"></i>
                </button>
            </div>

            <!-- Cat Details Below Image -->
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h3 class="fw-bold text-dark mb-1" id="modalKucingNama">-</h3>
                        <div class="text-muted small">
                            <i class="fas fa-user-circle text-primary me-1"></i> Pemilik:
                            <strong id="modalKucingPemilik" class="text-dark">-</strong>
                            <span id="modalKucingPemilikBadge" class="badge bg-light text-primary border rounded-pill ms-1">-</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1" id="modalKucingBadges">
                        <!-- Badges dynamically inserted -->
                    </div>
                </div>

                <div class="row g-3 pt-2 border-top">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted small d-block mb-1">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> Wilayah / Jalan
                            </span>
                            <strong class="text-dark d-block" id="modalKucingJalan">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted small d-block mb-1">
                                <i class="fas fa-palette text-warning me-1"></i> Ciri-ciri Fisik
                            </span>
                            <span class="text-dark fw-medium d-block" id="modalKucingCiri">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer: Navigation Controls -->
            <div class="modal-footer bg-light px-4 py-2 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" id="kucingFooterPrevBtn">
                    <i class="fas fa-chevron-left me-1"></i> Sebelumnya
                </button>
                <span class="text-muted small fw-semibold" id="modalKucingFooterCounter">Kucing 1 dari 1</span>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="kucingFooterNextBtn">
                    Selanjutnya <i class="fas fa-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Safely embed kucing data for client-side navigation -->
<script id="kucing-gallery-data" type="application/json">
<?= json_encode($modalKucings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
</script>

<script>
    (function() {
        let kucings = [];
        const dataEl = document.getElementById('kucing-gallery-data');
        if (dataEl && dataEl.textContent) {
            try {
                kucings = JSON.parse(dataEl.textContent);
            } catch (e) {
                console.error("Gagal parse data kucing:", e);
            }
        }

        let currentIndex = 0;
        let bsModal = null;

        function renderModal(index) {
            if (!kucings || kucings.length === 0) return;
            if (index < 0) index = kucings.length - 1;
            if (index >= kucings.length) index = 0;
            currentIndex = index;

            const cat = kucings[currentIndex];
            const total = kucings.length;

            // Counters
            const counterText = `${currentIndex + 1} / ${total}`;
            const footerCounterText = `Kucing ${currentIndex + 1} dari ${total}`;
            document.getElementById('modalKucingCounterBadge').textContent = counterText;
            document.getElementById('modalKucingFooterCounter').textContent = footerCounterText;

            // Image
            const imgEl = document.getElementById('modalKucingImg');
            const placeholderEl = document.getElementById('modalKucingPlaceholder');

            if (cat.foto) {
                imgEl.style.opacity = '0';
                imgEl.style.display = 'block';
                placeholderEl.style.display = 'none';
                imgEl.src = cat.foto;
                imgEl.alt = cat.nama;
                imgEl.onload = function() {
                    imgEl.style.opacity = '1';
                };
            } else {
                imgEl.style.display = 'none';
                placeholderEl.style.display = 'block';
            }

            // Text info
            document.getElementById('modalKucingNama').textContent = cat.nama;
            document.getElementById('modalKucingPemilik').textContent = cat.pemilik;
            document.getElementById('modalKucingPemilikBadge').textContent = (cat.owner_total || 1) + ' Kucing';
            document.getElementById('modalKucingJalan').textContent = cat.jalan || '-';
            document.getElementById('modalKucingCiri').textContent = cat.warna_ciri || '-';

            // Badges
            const badgesContainer = document.getElementById('modalKucingBadges');
            badgesContainer.innerHTML = '';

            if (cat.gender === 'jantan') {
                badgesContainer.innerHTML += '<span class="badge badge-soft-primary rounded-pill px-3 py-2"><i class="fas fa-mars me-1"></i>Jantan</span>';
            } else if (cat.gender === 'betina') {
                badgesContainer.innerHTML += '<span class="badge badge-soft-danger rounded-pill px-3 py-2"><i class="fas fa-venus me-1"></i>Betina</span>';
            }

            if (cat.steril) {
                badgesContainer.innerHTML += '<span class="badge badge-soft-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i>Steril</span>';
            } else {
                badgesContainer.innerHTML += '<span class="badge badge-soft-secondary rounded-pill px-3 py-2 text-muted">Belum Steril</span>';
            }

            if (cat.vaksin) {
                badgesContainer.innerHTML += '<span class="badge badge-soft-primary rounded-pill px-3 py-2"><i class="fas fa-shield-alt me-1"></i>Vaksin</span>';
            } else {
                badgesContainer.innerHTML += '<span class="badge badge-soft-secondary rounded-pill px-3 py-2 text-muted">Belum Vaksin</span>';
            }
        }

        window.openKucingModal = function(index) {
            renderModal(index);
            const modalEl = document.getElementById('kucingModal');
            if (window.bootstrap && window.bootstrap.Modal) {
                bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.show();
            } else if (typeof $('#kucingModal').modal === 'function') {
                $('#kucingModal').modal('show');
            }
        };

        function showNext() {
            renderModal(currentIndex + 1);
        }

        function showPrev() {
            renderModal(currentIndex - 1);
        }

        // Attach Click Handlers
        document.getElementById('kucingFloatPrevBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            showPrev();
        });
        document.getElementById('kucingFloatNextBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            showNext();
        });
        document.getElementById('kucingFooterPrevBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            showPrev();
        });
        document.getElementById('kucingFooterNextBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            showNext();
        });

        // Keyboard Navigation (ArrowLeft / ArrowRight)
        document.addEventListener('keydown', function(e) {
            const modalEl = document.getElementById('kucingModal');
            if (modalEl && modalEl.classList.contains('show')) {
                if (e.key === 'ArrowLeft') {
                    showPrev();
                } else if (e.key === 'ArrowRight') {
                    showNext();
                }
            }
        });

        // Touch Swipe Navigation for Mobile
        const stage = document.getElementById('kucingModalStage');
        let touchStartX = 0;
        let touchEndX = 0;

        stage.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        stage.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleGesture();
        }, { passive: true });

        function handleGesture() {
            const swipeDistance = touchEndX - touchStartX;
            if (Math.abs(swipeDistance) > 40) {
                if (swipeDistance > 0) {
                    showPrev(); // Swiped right -> go to previous
                } else {
                    showNext(); // Swiped left -> go to next
                }
            }
        }
    })();
</script>
