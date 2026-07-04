# Spec: Export Data Warga — Semua Detail + Pilihan Kolom

Tanggal: 2026-07-04
Status: Disetujui (brainstorming selesai)

## Tujuan

Saat ini export data warga (dari halaman Kelola Warga maupun Rekap RT) hanya
menampilkan 3 kolom (No, Nama Lengkap, Alamat), padahal tabel `warga` punya
puluhan field. Ubah supaya:

1. Export **default** menampilkan semua detail warga.
2. Ada **opsi** untuk memilih hanya beberapa kolom saja saat export.
3. Ada **filter** untuk menyertakan/tidak menyertakan warga yang sudah meninggal
   (`is_hidup`).

## Keputusan desain (hasil brainstorming)

| Keputusan | Pilihan |
|---|---|
| Cara pilih kolom | Modal checkbox di tombol "Export Data" (bukan dropdown/preset) |
| Cakupan | Diterapkan di kedua entry point: `Admin\Warga::export` dan `Admin\Rekap::export` (berbagi view & model yang sama) |
| Filter status hidup | Checkbox "Sertakan yang sudah meninggal" di modal, default **tidak** dicentang (hanya `is_hidup = 1` yang di-export) |
| Sumber kebenaran daftar kolom | Satu array statis di `WargaModel`, dipakai bareng oleh kedua controller + view (hindari duplikasi 3 tempat) |

## 1. Entry point yang ada saat ini

- `admin/warga/export` → `Admin\Warga::export()` — tombol di `admin/warga.php`,
  sudah kirim `type`/`value` (filter gender/kelompok umur/pendidikan dari
  filter-trigger di halaman).
- `admin/rekap/warga/(:num)/export` → `Admin\Rekap::export($idRt)` — tombol di
  `admin/rekap_warga.php`, kirim `type`/`value` yang sama, dipakai RW/superadmin
  untuk export RT tertentu (dengan validasi RW hanya boleh export RT di
  bawahnya).
- Keduanya memanggil `WargaModel::export($type, $value)` lalu
  `view('admin/export_warga', $data)`.

`type`/`value` memfilter **baris** (siapa yang di-export). Fitur ini menambah
dua filter/parameter baru yang independen: `columns` (kolom apa yang tampil)
dan `include_deceased` (apakah warga meninggal ikut ter-export).

## 2. Daftar kolom (default = semua)

Didefinisikan sebagai satu array asosiatif `key => label` di `WargaModel`
(mis. konstanta `EXPORT_COLUMNS`), urutan sesuai tabel berikut:

| Key | Label | Sumber |
|---|---|---|
| `nama_warga` | Nama Lengkap | `warga` |
| `nik` | NIK | `warga` |
| `no_kk` | No. KK | `warga` |
| `jenis_kelamin` | Jenis Kelamin | `warga` (L/P → Laki-Laki/Perempuan) |
| `tempat_lahir` | Tempat Lahir | `warga` |
| `tanggal_lahir` | Tanggal Lahir | `warga` |
| `gol_darah` | Golongan Darah | `warga` |
| `agama` | Agama | `warga` |
| `pendidikan` | Pendidikan | `warga` |
| `nama_pekerjaan` | Pekerjaan | **baru**: left join ke `pekerjaan` |
| `status_kawin` | Status Kawin | `warga` (0-3 → label, lihat §3) |
| `tanggal_kawin` | Tanggal Kawin | `warga` |
| `status_keluarga` | Status dlm Keluarga | sudah di-join (`status_keluarga.status`) |
| `ayah` | Nama Ayah | `warga` |
| `ibu` | Nama Ibu | `warga` |
| `no_hp` | No. HP | `warga` |
| `email` | Email | `warga` |
| `status_penduduk` | Status Penduduk | sudah di-join (`status_penduduk.label`) |
| `sumber_air` | Sumber Air | `warga` |
| `alamat` | Alamat (Blok) | sudah di-join (`alamat.alamat`) |
| `alamat_lengkap` | Alamat Lengkap | `warga` |
| `is_hidup` | Status Hidup | `warga` (0/1 → Meninggal/Hidup) |

Kolom internal (`id_warga`, `id_alamat`, `id_pekerjaan`, `id_status_keluarga`,
`id_status_penduduk`, `id_user`, `status_warga`, `created_at`, `timestamp`)
tetap tidak pernah ditampilkan.

## 3. Perubahan `WargaModel::export()`

Signature baru:

```php
public function export($type = null, $value = null, array $columns = [], bool $includeDeceased = false)
```

- Tambah `->join('pekerjaan', 'pekerjaan.id_pekerjaan = warga.id_pekerjaan', 'left')`
  supaya `nama_pekerjaan` tersedia (belum ada join ini sekarang).
- Filter baris `is_hidup`: kalau `$includeDeceased === false`, tambah
  `->where('is_hidup', 1)`. Kalau `true`, tidak ada filter tambahan (semua
  ikut, hidup maupun meninggal).
- Mapping label yang harus konsisten dengan yang sudah dipakai di
  `ubah_warga.php`: `status_kawin` 0=`Belum Kawin`, 1=`Kawin`,
  2=`Cerai Hidup`, 3=`Cerai Mati`; `jenis_kelamin` `L`=`Laki-Laki`,
  `P`=`Perempuan`; `is_hidup` 0=`Meninggal`, 1=`Hidup`.
- Logika `type`/`value` (gender/kelompok umur/pendidikan) tidak berubah.
- `$columns` tidak dipakai untuk `SELECT` (query tetap `select('*', ...)` yang
  sudah ada) — filtering kolom cukup dilakukan di view saat render, supaya
  model tetap simpel dan satu query melayani semua kombinasi kolom.

## 4. Perubahan controller

`Admin\Warga::export()` dan `Admin\Rekap::export($idRt)`:

```php
$columnsParam     = $this->request->getGet('columns'); // "nama_warga,nik,alamat"
$columns          = $columnsParam ? explode(',', $columnsParam) : array_keys(WargaModel::EXPORT_COLUMNS);
$includeDeceased  = $this->request->getGet('include_deceased') == '1';

$data['columns']  = $columns; // dikirim ke view untuk urutan+label header
$data['content']  = $this->wargaModel->export($type, $value, $columns, $includeDeceased);
```

## 5. Perubahan view `admin/export_warga.php`

Ganti tabel hardcoded 3 kolom dengan loop atas `$columns`:

```php
<tr>
    <th>NO</th>
    <?php foreach ($columns as $key): ?>
        <th><?= WargaModel::EXPORT_COLUMNS[$key] ?? $key ?></th>
    <?php endforeach; ?>
</tr>
<?php foreach ($content as $i => $value): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <?php foreach ($columns as $key): ?>
            <td><?= export_format_warga_field($key, $value) ?></td>
        <?php endforeach; ?>
    </tr>
<?php endforeach; ?>
```

`export_format_warga_field()` (fungsi kecil, taruh di `kbw_helper.php` atau
method statis `WargaModel`) menangani 3 field yang perlu mapping label:
`jenis_kelamin` (L/P), `status_kawin` (0-3), `is_hidup` (0/1). Field lain
di-echo apa adanya (dengan `esc()`).

## 6. UI modal pilih kolom

Di `admin/warga.php` dan `admin/rekap_warga.php`, di sebelah tombol "Export
Data" yang ada:

- Tombol kecil ikon gear (`<i class="fas fa-cog"></i>`) yang membuka Bootstrap
  modal `#modal-export-custom`.
- Isi modal: checkbox per kolom (default semua tercentang) + link toggle
  "Pilih Semua / Kosongkan Semua"; lalu checkbox terpisah "Sertakan warga yang
  sudah meninggal" (default tidak tercentang); tombol "Export" di footer modal.
- Tombol "Export" di modal: JS mengumpulkan kolom yang tercentang + status
  checkbox meninggal, membangun URL
  `<?= base_url('admin/warga/export') ?>?type=...&value=...&columns=...&include_deceased=...`
  (melanjutkan `type`/`value` yang sedang aktif dari filter-trigger halaman,
  pola yang sama seperti JS yang sudah ada untuk `#btn-export-warga`), lalu
  `window.location.href = url`.
- Tombol "Export Data" biasa (di luar modal) tetap tidak berubah perilakunya:
  klik langsung download, tanpa `columns`/`include_deceased` di URL → berarti
  semua kolom + hanya warga hidup (default `Admin\Warga::export()` dan
  `Admin\Rekap::export()` saat param kosong).

## 7. Testing

- Test manual (dev server): buka Kelola Warga → Export Data (cek semua 22
  kolom + hanya warga hidup) → buka modal, uncek beberapa kolom + centang
  "Sertakan yang meninggal" → Export, cek file `.xls` hasilnya sesuai pilihan.
- Ulangi di halaman Rekap RT (sebagai superadmin, pilih salah satu RT).
- Tidak ada test otomatis baru untuk fitur ini (project tidak punya test untuk
  fitur export yang sudah ada); cukup verifikasi manual sesuai
  `superpowers:verification-before-completion`.
