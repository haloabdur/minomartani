# Struktur Database `rt29mino`

Sumber: dump `rt29mino_rt29mino (1).sql` langsung dari produksi (MariaDB 10.11.18, diambil 2026-07-14), disilangkan dengan migration CI4 di `app/Database/Migrations/` dan `allowedFields` tiap Model di `app/Models/`. Jadi ini bukan lagi rekonstruksi dari kode — ini skema live yang sebenarnya.

Terakhir diperbarui: 2026-07-15.

## ✅ Temuan yang sudah diperbaiki (2026-07-15)

1. **~~`Admin\Surat::store()`/`update()` error karena kolom `no_surat`/`id_alamat` tidak ada~~ — Diperbaiki dengan menghapus fitur, bukan menambah kolom.** Fitur "Tambah/Ubah Surat" manual di admin ternyata tidak pernah selesai dibangun: view `admin/tambah_surat.php`/`admin/ubah_surat.php` tidak ada filenya, dan data yang dikirim tidak menyertakan `id_warga`/`maksut`/`perlu` yang wajib diisi. Alur yang sebenarnya jalan (submit lewat form Layanan publik → RT setujui via `admin/surat/setuju`) sudah lengkap dan tidak menyentuh kolom ini. Route `surat/add`, `surat/store`, `surat/edit`, `surat/update` serta method controller terkait sudah dihapus; `SuratModel::$allowedFields` dibersihkan dari `no_surat`/`id_alamat`.
2. **~~PIN check di form Layanan publik selalu gagal karena `alamat.kode_rumah` tidak ada~~ — Diperbaiki dengan menambah kolom sungguhan.** Migration `2026-07-15-071000_AddKodeRumahToAlamat.php` menambahkan kolom `kode_rumah` (nullable, tanpa backfill — fail-closed by design) ke tabel `alamat`. RT admin sekarang bisa mengatur PIN per alamat lewat Admin > Alamat (form tambah & ubah), dan daftar alamat menampilkan badge "Sudah"/"Belum" untuk status PIN. `Layanan::store()` diberi pesan error terpisah untuk kasus "PIN belum diatur" vs "PIN salah". **Catatan: modul Layanan publik saat ini sengaja disembunyikan dari menu oleh pemilik project — perbaikan ini menyiapkan logikanya supaya benar begitu modul diaktifkan kembali, tapi setiap alamat perlu diisi PIN-nya dulu lewat Admin > Alamat sebelum warga di alamat itu bisa mengajukan surat.**
3. **Tabel `layanan` tidak ada di database produksi**, padahal `app/Models/LayananModel.php` mendefinisikan model untuknya. `Controller\Layanan` tidak memakai model ini sama sekali (dead code, dibiarkan apa adanya — tidak berdampak selama tidak ada yang mulai memakainya).
4. **`AlamatModel::$allowedFields` masih mencantumkan `nomor`** yang tidak ada di tabel `alamat` produksi. Dibiarkan apa adanya karena `Admin\Alamat::store()` hanya memakai `nomor` sebagai input form biasa (digabung ke kolom `alamat`), tidak pernah disimpan sebagai kolom terpisah — jadi tidak ada risiko error, hanya kurang rapi.

---

## Ringkasan arsitektur

- **Multi-tenant**: hierarki `rw` → `rt`. Tabel data milik tenant (`warga`, `alamat`, `berita`, `surat`, `inventaris`, `dawis`, `ketua`) dan `kesehatan_kegiatan`/`kesehatan_catatan` punya kolom `id_rt` (dua tabel kesehatan juga bisa `id_rw` untuk kegiatan level RW). Tabel lookup (`pekerjaan`, `status_keluarga`, `status_penduduk`) global, tanpa `id_rt`.
- **Auth**: CodeIgniter Shield (`users`, `auth_identities`, dst). Tabel `user` (legacy CI3) diarsipkan, tidak dipakai aplikasi.
- **Charset campuran**: tabel era CI3 pakai `latin1`/`latin1_swedish_ci` (`alamat`, `berita`, `dawis`, `ketua`, `pekerjaan`, `status_keluarga`, `status_penduduk`, `surat`, `user`, `warga`); tabel baru pakai `utf8mb4` (`auth_*`, `kesehatan_*`, `rt`, `rw`, `settings`, `users`); `inventaris` pakai `utf8mb3`.
- **FK constraint nyata di DB cuma sedikit**: `dawis.id_warga → warga.id_warga` dan rantai `auth_*.user_id → users.id` (semua `ON DELETE CASCADE`). Semua relasi tenant/lookup lainnya (warga→alamat, warga→pekerjaan, surat→warga, dst) ditegakkan di query aplikasi, bukan constraint DB.
- **Server**: MariaDB 10.11.18 (dump generation tool: phpMyAdmin 5.2.3, PHP 8.2.29).

---

## Tabel tenant/data

### `warga` — Data penduduk
PK: `id_warga` (AI). Unique: `nik`. Index: `id_rt`. Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_warga` | int(11) | PK, AUTO_INCREMENT |
| `id_alamat` | int(11) | NULL |
| `alamat_lengkap` | varchar(255) | NULL |
| `no_kk` | varchar(50) | NOT NULL |
| `nama_warga` | varchar(255) | NOT NULL |
| `nik` | varchar(50) | NOT NULL, UNIQUE |
| `jenis_kelamin` | char(5) | NULL — `L`/`P` |
| `tempat_lahir` | varchar(50) | NOT NULL |
| `tanggal_lahir` | date | NOT NULL |
| `gol_darah` | char(10) | NULL |
| `agama` | char(10) | NULL |
| `pendidikan` | char(10) | NULL |
| `id_pekerjaan` | int(11) | NOT NULL |
| `status_kawin` | tinyint(4) | NULL — 0=tidak,1=kawin,2=cerai hidup,3=cerai mati |
| `tanggal_kawin` | date | NULL |
| `id_status_keluarga` | tinyint(4) | NULL |
| `ayah` | varchar(255) | NULL — nama literal atau `id_warga` numerik (data legacy) |
| `ibu` | varchar(255) | NULL — idem |
| `no_hp` | varchar(20) | NULL |
| `email` | varchar(255) | NULL |
| `sumber_air` | varchar(20) | NULL |
| `id_status_penduduk` | tinyint(4) | NOT NULL DEFAULT 1 |
| `status_warga` | tinyint(4) | NOT NULL DEFAULT 1 |
| `is_hidup` | tinyint(4) | NOT NULL DEFAULT 1 |
| `id_user` | int(11) | NULL |
| `created_at` | timestamp | NULL DEFAULT current_timestamp() |
| `timestamp` | timestamp | NULL, ON UPDATE current_timestamp() |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

### `alamat` — Alamat rumah (dengan QR code)
PK: `id_alamat` (AI). Index: `id_rt`. Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_alamat` | int(11) | PK, AUTO_INCREMENT |
| `alamat` | varchar(50) | NULL |
| `qrcode` | varchar(100) | NULL |
| `kode_rumah` | varchar(20) | NULL — PIN Layanan per alamat, ditambahkan via `2026-07-15-071000_AddKodeRumahToAlamat.php`. Nullable tanpa backfill (fail-closed), diisi manual per alamat lewat Admin > Alamat |
| `timestamp` | timestamp | NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

Catatan: **tidak ada** `nomor` di sini meski dicantumkan di `AlamatModel::$allowedFields` — lihat temuan #4 di atas (harmless, dead field).

### `berita` — Berita/pengumuman
PK: `id_berita` (AI). Index: `id_rt`. Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_berita` | int(11) | PK, AUTO_INCREMENT |
| `judul` | varchar(255) | NOT NULL |
| `slug` | varchar(255) | NULL |
| `deskripsi` | text | NOT NULL |
| `kategori` | varchar(50) | NULL |
| `foto` | varchar(50) | NULL |
| `lampiran` | varchar(255) | NULL |
| `sumber` | varchar(255) | NULL |
| `is_status` | tinyint(4) | NOT NULL |
| `created_by` | tinyint(4) | NULL |
| `timestamp` | timestamp | NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

### `surat` — Permintaan surat/pengantar
PK: `id_surat` (AI). Index: `id_rt`. Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_surat` | int(11) | PK, AUTO_INCREMENT |
| `id_warga` | int(11) | NOT NULL |
| `maksut` | varchar(100) | NOT NULL |
| `perlu` | varchar(100) | NOT NULL |
| `lampiran` | varchar(255) | NULL |
| `status_surat` | tinyint(4) | NOT NULL DEFAULT 0 |
| `created_at` | timestamp | NOT NULL DEFAULT current_timestamp() |
| `timestamp` | timestamp | NOT NULL DEFAULT `'0000-00-00 00:00:00'` ON UPDATE current_timestamp() — literal zero-date legacy, lihat catatan di migration |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

Catatan: kolom `no_surat`/`id_alamat` yang sebelumnya dipakai fitur "Tambah Surat" manual di admin sudah tidak relevan — fiturnya dihapus (bukan kolomnya yang ditambah), lihat temuan #1 di atas.

### `inventaris` — Inventaris barang RT
PK: `id` (AI, unsigned). Index: `id_rt`. Engine/charset: InnoDB, `utf8mb3`/`utf8mb3_general_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id` | int(11) unsigned | PK, AUTO_INCREMENT |
| `nama_barang` | varchar(255) | NOT NULL |
| `stok` | int(11) | NOT NULL DEFAULT 0 |
| `foto` | varchar(255) | NULL |
| `created_at` | datetime | NULL |
| `updated_at` | datetime | NULL |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

### `dawis` — Kelompok dasa wisma
PK: `id_dawis` (AI). Index: `id_warga`, `id_rt`. FK: `id_warga → warga.id_warga` (`dawis_ibfk_1`). Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_dawis` | int(11) | PK, AUTO_INCREMENT |
| `id_warga` | int(11) | NOT NULL, FK |
| `nama_dawis` | char(10) | NOT NULL |
| `timestamp` | datetime | NOT NULL |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

Tidak ada Model (`DawisModel`) maupun controller yang mengakses tabel ini saat ini — tampak tidak terpakai di aplikasi (fitur dasa wisma belum ada UI-nya).

### `ketua` — Riwayat ketua RT
PK: `id_ketua` (AI). Index: `id_rt`. Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_ketua` | int(11) | PK, AUTO_INCREMENT |
| `nama_ketua` | varchar(50) | NOT NULL |
| `mulai` | varchar(20) | NOT NULL |
| `selesai` | varchar(20) | NOT NULL |
| `foto_ketua` | varchar(50) | NULL |
| `timestamp` | timestamp | NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() |
| `id_rt` | int(11) | NOT NULL DEFAULT 1 |

Diakses lewat query builder langsung di `Home.php`, tidak ada Model khusus.

### `kesehatan_kegiatan` — Sesi kegiatan kesehatan (mis. Posyandu Lansia)
PK: `id_kegiatan` (AI). Index: `id_rt`, `id_rw`. Engine/charset: InnoDB, `utf8mb4`/`utf8mb4_general_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_kegiatan` | int(11) | PK, AUTO_INCREMENT |
| `nama_kegiatan` | varchar(255) | NOT NULL |
| `tanggal_kegiatan` | date | NOT NULL |
| `kategori` | varchar(30) | NOT NULL DEFAULT `'lansia'` |
| `id_rt` | int(11) | NULL — terisi jika kegiatan level RT |
| `id_rw` | int(11) | NULL — terisi jika kegiatan level RW |
| `catatan` | text | NULL |
| `id_user` | int(11) | NULL |
| `created_at` | timestamp | NULL DEFAULT current_timestamp() |
| `timestamp` | timestamp | NULL ON UPDATE current_timestamp() |

### `kesehatan_catatan` — Hasil pemeriksaan per warga per kegiatan
PK: `id_catatan` (AI). Unique: (`id_kegiatan`,`id_warga`). Index: `id_warga`, `id_rt`. Engine/charset: InnoDB, `utf8mb4`/`utf8mb4_general_ci`.

| Kolom | Tipe | Nullable / Default |
|---|---|---|
| `id_catatan` | int(11) | PK, AUTO_INCREMENT |
| `id_kegiatan` | int(11) | NOT NULL |
| `id_warga` | int(11) | NOT NULL |
| `id_rt` | int(11) | NOT NULL |
| `tensi_sistol` | smallint(5) | NULL |
| `tensi_diastol` | smallint(5) | NULL |
| `berat_badan` | decimal(5,2) | NULL |
| `tinggi_badan` | decimal(5,2) | NULL |
| `lingkar_perut` | decimal(5,2) | NULL |
| `gula_darah` | decimal(6,2) | NULL |
| `gula_darah_ket` | varchar(20) | NULL — `puasa`\|`sewaktu` |
| `kolesterol` | decimal(6,2) | NULL |
| `asam_urat` | decimal(5,2) | NULL |
| `catatan` | text | NULL |
| `id_user` | int(11) | NULL |
| `created_at` | timestamp | NULL DEFAULT current_timestamp() |
| `timestamp` | timestamp | NULL ON UPDATE current_timestamp() |

---

## Tabel lookup (global, shared antar tenant)

### `pekerjaan`
PK: `id_pekerjaan` (AI). Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe |
|---|---|
| `id_pekerjaan` | int(11), PK AI |
| `nama_pekerjaan` | varchar(50) NOT NULL |
| `timestamp` | timestamp NOT NULL DEFAULT current_timestamp() |

### `status_keluarga`
PK: `id_status_keluarga` (AI). Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe |
|---|---|
| `id_status_keluarga` | int(11), PK AI |
| `status` | varchar(20) NULL |
| `timestamp` | timestamp NULL ON UPDATE current_timestamp() |

### `status_penduduk`
PK: `id_status_penduduk` (AI). Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe |
|---|---|
| `id_status_penduduk` | int(11), PK AI |
| `status` | varchar(20) NOT NULL |
| `label` | varchar(10) NOT NULL |
| `timestamp` | timestamp NOT NULL DEFAULT current_timestamp() |

### `layanan` — **tidak ada di database produksi**
`app/Models/LayananModel.php` mendefinisikan `table = 'layanan'`, `primaryKey = 'id_layanan'`, `allowedFields = ['nama_layanan']` — tapi tabel ini tidak ada di dump produksi, dan tidak ada migration untuknya. Model ini juga tidak dipakai oleh `Controller\Layanan` (lihat temuan #3). Kemungkinan sisa rencana fitur "jenis layanan" yang belum jadi, atau tabel yang sudah di-drop dari produksi.

---

## Tabel multi-tenancy

### `rw`
PK: `id_rw` (AI). Unique: `slug`, `subdomain`. Engine/charset: InnoDB, `utf8mb4`/`utf8mb4_general_ci`.

| Kolom | Tipe |
|---|---|
| `id_rw` | int(11), PK AI |
| `nama` | varchar(100) NOT NULL |
| `slug` | varchar(50) NOT NULL, UNIQUE |
| `subdomain` | varchar(63) NULL, UNIQUE |
| `is_aktif` | tinyint(4) NOT NULL DEFAULT 1 |
| `created_at` | timestamp NULL DEFAULT current_timestamp() |

### `rt`
PK: `id_rt` (AI). Unique: `slug`, `subdomain`. Index: `id_rw`. Engine/charset: InnoDB, `utf8mb4`/`utf8mb4_general_ci`.

| Kolom | Tipe |
|---|---|
| `id_rt` | int(11), PK AI |
| `id_rw` | int(11) NOT NULL |
| `nama` | varchar(100) NOT NULL |
| `slug` | varchar(50) NOT NULL, UNIQUE |
| `subdomain` | varchar(63) NULL, UNIQUE |
| `is_aktif` | tinyint(4) NOT NULL DEFAULT 1 |
| `created_at` | timestamp NULL DEFAULT current_timestamp() |

---

## Tabel auth (CodeIgniter Shield)

### `users`
PK: `id` (AI, unsigned). Unique: `username`. Engine/charset: InnoDB, `utf8mb4`/`utf8mb4_general_ci`.

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `username` | varchar(30) NULL, UNIQUE |
| `status` | varchar(255) NULL |
| `status_message` | varchar(255) NULL |
| `active` | tinyint(1) NOT NULL DEFAULT 0 |
| `last_active` | datetime NULL |
| `created_at` / `updated_at` / `deleted_at` | datetime NULL |
| `id_rt` | int(11) NULL — NULL untuk superadmin, terisi untuk RT admin |
| `id_rw` | int(11) NULL — terisi untuk akun grup `rw` |

### `auth_identities` — password/token/social login per user
PK: `id`. Unique: (`type`,`secret`). Index: `user_id`. FK: `user_id → users.id` (CASCADE).

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `user_id` | int(11) unsigned NOT NULL |
| `type` | varchar(255) NOT NULL — mis. `email_password` |
| `name` | varchar(255) NULL |
| `secret` | varchar(255) NOT NULL — mis. email |
| `secret2` | varchar(255) NULL — mis. hash bcrypt |
| `expires` | datetime NULL |
| `extra` | text NULL |
| `force_reset` | tinyint(1) NOT NULL DEFAULT 0 |
| `last_used_at` | datetime NULL |
| `created_at` / `updated_at` | datetime NULL |

### `auth_logins` & `auth_token_logins` — riwayat percobaan login (form vs bearer token)
PK: `id`. Index: (`id_type`,`identifier`), `user_id`. Kolom sama untuk kedua tabel:

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `ip_address` | varchar(255) NOT NULL |
| `user_agent` | varchar(255) NULL |
| `id_type` | varchar(255) NOT NULL |
| `identifier` | varchar(255) NOT NULL |
| `user_id` | int(11) unsigned NULL |
| `date` | datetime NOT NULL |
| `success` | tinyint(1) NOT NULL |

### `auth_remember_tokens`
PK: `id`. Unique: `selector`. FK: `user_id → users.id` (CASCADE).

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `selector` | varchar(255) NOT NULL, UNIQUE |
| `hashedValidator` | varchar(255) NOT NULL |
| `user_id` | int(11) unsigned NOT NULL |
| `expires` | datetime NOT NULL |
| `created_at` / `updated_at` | datetime NOT NULL |

### `auth_groups_users` — keanggotaan grup (role)
PK: `id`. Index: `user_id`. FK: `user_id → users.id` (CASCADE).

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `user_id` | int(11) unsigned NOT NULL |
| `group` | varchar(255) NOT NULL — `superadmin`\|`admin`\|`developer`\|`user`\|`rw`\|`beta` (`app/Config/AuthGroups.php`) |
| `created_at` | datetime NOT NULL |

### `auth_permissions_users`
PK: `id`. FK: `user_id → users.id` (CASCADE).

| Kolom | Tipe |
|---|---|
| `id` | int(11) unsigned, PK AI |
| `user_id` | int(11) unsigned NOT NULL |
| `permission` | varchar(255) NOT NULL |
| `created_at` | datetime NOT NULL |

---

## Tabel infrastruktur/framework (bukan data aplikasi)

### `migrations`
Tabel internal CI4 untuk mencatat migration yang sudah dijalankan (`version`, `class`, `group`, `namespace`, `time`, `batch`). Engine MyISAM.

### `settings`
Dibuat oleh paket `codeigniter4/settings` (dependency, kemungkinan besar transitif lewat Shield) — key-value store generik (`class`, `key`, `value`, `type`, `context`). Tidak ada `Config\Settings` override maupun pemanggilan `Settings::` di kode aplikasi (`app/`) — tabel ini ada tapi tidak dipakai aktif saat ini. Engine MyISAM.

---

## Tabel legacy (arsip, tidak dipakai aplikasi)

### `user`
Auth CI3 lama, digantikan Shield (lihat migration `MigrateLegacyUsersToShield.php` yang memindahkan akunnya ke `users`/`auth_identities`/`auth_groups_users`). Masih ada untuk arsip/audit. PK: `id_user` (AI). Engine/charset: InnoDB, `latin1`/`latin1_swedish_ci`.

| Kolom | Tipe |
|---|---|
| `id_user` | int(11), PK AI |
| `username` | varchar(100) NOT NULL |
| `email` | varchar(100) NULL |
| `password` | varchar(100) NOT NULL |
| `role` | tinyint(4) NOT NULL DEFAULT 1 |
| `status_user` | tinyint(4) NOT NULL DEFAULT 1 |
| `timestamp` | timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() |

---

## Relasi antar tabel

**FK constraint sungguhan di database** (cuma ini):
- `dawis.id_warga → warga.id_warga` (`dawis_ibfk_1`)
- `auth_groups_users.user_id → users.id` (CASCADE)
- `auth_identities.user_id → users.id` (CASCADE)
- `auth_permissions_users.user_id → users.id` (CASCADE)
- `auth_remember_tokens.user_id → users.id` (CASCADE)

**Relasi konseptual** (ditegakkan di kode aplikasi lewat JOIN/WHERE, bukan constraint DB):
- `rt.id_rw → rw.id_rw`
- `users.id_rt → rt.id_rt` (nullable), `users.id_rw → rw.id_rw` (nullable)
- `warga.id_alamat → alamat.id_alamat`
- `warga.id_pekerjaan → pekerjaan.id_pekerjaan`
- `warga.id_status_keluarga → status_keluarga.id_status_keluarga`
- `warga.id_status_penduduk → status_penduduk.id_status_penduduk`
- `surat.id_warga → warga.id_warga`
- `kesehatan_kegiatan.id_rt → rt.id_rt` / `kesehatan_kegiatan.id_rw → rw.id_rw`
- `kesehatan_catatan.id_kegiatan → ke