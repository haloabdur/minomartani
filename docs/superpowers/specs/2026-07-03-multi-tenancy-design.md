# Spec: Multi-tenancy RT/RW — RT29 Minomartani

Tanggal: 2026-07-03
Status: Disetujui (brainstorming selesai)

## Tujuan

Mengubah aplikasi dari single-tenant (hanya RT 29) menjadi multi-tenant: setiap RT
dan RW mendapat akun sendiri, dan semua data terisolasi per tenant. Struktur
mengikuti pemerintahan nyata: **RW membawahi beberapa RT** (hierarki). Data RT 29
yang sudah ada di database produksi menjadi tenant pertama tanpa perubahan perilaku.

## Keputusan desain (hasil brainstorming)

| Keputusan | Pilihan |
|---|---|
| Model tenant | Hierarki: RW membawahi RT |
| Isolasi data | Satu database, kolom `id_rt` di tiap tabel data |
| Akses publik | Prefix path per tenant (`/rt29/...`) |
| Provisioning tenant | Hanya superadmin |
| Hak akses akun RW | Read-only (rekap agregat + lihat detail RT di bawahnya) |

## 1. Tabel master tenant

Dua tabel baru (migration idempoten, pola `createTable($table, true, [...])`):

- **`rw`**: `id_rw` (PK, AI), `nama` (mis. "RW 08 Minomartani"), `slug` (unik),
  `is_aktif` (default 1), timestamps.
- **`rt`**: `id_rt` (PK, AI), `id_rw` (FK ke `rw`), `nama` (mis. "RT 29"),
  `slug` (unik, mis. `rt29`), `is_aktif` (default 1), timestamps.

Migration data men-seed RW pertama dan RT 29 (`id_rt = 1`, slug `rt29`) secara
idempoten (cek slug sebelum insert, pola `MigrateLegacyUsersToShield`).

## 2. Kolom tenant di tabel data

Tabel milik tenant mendapat kolom `id_rt INT NOT NULL` + index:

`warga`, `alamat`, `berita`, `surat`, `inventaris`, `dawis`, `ketua`.

- Migration ALTER TABLE idempoten: cek keberadaan kolom via `$db->fieldExists()`
  sebelum ADD COLUMN; match charset/collation tabel existing (banyak yang latin1).
- Backfill: semua baris lama di-set `id_rt = 1` (RT 29).
- Tabel lookup tetap **global/shared** (tanpa `id_rt`): `pekerjaan`,
  `status_keluarga`, `status_penduduk`, dan `layanan` (daftar jenis layanan —
  permintaan layanan publik sendiri tersimpan di `surat`, yang sudah
  tenant-scoped).

## 3. Akun ↔ tenant

- Tabel Shield `users` ditambah kolom `id_rt` (nullable) dan `id_rw` (nullable)
  via migration idempoten.
- Group Shield baru **`rw`** ditambahkan di `app/Config/AuthGroups.php`.
- Jenis akun:
  - **Superadmin** — `id_rt` dan `id_rw` NULL; akses semua tenant; memilih "RT
    aktif" lewat dropdown di header admin, disimpan di session.
  - **Admin RT** (group `admin`) — `id_rt` terisi; seluruh panel admin terkunci
    ke RT tersebut.
  - **Akun RW** (group `rw`) — `id_rw` terisi; hanya route `admin/rekap/*`.
- Akun existing hasil migrasi legacy di-backfill: group `admin` → `id_rt = 1`.

## 4. Penegakan isolasi (inti sistem)

Defense-in-depth dua lapis:

1. **Helper `tenant_helper.php`** (autoload di BaseController seperti `kbw`):
   - `current_rt_id(): ?int` — dari user login; untuk superadmin dari session
     (RT aktif yang dipilih).
   - `current_rt(): ?object`, `current_rw_id(): ?int` sesuai kebutuhan.
2. **Model**: setiap method query di `WargaModel`, `AlamatModel`, `BeritaModel`,
   `SuratModel`, `InventarisModel` (yang memakai query builder
   manual) ditambah `->where('<tabel>.id_rt', current_rt_id())`. Insert/update
   selalu men-set `id_rt` dari konteks, bukan dari input form.
3. **Filter route baru `tenant`** (`App\Filters\TenantFilter`, alias `tenant` di
   `app/Config/Filters.php`): dipasang di group `admin`; menolak request jika
   user tidak punya konteks tenant (kecuali superadmin yang belum memilih RT →
   redirect ke pemilih RT; akun `rw` hanya lolos untuk `admin/rekap/*`).

Route admin CRUD dibatasi group: `admin` + superadmin. Akun `rw` tidak diberi
akses route CRUD sama sekali (route-level, bukan hanya di view).

## 5. Halaman rekap RW (read-only)

Controller baru `Admin\Rekap` (filter `group:rw,superadmin`):

- Daftar RT di bawah RW user + statistik agregat per RT (jumlah warga, KK,
  laki/perempuan, surat masuk).
- Drill-down read-only: lihat daftar warga dan detail warga per RT — tanpa
  tombol tambah/edit/hapus, dan tanpa route mutasi.

## 6. Halaman publik per tenant (prefix path)

- Route publik menjadi `/{slug}/...`: `/rt29`, `/rt29/berita/(:any)`,
  `/rt29/layanan`, `/rt29/detail/(:any)`.
- Segmen pertama dicocokkan ke `rt.slug`; tidak dikenal → 404. Route spesifik
  (`admin`, `login`, `register`, dll.) didaftarkan **sebelum** route slug agar
  tidak bentrok (CI4 mencocokkan berurutan).
- **Backward compat**: URL lama tetap hidup — `/`, `/layanan`, `/berita/x`
  redirect ke `/rt29/...`; `/detail/(:any)` (QR alamat yang sudah dicetak)
  me-resolve alamat → RT pemiliknya lalu redirect. QR baru digenerate dengan
  URL berprefix slug.

## 7. Manajemen tenant

- Controller baru `Admin\Tenants` (filter `group:superadmin`): CRUD RW dan RT.
- Form `Admin\Users` diperluas: pilih group + assignment RT/RW saat membuat/
  mengubah akun. Route `admin/users/*` tetap `group:admin` per wiring existing,
  dengan penambahan: hanya superadmin yang bisa meng-assign tenant lain;
  admin RT hanya bisa membuat user untuk RT-nya sendiri.

## 8. Testing

Mengikuti pola test existing (MySQL `rt29mino_test`, `$namespace = null`):

- **Migration test**: tabel `rw`/`rt` terbentuk; kolom `id_rt` ada di 7 tabel
  data; backfill `id_rt = 1`; kolom tenant di `users`; migration aman dijalankan
  dua kali (idempoten).
- **Route/filter wiring test**: `admin/*` memakai filter `tenant`;
  `admin/rekap/*` group `rw`; `admin/tenants/*` group `superadmin`; ingat
  `setHTTPVerb()` dan pola route literal.
- **Isolation test**: seed 2 RT dengan data masing-masing → query model dengan
  konteks RT A tidak pernah mengembalikan baris RT B; insert selalu men-set
  `id_rt` konteks; rekap RW hanya menghitung RT di bawah RW-nya.
- **Public routing test**: `/rt29/...` menampilkan konten tenant benar; slug
  tak dikenal → 404; URL lama redirect ke `/rt29/...`.

## Batasan & non-goals

- Tidak ada pendaftaran tenant mandiri (hanya superadmin).
- Tidak ada subdomain per tenant.
- Tabel lookup (`pekerjaan`, `status_*`) tetap shared; tidak per-tenant.
- Tabel legacy `user` tidak disentuh (tetap arsip).
- Tidak ada perubahan mesin rendering view (tetap `loadViews`/`load_view`).

## Catatan keamanan operasional

Database dev = data produksi RT 29. Semua migration wajib idempoten dan
non-destruktif; backfill default `id_rt = 1` menjamin perilaku RT 29 tidak
berubah setelah migrate.
