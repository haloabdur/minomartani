# Export Warga — Semua Detail + Pilihan Kolom Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the resident (warga) data export show all 22 detail fields by default, with a column-picker modal to export only a subset, plus a checkbox to include/exclude deceased residents — applied to both export entry points (Kelola Warga and Rekap RT).

**Architecture:** `WargaModel` gains an `EXPORT_COLUMNS` map (key → label) that both controllers and the shared `export_warga.php` view read from, a `resolveExportColumns()` helper to parse/validate the `columns` GET param, and an `$includeDeceased` flag on `export()` that adds a `is_hidup = 1` filter unless overridden. The view loops over the resolved column list instead of hardcoding 3 columns. A new Bootstrap modal (mirrored on both list pages) lets the user check/uncheck columns and toggle "include deceased", then builds the export URL client-side, reusing the `type`/`value` row-filter state that already exists on both pages.

**Tech Stack:** CodeIgniter 4, jQuery + Bootstrap 4 (already loaded via `layouts/footer.php`), PHP 8.

## Global Constraints

- Default export (no `columns` param) = all 22 fields from `WargaModel::EXPORT_COLUMNS`, and only `is_hidup = 1` residents unless `include_deceased=1` is passed.
- Column picker is a **modal with checkboxes**, not a dropdown or presets.
- Applies to **both** entry points: `Admin\Warga::export()` (`admin/warga/export`) and `Admin\Rekap::export($idRt)` (`admin/rekap/warga/(:num)/export`) — they share the same view and model.
- Existing `type`/`value` row filters (gender / age-group / education, from the `.filter-trigger` cards) are untouched and must keep working exactly as today.
- Label mappings must match what's already used in `ubah_warga.php`: `status_kawin` 0=`Belum Kawin`, 1=`Kawin`, 2=`Cerai Hidup`, 3=`Cerai Mati`; `jenis_kelamin` `L`=`Laki-Laki`, `P`=`Perempuan`; `is_hidup` 0=`Meninggal`, 1=`Hidup`.
- No new automated tests — this matches the existing export feature (zero test coverage today); verify manually via the dev server (see Task 8). This is a deliberate, spec-approved deviation from the usual TDD default.
- Full design context: `docs/superpowers/specs/2026-07-04-export-warga-kolom-pilihan-design.md`.

---

### Task 1: `WargaModel` — column map, pekerjaan join, deceased filter

**Files:**
- Modify: `app/Models/WargaModel.php:127-179` (the `export()` method)

**Interfaces:**
- Produces: `WargaModel::EXPORT_COLUMNS` (public array constant, `key => label`), `WargaModel::resolveExportColumns(?string $columnsParam): array` (public static method, returns a non-empty array of valid keys), `WargaModel::export($type, $value, bool $includeDeceased = false)` (instance method, unchanged return shape — array of `stdClass` rows, now including `nama_pekerjaan` and always respecting `is_hidup` unless `$includeDeceased` is true).
- Consumes: nothing new (uses existing `current_rt_id()` helper, already in file).

- [ ] **Step 1: Replace the `export()` method and add the column map + resolver**

Replace lines 127-179 (the whole current `export()` method) with:

```php
    public const EXPORT_COLUMNS = [
        'nama_warga'      => 'Nama Lengkap',
        'nik'             => 'NIK',
        'no_kk'           => 'No. KK',
        'jenis_kelamin'   => 'Jenis Kelamin',
        'tempat_lahir'    => 'Tempat Lahir',
        'tanggal_lahir'   => 'Tanggal Lahir',
        'gol_darah'       => 'Golongan Darah',
        'agama'           => 'Agama',
        'pendidikan'      => 'Pendidikan',
        'nama_pekerjaan'  => 'Pekerjaan',
        'status_kawin'    => 'Status Kawin',
        'tanggal_kawin'   => 'Tanggal Kawin',
        'status_keluarga' => 'Status dlm Keluarga',
        'ayah'            => 'Nama Ayah',
        'ibu'             => 'Nama Ibu',
        'no_hp'           => 'No. HP',
        'email'           => 'Email',
        'status_penduduk' => 'Status Penduduk',
        'sumber_air'      => 'Sumber Air',
        'alamat'          => 'Alamat (Blok)',
        'alamat_lengkap'  => 'Alamat Lengkap',
        'is_hidup'        => 'Status Hidup',
    ];

    /**
     * Parse the `columns` GET param (comma-separated keys) into a
     * validated, non-empty list of EXPORT_COLUMNS keys. Falls back to
     * all columns when the param is missing, empty, or contains no
     * recognized key.
     */
    public static function resolveExportColumns(?string $columnsParam): array
    {
        if ($columnsParam === null || $columnsParam === '') {
            return array_keys(self::EXPORT_COLUMNS);
        }

        $columns = array_values(array_intersect(explode(',', $columnsParam), array_keys(self::EXPORT_COLUMNS)));

        return empty($columns) ? array_keys(self::EXPORT_COLUMNS) : $columns;
    }

    public function export($type = null, $value = null, bool $includeDeceased = false)
    {
        $builder = $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk, pekerjaan.nama_pekerjaan nama_pekerjaan')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
            ->join('pekerjaan', 'pekerjaan.id_pekerjaan = warga.id_pekerjaan', 'left')
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id());

        if (!$includeDeceased) {
            $builder->where('warga.is_hidup', 1);
        }

        if (!empty($type) && !empty($value)) {
            if ($type === 'gender') {
                $builder->where('jenis_kelamin', $value);
            } else if ($type === 'age-group') {
                if ($value === 'balita') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 5);
                } else if ($value === 'anak') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 6);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 11);
                } else if ($value === 'remaja') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 12);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 25);
                } else if ($value === 'dewasa') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 26);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 59);
                } else if ($value === 'lansia') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 60);
                }
            } else if ($type === 'education') {
                if ($value === 'BELUM_SEKOLAH') {
                    $builder->groupStart()
                        ->where('warga.pendidikan', '-')
                        ->orWhere('warga.pendidikan', '')
                        ->orWhere('warga.pendidikan', null)
                        ->groupEnd();
                } else if ($value === 'SD') {
                    $builder->where('warga.pendidikan', 'SD');
                } else if ($value === 'SMP') {
                    $builder->where('warga.pendidikan', 'SMP');
                } else if ($value === 'SMA') {
                    $builder->groupStart()
                        ->where('warga.pendidikan', 'SMA')
                        ->orWhere('warga.pendidikan', 'SMK')
                        ->groupEnd();
                } else if ($value === 'KULIAH') {
                    $builder->whereIn('warga.pendidikan', ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']);
                }
            }
        }

        return $builder->get()->getResult();
    }
```

Note: only the `select()` line, the new `EXPORT_COLUMNS`/`resolveExportColumns()` additions, the `$includeDeceased` parameter, and the `is_hidup` filter block are new — the `type`/`value` filter logic below it is copied unchanged from the current file.

- [ ] **Step 2: Lint the file**

Run: `php -l app/Models/WargaModel.php`
Expected: `No syntax errors detected in app/Models/WargaModel.php`

- [ ] **Step 3: Commit**

```bash
git add app/Models/WargaModel.php
git commit -m "feat: add export column map and deceased filter to WargaModel"
```

---

### Task 2: Field-formatting helper for the export view

**Files:**
- Modify: `app/Helpers/kbw_helper.php` (append new function)

**Interfaces:**
- Produces: `export_format_warga_field(string $key, object $row): string` — global function, globally autoloaded (this file is already in `Config\Autoload::$helpers`, per `BaseController::initController()`).
- Consumes: nothing new.

- [ ] **Step 1: Append the helper function**

`loadFlashData()` is the last function in `app/Helpers/kbw_helper.php`. Find its
closing lines (end of file):

```php
        } else {
            return '';
        }
    }
}
```

Insert the new function immediately after that closing `}` (end of file):

```php

/**
 * Format one WargaModel::EXPORT_COLUMNS field for display in the
 * export table. Handles the 3 coded fields (jenis_kelamin,
 * status_kawin, is_hidup); everything else is echoed as-is.
 */
if (!function_exists('export_format_warga_field')) {
    function export_format_warga_field(string $key, object $row): string
    {
        $value = $row->{$key} ?? null;

        if ($key === 'jenis_kelamin') {
            return $value === 'L' ? 'Laki-Laki' : ($value === 'P' ? 'Perempuan' : '-');
        }

        if ($key === 'status_kawin') {
            $labels = ['0' => 'Belum Kawin', '1' => 'Kawin', '2' => 'Cerai Hidup', '3' => 'Cerai Mati'];
            return $labels[(string) $value] ?? '-';
        }

        if ($key === 'is_hidup') {
            return ((int) $value === 1) ? 'Hidup' : 'Meninggal';
        }

        return ($value !== null && $value !== '') ? esc((string) $value) : '-';
    }
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l app/Helpers/kbw_helper.php`
Expected: `No syntax errors detected in app/Helpers/kbw_helper.php`

- [ ] **Step 3: Commit**

```bash
git add app/Helpers/kbw_helper.php
git commit -m "feat: add export_format_warga_field helper"
```

---

### Task 3: Rewrite `export_warga.php` to render dynamic columns

**Files:**
- Modify: `app/Views/admin/export_warga.php` (whole file)

**Interfaces:**
- Consumes: `$content` (array of `stdClass` rows from `WargaModel::export()`, as before), `$columns` (array of valid `WargaModel::EXPORT_COLUMNS` keys — always provided by the controller, non-empty), `WargaModel::EXPORT_COLUMNS`, `export_format_warga_field()`.

- [ ] **Step 1: Replace the whole file**

```php
<?php

header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=datart29_" . date('dmY-His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
ob_end_clean();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Data Warga RT 029 RW 006</title>
    </head>
    <body>

        <?php if ($content != NULL) : ?>
            <table cellspacing=0 border=1>
                <tr>
                    <th style=min-width:50px;text-align:center;font-weight:bold>NO</th>
                    <?php foreach ($columns as $key) : ?>
                        <th style=min-width:50px;text-align:center;font-weight:bold><?php echo esc(\App\Models\WargaModel::EXPORT_COLUMNS[$key]); ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php
                foreach ($content as $i=>$value) :
                    ?>
                    <tr>
                        <td style=min-width:50px;><?php echo ($i+1); ?></td>
                        <?php foreach ($columns as $key) : ?>
                            <td style=min-width:50px;><?php echo export_format_warga_field($key, $value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php
                endforeach;
                ?>
            </table>
        <?php
        else :
            echo "<h2>Data tidak ditemukan!</h2>";
        endif;
        ?>
    </body>
</html>
```

- [ ] **Step 2: Lint the file**

Run: `php -l app/Views/admin/export_warga.php`
Expected: `No syntax errors detected in app/Views/admin/export_warga.php`

- [ ] **Step 3: Commit**

```bash
git add app/Views/admin/export_warga.php
git commit -m "feat: render export_warga table with dynamic column list"
```

---

### Task 4: `Admin\Warga::export()` — read columns + include_deceased

**Files:**
- Modify: `app/Controllers/Admin/Warga.php:157-163`

**Interfaces:**
- Consumes: `WargaModel::resolveExportColumns()`, `WargaModel::EXPORT_COLUMNS`, `$this->wargaModel->export($type, $value, bool $includeDeceased)` (all from Task 1).

- [ ] **Step 1: Replace the `export()` method**

Replace lines 157-163:

```php
    public function export()
    {
        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');
        $data['content'] = $this->wargaModel->export($type, $value);
        return view('admin/export_warga', $data);
    }
```

with:

```php
    public function export()
    {
        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');

        $data['columns'] = WargaModel::resolveExportColumns($this->request->getGet('columns'));
        $includeDeceased = $this->request->getGet('include_deceased') === '1';

        $data['content'] = $this->wargaModel->export($type, $value, $includeDeceased);
        return view('admin/export_warga', $data);
    }
```

(`WargaModel` is already imported at the top of this file — no new `use` statement needed.)

- [ ] **Step 2: Lint the file**

Run: `php -l app/Controllers/Admin/Warga.php`
Expected: `No syntax errors detected in app/Controllers/Admin/Warga.php`

- [ ] **Step 3: Commit**

```bash
git add app/Controllers/Admin/Warga.php
git commit -m "feat: support column/deceased filters in Admin\\Warga::export"
```

---

### Task 5: `Admin\Rekap::export($idRt)` — same params

**Files:**
- Modify: `app/Controllers/Admin/Rekap.php:58-78`

**Interfaces:**
- Consumes: same as Task 4.

- [ ] **Step 1: Replace the `export()` method**

Replace lines 73-77 (the body from `$type = ...` down to the `view(...)` call):

```php
        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');
        $data['content'] = (new WargaModel())->export($type, $value);

        return view('admin/export_warga', $data);
```

with:

```php
        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');

        $data['columns'] = WargaModel::resolveExportColumns($this->request->getGet('columns'));
        $includeDeceased = $this->request->getGet('include_deceased') === '1';

        $data['content'] = (new WargaModel())->export($type, $value, $includeDeceased);

        return view('admin/export_warga', $data);
```

(The RW-scoping checks above this block, lines 58-71, are untouched.)

- [ ] **Step 2: Lint the file**

Run: `php -l app/Controllers/Admin/Rekap.php`
Expected: `No syntax errors detected in app/Controllers/Admin/Rekap.php`

- [ ] **Step 3: Commit**

```bash
git add app/Controllers/Admin/Rekap.php
git commit -m "feat: support column/deceased filters in Admin\\Rekap::export"
```

---

### Task 6: Column-picker modal on `admin/warga.php`

**Files:**
- Modify: `app/Views/admin/warga.php` (button row ~line 58, modal insertion before the closing `<script>` block, JS additions inside the existing `DOMContentLoaded` handler)

**Interfaces:**
- Consumes: `activeFilterType` / `activeFilterValue` (existing JS variables in this file's script, declared `let` inside `DOMContentLoaded`), `\App\Models\WargaModel::EXPORT_COLUMNS`.

- [ ] **Step 1: Add the gear button next to "Export Data"**

Replace:

```php
		<div class="col text-right"><a id="btn-export-warga" href="<?php echo base_url('admin/warga/export') ?>" class="btn btn-success">Export Data</a></div>
```

with:

```php
		<div class="col text-right">
			<a id="btn-export-warga" href="<?php echo base_url('admin/warga/export') ?>" class="btn btn-success">Export Data</a>
			<button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-export-custom" title="Pilih kolom export">
				<i class="fas fa-cog"></i>
			</button>
		</div>
```

- [ ] **Step 2: Insert the modal before the closing `<script>` block**

Find the end of the file, where it reads:

```php
<script>
	document.addEventListener("DOMContentLoaded", function() {
		let activeFilterType = null;
```

Insert this modal markup immediately **before** that `<script>` tag:

```php
<div class="modal fade" id="modal-export-custom" tabindex="-1" role="dialog" aria-labelledby="modalExportCustomLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalExportCustomLabel">Pilih Kolom Export</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="mb-2">
					<a href="javascript:void(0)" id="export-columns-select-all">Pilih Semua</a> /
					<a href="javascript:void(0)" id="export-columns-select-none">Kosongkan Semua</a>
				</div>
				<div class="row">
					<?php foreach (\App\Models\WargaModel::EXPORT_COLUMNS as $key => $label) : ?>
						<div class="col-md-4">
							<div class="form-check">
								<input class="form-check-input export-column-checkbox" type="checkbox" value="<?php echo esc($key) ?>" id="export-col-<?php echo esc($key) ?>" checked>
								<label class="form-check-label" for="export-col-<?php echo esc($key) ?>"><?php echo esc($label) ?></label>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<hr>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="export-include-deceased">
					<label class="form-check-label" for="export-include-deceased">Sertakan warga yang sudah meninggal</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
				<button type="button" class="btn btn-success" id="btn-export-custom-confirm">Export</button>
			</div>
		</div>
	</div>
</div>

```

- [ ] **Step 3: Add the modal's JS handlers**

Find this block near the end of the `<script>` (the reset-filter handler):

```php
			// Reset Export link to default
			jQuery('#btn-export-warga').attr('href', "<?php echo base_url('admin/warga/export') ?>");

			jQuery('.datatable').DataTable().draw();
		});
	});
</script>
```

Replace it with:

```php
			// Reset Export link to default
			jQuery('#btn-export-warga').attr('href', "<?php echo base_url('admin/warga/export') ?>");

			jQuery('.datatable').DataTable().draw();
		});

		jQuery('#export-columns-select-all').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', true);
		});

		jQuery('#export-columns-select-none').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', false);
		});

		jQuery('#btn-export-custom-confirm').on('click', function() {
			var selected = jQuery('.export-column-checkbox:checked').map(function() {
				return this.value;
			}).get();

			var params = new URLSearchParams();
			if (activeFilterType) {
				params.set('type', activeFilterType);
				params.set('value', activeFilterValue);
			}
			params.set('columns', selected.join(','));
			params.set('include_deceased', jQuery('#export-include-deceased').is(':checked') ? '1' : '0');

			window.location.href = "<?php echo base_url('admin/warga/export') ?>?" + params.toString();
		});
	});
</script>
```

- [ ] **Step 4: Lint the file**

Run: `php -l app/Views/admin/warga.php`
Expected: `No syntax errors detected in app/Views/admin/warga.php`

- [ ] **Step 5: Commit**

```bash
git add app/Views/admin/warga.php
git commit -m "feat: add export column-picker modal to Kelola Warga"
```

---

### Task 7: Column-picker modal on `admin/rekap_warga.php`

**Files:**
- Modify: `app/Views/admin/rekap_warga.php` (button row ~line 58, modal insertion before the closing `<script>` block, JS additions inside the existing `DOMContentLoaded` handler)

**Interfaces:**
- Same as Task 6, mirrored on this page. Export URL base is `admin/rekap/warga/{$rt->id_rt}/export` instead of `admin/warga/export`.

- [ ] **Step 1: Add the gear button next to "Export Data"**

Replace:

```php
		<div class="col text-right"><a id="btn-export-warga" href="<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>" class="btn btn-success">Export Data</a></div>
```

with:

```php
		<div class="col text-right">
			<a id="btn-export-warga" href="<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>" class="btn btn-success">Export Data</a>
			<button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-export-custom" title="Pilih kolom export">
				<i class="fas fa-cog"></i>
			</button>
		</div>
```

- [ ] **Step 2: Insert the modal before the closing `<script>` block**

Find the end of the file, where it reads:

```php
<script>
	document.addEventListener("DOMContentLoaded", function() {
		let activeFilterType = null;
```

Insert this modal markup immediately **before** that `<script>` tag (identical to Task 6's modal):

```php
<div class="modal fade" id="modal-export-custom" tabindex="-1" role="dialog" aria-labelledby="modalExportCustomLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalExportCustomLabel">Pilih Kolom Export</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="mb-2">
					<a href="javascript:void(0)" id="export-columns-select-all">Pilih Semua</a> /
					<a href="javascript:void(0)" id="export-columns-select-none">Kosongkan Semua</a>
				</div>
				<div class="row">
					<?php foreach (\App\Models\WargaModel::EXPORT_COLUMNS as $key => $label) : ?>
						<div class="col-md-4">
							<div class="form-check">
								<input class="form-check-input export-column-checkbox" type="checkbox" value="<?php echo esc($key) ?>" id="export-col-<?php echo esc($key) ?>" checked>
								<label class="form-check-label" for="export-col-<?php echo esc($key) ?>"><?php echo esc($label) ?></label>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<hr>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="export-include-deceased">
					<label class="form-check-label" for="export-include-deceased">Sertakan warga yang sudah meninggal</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
				<button type="button" class="btn btn-success" id="btn-export-custom-confirm">Export</button>
			</div>
		</div>
	</div>
</div>

```

- [ ] **Step 3: Add the modal's JS handlers**

Find this block near the end of the `<script>` (the reset-filter handler):

```php
			jQuery('#btn-export-warga').attr('href', "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>");

			jQuery('.datatable').DataTable().draw();
		});
	});
</script>
```

Replace it with:

```php
			jQuery('#btn-export-warga').attr('href', "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>");

			jQuery('.datatable').DataTable().draw();
		});

		jQuery('#export-columns-select-all').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', true);
		});

		jQuery('#export-columns-select-none').on('click', function() {
			jQuery('.export-column-checkbox').prop('checked', false);
		});

		jQuery('#btn-export-custom-confirm').on('click', function() {
			var selected = jQuery('.export-column-checkbox:checked').map(function() {
				return this.value;
			}).get();

			var params = new URLSearchParams();
			if (activeFilterType) {
				params.set('type', activeFilterType);
				params.set('value', activeFilterValue);
			}
			params.set('columns', selected.join(','));
			params.set('include_deceased', jQuery('#export-include-deceased').is(':checked') ? '1' : '0');

			window.location.href = "<?= base_url('admin/rekap/warga/' . $rt->id_rt . '/export') ?>?" + params.toString();
		});
	});
</script>
```

- [ ] **Step 4: Lint the file**

Run: `php -l app/Views/admin/rekap_warga.php`
Expected: `No syntax errors detected in app/Views/admin/rekap_warga.php`

- [ ] **Step 5: Commit**

```bash
git add app/Views/admin/rekap_warga.php
git commit -m "feat: add export column-picker modal to Rekap RT warga list"
```

---

### Task 8: Manual end-to-end verification

No file changes in this task — it verifies Tasks 1-7 together against the real app, per the project's convention of manual verification for the export feature (see Global Constraints).

- [ ] **Step 1: Confirm no routing regressions**

Run: `php spark routes`
Expected: `warga/export` still maps to `Admin\Warga::export`, and `warga/(:num)/export` still maps to `Admin\Rekap::export/$1` (this task only added query params, no new routes).

- [ ] **Step 2: Start the dev server**

Run: `php spark serve --port 8082`

- [ ] **Step 3: Verify default export (all columns, alive-only) on Kelola Warga**

In a browser, log in as an admin/superadmin, go to `http://localhost:8082/admin/warga`, click **Export Data** (the green button, not the gear icon). Open the downloaded `.xls` file. Expected: 22 data columns (NO + the 21 labeled columns from `EXPORT_COLUMNS`, in the order defined), and no row for any resident whose `is_hidup = 0` (if such test data exists).

- [ ] **Step 4: Verify the column-picker modal on Kelola Warga**

Click the gear icon next to Export Data. Expected: a modal opens with one checkbox per column (all checked), a "Pilih Semua / Kosongkan Semua" toggle, an "Sertakan warga yang sudah meninggal" checkbox (unchecked), and "Batal"/"Export" buttons.

Uncheck all but "Nama Lengkap" and "NIK", check "Sertakan warga yang sudah meninggal", click **Export**. Expected: downloaded file has exactly 2 data columns (Nama Lengkap, NIK) and, if any deceased resident exists in the data, includes them too.

- [ ] **Step 5: Verify the existing row-filter still works together with columns**

On `admin/warga`, click one of the "Kelompok Umur" or "Pendidikan" filter-trigger links (e.g. "Balita (0-5 th)"), then open the gear modal and export with a couple of columns selected. Expected: exported rows are still restricted to that age group (row filter unaffected), with only the selected columns.

- [ ] **Step 6: Repeat on Rekap RT**

Log in as an RW or superadmin account, go to `http://localhost:8082/admin/rekap`, open a specific RT's warga list (`admin/rekap/warga/{id_rt}`), and repeat Steps 3-5 there. Expected: identical behavior, scoped to that RT.
