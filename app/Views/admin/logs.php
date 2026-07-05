<?php
$levelBadge = [
    'emergency' => 'danger',
    'alert' => 'danger',
    'critical' => 'danger',
    'error' => 'danger',
    'warning' => 'warning',
    'notice' => 'info',
    'info' => 'info',
    'debug' => 'secondary',
];
?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-end">
                    <form action="<?= base_url('admin/logs') ?>" method="get" class="form-row align-items-end flex-grow-1">
                        <div class="form-group col-auto mb-2">
                            <label for="date" class="mb-1">Tanggal</label>
                            <input type="date" id="date" name="date" class="form-control form-control-sm" value="<?= esc($date) ?>">
                        </div>
                        <div class="form-group col-auto mb-2">
                            <label for="level" class="mb-1">Level</label>
                            <select id="level" name="level" class="form-control form-control-sm">
                                <option value="">Semua Level</option>
                                <?php foreach ($levels as $lvl): ?>
                                    <option value="<?= esc($lvl) ?>" <?= $level === $lvl ? 'selected' : '' ?>><?= esc(strtoupper($lvl)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-auto mb-2">
                            <label for="q" class="mb-1">Cari</label>
                            <input type="text" id="q" name="q" class="form-control form-control-sm" placeholder="kata kunci pesan..." value="<?= esc($search) ?>">
                        </div>
                        <div class="form-group col-auto mb-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter mr-1"></i> Tampilkan
                            </button>
                        </div>
                    </form>

                    <?php if ($logFileExists): ?>
                        <form action="<?= base_url('admin/logs/delete') ?>" method="post" class="mb-2" onsubmit="return confirm('Hapus file log tanggal <?= esc($date) ?>? Tindakan ini tidak bisa dibatalkan.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash-alt mr-1"></i> Hapus Log Tanggal Ini
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Komposisi Level Log - <?= esc($date) ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!$logFileExists): ?>
                        <div class="text-muted">Tidak ada file log untuk tanggal ini.</div>
                    <?php else: ?>
                        <canvas id="logLevelChart" height="90"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i> Daftar Log (<?= (int) $totalEntries ?> entri)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Waktu</th>
                                <th style="width: 110px;">Level</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada log yang cocok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $i => $entry): ?>
                                    <tr>
                                        <td><?= esc($entry['timestamp']) ?></td>
                                        <td><span class="badge badge-<?= $levelBadge[$entry['level']] ?? 'secondary' ?>"><?= esc(strtoupper($entry['level'])) ?></span></td>
                                        <td>
                                            <?= esc($entry['message']) ?>
                                            <?php if ($entry['context'] !== ''): ?>
                                                <div>
                                                    <a href="#" class="small" data-toggle="collapse" data-target="#ctx-<?= $i ?>">
                                                        <i class="fas fa-chevron-down mr-1"></i>Detail stack trace
                                                    </a>
                                                    <pre id="ctx-<?= $i ?>" class="collapse bg-light p-2 mt-1 mb-0" style="font-size: 0.8rem;"><?= esc($entry['context']) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination pagination-sm mb-0 justify-content-center">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= base_url('admin/logs') ?>?date=<?= urlencode($date) ?>&level=<?= urlencode($level) ?>&page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($logFileExists): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('logLevelChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map('strtoupper', array_keys($counts))) ?>,
            datasets: [{
                label: 'Jumlah Entri',
                data: <?= json_encode(array_values($counts)) ?>,
                backgroundColor: [
                    '#dc3545', '#dc3545', '#dc3545', '#dc3545',
                    '#ffc107', '#17a2b8', '#17a2b8', '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>
<?php endif; ?>
