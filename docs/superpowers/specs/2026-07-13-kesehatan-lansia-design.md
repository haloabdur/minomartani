# Pencatatan Kesehatan Lansia

## Context

RT mengadakan kegiatan Posyandu Lansia setiap bulan, di mana tensi, berat badan, tinggi badan, lingkar perut, gula darah, kolesterol, dan asam urat warga lansia diukur. Data ini belum tersimpan terstruktur di database — hasilnya dicatat manual/kertas dan tidak bisa dilihat tren historisnya.

Fitur ini menambahkan modul admin baru untuk mencatat hasil pemeriksaan per kegiatan bulanan, dengan dua mode akses:
- **RT** (`admin` group + `id_rt`): membuat kegiatan dan mencatat data hanya untuk warga RT-nya sendiri.
- **RW** (`rw` group + `id_rw`): membuat kegiatan dan mencatat data lintas RT dalam RW-nya. Ini memperluas peran `rw`, yang saat ini bersifat read-only (`admin/rekap` saja) — perluasan ini disengaja per keputusan produk, bukan bug.

Confirmed intent (dari sesi `interview-me`): lihat ringkasan restate yang dikonfirmasi user di percakapan — outcome, field kesehatan, filter usia otomatis+manual, entitas kegiatan terpisah, grafik tren per-warga.

## Assumptions (surface before build — koreksi jika salah)

1. **Cakupan riwayat RW** — layar "riwayat general" RW hanya menampilkan kegiatan yang dibuat RW itu sendiri (`id_rw` miliknya), bukan gabungan dengan kegiatan yang dibuat masing-masing RT admin secara independen. Kalau RW butuh visibilitas ke kegiatan RT juga, itu perluasan terpisah.
2. **Threshold usia lansia = 60 tahun** — reuse angka yang sudah dipakai di `WargaModel::export()` untuk filter `age-group=lansia` (`TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= 60`), bukan angka baru.
3. **Chart.js via CDN** — sudah dipakai di `admin/logs.php` (`chart.js@4.4.0` dari jsdelivr), dipakai lagi di sini untuk konsistensi, bukan menambah library charting baru.
4. **Tidak ada hapus kegiatan** — kegiatan bisa diedit (nama/tanggal/catatan) tapi tidak dihapus di MVP ini (data pemeriksaan berharga untuk histori). Catatan kesehatan per-warga di dalam kegiatan bisa dihapus/dikoreksi (salah input saat pemeriksaan itu realistis).
5. **`id_rt`/`id_rw` kegiatan tidak pernah datang dari input user** — selalu di-set server-side dari `current_rt_id()`/`current_rw_id()`, sama seperti pola tenant lain di app ini. Form tidak punya dropdown RT/RW.
6. **IMT (BMI) dihitung saat tampil, tidak disimpan** — dari `berat_badan`/`tinggi_badan` yang sudah ada, bukan kolom baru.

## Decisions

- **Entitas kegiatan terpisah dari catatan kesehatan** (dua tabel, bukan satu): `kesehatan_kegiatan` (sesi bulanan) dan `kesehatan_catatan` (satu baris = satu warga dalam satu kegiatan). Ini yang memungkinkan RW mencatat lintas-RT dalam satu sesi dan menyajikan riwayat per-kegiatan.
- **Nama tabel/kelas generik (`kesehatan_*`), bukan `lansia_*`** — kolom `kategori` (default `'lansia'`) menyimpan jenis kegiatan tanpa membangun katalog jenis kegiatan sekarang. MVP hanya pernah menulis `'lansia'`, tapi struktur data tidak terkunci ke situ.
- **Filter peserta: otomatis by usia + pencarian manual** — default menampilkan warga ≥60 tahun dalam scope (RT atau seluruh RT di RW), dengan kotak pencarian nama/NIK yang query semua warga (tanpa filter usia) dalam scope yang sama untuk menambah peserta di luar filter.
- **Tenant isolation ganda, konsisten dengan pola app**: `kesehatan_kegiatan` punya `id_rt` XOR `id_rw` (persis satu terisi, divalidasi di model/controller — bukan DB constraint, mengikuti gaya validasi aplikatif yang sudah ada). `kesehatan_catatan` menyimpan `id_rt` warga saat dicatat (denormalized dari `warga.id_rt`) supaya query isolasi konsisten dengan tabel tenant lain, walau kegiatan induknya level-RW.
- **Upsert manual, bukan `ON DUPLICATE KEY`** — cek-lalu-tulis (`find` → `update`/`insert`) mengikuti gaya prosedural model lain di app ini; volume data per kegiatan kecil jadi tidak perlu SQL upsert.
- **Extract `WargaModel::LANSIA_MIN_AGE = 60`** dari magic number yang sudah ada di `export()`'s age-group filter, dipakai ulang di method baru — menghindari duplikasi angka yang sama.

## Design

### Migrations

`app/Database/Migrations/2026-07-13-090000_CreateKesehatanKegiatanTable.php`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateKesehatanKegiatanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kegiatan'      => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_kegiatan'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tanggal_kegiatan' => ['type' => 'DATE', 'null' => false],
            'kategori'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'lansia'],
            'id_rt'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'id_rw'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'id_user'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'       => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'        => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_kegiatan');
        $this->forge->addKey('id_rt');
        $this->forge->addKey('id_rw');
        $this->forge->createTable('kesehatan_kegiatan', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('kesehatan_kegiatan', true);
    }
}
```

`app/Database/Migrations/2026-07-13-090100_CreateKesehatanCatatanTable.php`:

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateKesehatanCatatanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_catatan'     => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'id_kegiatan'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_warga'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'id_rt'          => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'tensi_sistol'   => ['type' => 'SMALLINT', 'constraint' => 5, 'null' => true],
            'tensi_diastol'  => ['type' => 'SMALLINT', 'constraint' => 5, 'null' => true],
            'berat_badan'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tinggi_badan'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'lingkar_perut'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'gula_darah'     => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'gula_darah_ket' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'puasa|sewaktu'],
            'kolesterol'     => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'asam_urat'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'catatan'        => ['type' => 'TEXT', 'null' => true],
            'id_user'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'timestamp'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('NULL ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id_catatan');
        $this->forge->addUniqueKey(['id_kegiatan', 'id_warga']);
        $this->forge->addKey('id_warga');
        $this->forge->addKey('id_rt');
        $this->forge->createTable('kesehatan_catatan', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('kesehatan_catatan', true);
    }
}
```

Semua kolom kesehatan `null => true` — tidak ada yang required, sesuai kebutuhan (petugas kadang tidak sempat ukur semua parameter).

### Models

`app/Models/KesehatanKegiatanModel.php`:
- `allowedFields`: `nama_kegiatan, tanggal_kegiatan, kategori, id_rt, id_rw, catatan, id_user`
- `forCurrentScope(): array` — riwayat general: `WHERE id_rw = current_rw_id()` jika RW-scoped (`current_rw_id() !== null`), else `WHERE id_rt = current_rt_id()`. Sertakan `COUNT` peserta tercatat via subquery/join ke `kesehatan_catatan` untuk ditampilkan di list.
- `detailForCurrentScope(int $id): ?object` — fetch + authorize di query yang sama (`WHERE id_kegiatan = ? AND (id_rw = current_rw_id() OR id_rt = current_rt_id())` sesuai scope), return `null` kalau tidak match → controller lempar 404. Pola ini sama seperti `Rekap::warga()`'s manual RT/RW ownership check.

`app/Models/KesehatanCatatanModel.php`:
- `allowedFields`: `id_kegiatan, id_warga, id_rt, tensi_sistol, tensi_diastol, berat_badan, tinggi_badan, lingkar_perut, gula_darah, gula_darah_ket, kolesterol, asam_urat, catatan, id_user`
- `byKegiatan(int $idKegiatan): array` — semua catatan dalam satu kegiatan, joined ke `warga` untuk nama.
- `forWarga(int $idWarga): array` — histori satu warga lintas kegiatan, joined ke `kesehatan_kegiatan` untuk `tanggal_kegiatan`/`nama_kegiatan`, `ORDER BY tanggal_kegiatan ASC` (urutan chart).
- `upsert(int $idKegiatan, int $idWarga, int $idRt, array $data): void` — `where(['id_kegiatan' => $idKegiatan, 'id_warga' => $idWarga])->first()`; jika ada → `update()`, jika tidak → `insert()` dengan `id_kegiatan`/`id_warga`/`id_rt` ditambahkan ke `$data`.

`app/Models/WargaModel.php` (tambahan, tidak mengubah yang sudah ada selain extract constant):
- `public const LANSIA_MIN_AGE = 60;` — dipakai ulang di `export()`'s existing `'lansia'` branch (ganti literal `60` jadi `self::LANSIA_MIN_AGE`) dan di method baru berikut.
- `lansiaByRtIds(array $idRts): array` — `SELECT id_warga, nama_warga, tanggal_lahir, id_rt` dari `warga` `WHERE status_warga = 1 AND id_rt IN (...) AND TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= LANSIA_MIN_AGE`, joined ke `alamat` kalau perlu tampilkan alamat di picker.
- `searchByRtIds(array $idRts, string $q): array` — pencarian manual (tanpa filter usia) by `nama_warga LIKE` / `nik LIKE`, `WHERE status_warga = 1 AND id_rt IN (...)`, dipakai untuk kotak cari peserta di luar filter lansia.

### Access control

**Routes** (`app/Config/Routes.php`, di dalam grup `admin` yang sudah ada):

```php
// Kesehatan Lansia - RT admin (RT sendiri) dan RW (lintas RT dalam RW-nya)
$routes->group('kesehatan', ['filter' => 'group:admin,rw,superadmin'], function ($routes) {
    $routes->get('/', 'Admin\Kesehatan::index');
    $routes->get('add', 'Admin\Kesehatan::add');
    $routes->post('store', 'Admin\Kesehatan::store');
    $routes->get('kegiatan/(:num)', 'Admin\Kesehatan::kegiatan/$1');
    $routes->get('kegiatan/(:num)/edit', 'Admin\Kesehatan::editKegiatan/$1');
    $routes->post('kegiatan/(:num)/update', 'Admin\Kesehatan::updateKegiatan/$1');
    $routes->post('kegiatan/(:num)/simpan', 'Admin\Kesehatan::simpanCatatan/$1');
    $routes->get('kegiatan/(:num)/catatan/(:num)/hapus', 'Admin\Kesehatan::hapusCatatan/$1/$2');
    $routes->get('warga/(:num)', 'Admin\Kesehatan::warga/$1');
});
```

**`TenantFilter`** — `rw` group saat ini di-redirect paksa ke `admin/rekap` untuk path apa pun selain itu (`app/Filters/TenantFilter.php:77`). Perlu diperluas:

```php
// RW accounts: rekap (read-only) dan kesehatan (read-write) adalah surface mereka.
if (strpos($path, 'admin/rekap') !== 0 && strpos($path, 'admin/kesehatan') !== 0) {
    return redirect()->to('admin/rekap');
}
```

**`AuthGroups.php`** — tidak perlu perubahan pada `$matrix` (akses diatur lewat filter `group:` seperti `rekap`, bukan Shield permission matrix, konsisten dengan pola yang sudah ada). Update string deskripsi grup `rw` (baris 62) dari "Read-only recap access..." jadi mencerminkan bahwa sekarang mereka juga bisa mencatat kesehatan lansia lintas RT — housekeeping kecil, bukan perubahan behavior.

**Controller-level double-check** (defense in depth, sama seperti pola `Rekap`): setiap method yang menerima `id_kegiatan` dari URL wajib lewat `KesehatanKegiatanModel::detailForCurrentScope()` yang sudah menyertakan authorization query — kalau `null`, lempar `PageNotFoundException`. `id_rt`/`id_rw` kegiatan **tidak pernah** diambil dari POST body.

### Controller: `app/Controllers/Admin/Kesehatan.php`

- `index()` — `kegiatanModel->forCurrentScope()`, render riwayat general (list kegiatan + jumlah peserta tercatat).
- `add()` — form nama/tanggal/catatan kegiatan saja (tidak ada picker RT/RW).
- `store()` — validasi `nama_kegiatan`+`tanggal_kegiatan` wajib diisi (ini metadata kegiatan, bukan field kesehatan); set `id_rt = current_rt_id()` atau `id_rw = current_rw_id()` (persis satu, ditentukan dari `current_rw_id() !== null`), `id_user = auth()->id()`; redirect ke `kegiatan/(id)`.
- `kegiatan($id)` — authorize via `detailForCurrentScope`; hitung `$idRts` (satu RT untuk RT-scoped, atau `RtModel::byRw(current_rw_id())` untuk RW-scoped); load peserta lansia (`WargaModel::lansiaByRtIds`) + catatan yang sudah ada (`KesehatanCatatanModel::byKegiatan`) + hasil pencarian manual kalau ada `?q=`; render form input per-warga.
- `simpanCatatan($idKegiatan)` — authorize kegiatan; validasi `id_warga` termasuk dalam `$idRts` yang berwenang (defense in depth terhadap POST yang dimanipulasi); panggil `KesehatanCatatanModel::upsert(...)` dengan field kesehatan dari POST (semua opsional, tidak ada `required` di validasi).
- `editKegiatan($id)` / `updateKegiatan($id)` — ubah `nama_kegiatan`/`tanggal_kegiatan`/`catatan`, authorize sama.
- `hapusCatatan($idKegiatan, $idCatatan)` — hapus satu baris catatan warga (koreksi input), authorize kegiatan dulu.
- `warga($idWarga)` — authorize: warga harus ada di `current_rt_id()` (RT-scoped) atau salah satu RT di `current_rw_id()` (RW-scoped) — pola sama seperti pengecekan RT di `Rekap`; load `KesehatanCatatanModel::forWarga($idWarga)`, render tabel histori + data untuk Chart.js (satu line chart per metrik: tensi sistol/diastol, berat badan, lingkar perut, gula darah, kolesterol, asam urat — dikelompokkan per section, bukan satu chart gabungan multi-axis).

### Views

- `app/Views/admin/kesehatan.php` — riwayat general (list kegiatan, tombol "Tambah Kegiatan").
- `app/Views/admin/tambah_kegiatan.php` — form kegiatan baru.
- `app/Views/admin/ubah_kegiatan.php` — form edit kegiatan.
- `app/Views/admin/kegiatan_kesehatan.php` — detail kegiatan: daftar peserta (auto-filter lansia + kotak cari manual) dengan form input inline per-warga untuk field kesehatan.
- `app/Views/admin/kesehatan_warga.php` — detail per-warga: tabel histori + Chart.js line charts per metrik. Chart.js dimuat via `<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>` (sama seperti `admin/logs.php`).
- `app/Views/layouts/sidebar_menu.php` — tambah entry menu "Kesehatan Lansia" (icon `fa-heartbeat` atau serupa), terlihat untuk `admin`, `rw`, dan `superadmin` — mengikuti pola conditional yang sudah ada untuk entry `rekap`:
  ```php
  <?php if (auth()->user() && (auth()->user()->inGroup('admin') || auth()->user()->inGroup('rw') || auth()->user()->inGroup('superadmin'))): ?>
  ```

## Out of scope

- Sistem kegiatan kesehatan generik untuk jenis lain (posyandu balita, posbindu dewasa, dll) — kolom `kategori` menyiapkan tempatnya, tapi tidak ada UI/logic untuk kategori selain `'lansia'` di iterasi ini.
- Input mandiri oleh warga (self-service) — hanya admin/RT/RW yang mencatat.
- Alert/notifikasi otomatis (mis. peringatan tensi tinggi).
- Hapus kegiatan (hanya catatan per-warga di dalamnya yang bisa dihapus).
- Dashboard agregat lintas-kegiatan (mis. rata-rata tensi RT bulan ini) — riwayat general MVP hanya daftar kegiatan, bukan analitik.
- Export/print hasil pemeriksaan (PDF/Excel) — bisa jadi permintaan terpisah nanti, mengikuti pola export yang sudah ada di Warga.

## Testing Strategy

Manual testing, sesuai konvensi project ini — tidak ada PHPUnit ditulis kecuali diminta eksplisit.

## Success Criteria

- Admin RT bisa membuat kegiatan, melihat daftar lansia RT-nya otomatis, mencatat sebagian atau semua field kesehatan per warga tanpa ada yang wajib diisi.
- Admin RW bisa membuat kegiatan yang menampilkan lansia dari seluruh RT di RW-nya dalam satu sesi, dan mencatat data untuk warga dari RT mana pun dalam RW tersebut.
- Admin RT **tidak bisa** membuka/mencatat kegiatan RW atau RT lain (404 saat dicoba, baik lewat URL langsung maupun POST yang dimanipulasi).
- Riwayat general menampilkan kegiatan-kegiatan yang sudah berjalan sesuai scope user.
- Halaman detail warga menampilkan grafik tren tiap metrik kesehatan dari seluruh kegiatan yang pernah ia ikuti.
- Migration idempotent — bisa dijalankan berulang di DB yang sudah berisi data tanpa error.

## Open Questions

- Apakah label menu/copy UI perlu "Kesehatan Lansia" persis, atau ada istilah lain yang lebih familiar untuk pengurus RT (mis. "Posyandu Lansia", "Posbindu")?
- Perlu icon spesifik di sidebar (AdminLTE/FontAwesome) — punya preferensi, atau pakai `fa-heartbeat` saja?
