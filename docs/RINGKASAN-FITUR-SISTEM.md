# Ringkasan Sistem RT29 Minomartani (Sistem Informasi RT/RW Terpadu)

Dokumen ini untuk bahan presentasi ke para Ketua RT/RW: apa saja yang bisa dikerjakan sistem ini, siapa yang bisa pakai, dan kenapa berguna dipakai bersama (satu sistem untuk banyak RT/RW).

---

## 1. Gambaran Umum

Sistem ini web-based (dibuka lewat browser, HP atau komputer, tanpa install apa-apa). Satu sistem dipakai bersama oleh banyak RT dan RW sekaligus (**multi-tenant**) — data tiap RT terpisah aman, tapi RW bisa lihat rekap gabungan dari RT-RT di bawahnya.

Ada 2 sisi:
- **Sisi publik** — bisa diakses warga tanpa login (info RT, berita, cek alamat, ajukan surat).
- **Sisi admin** — login khusus pengurus RT/RW untuk kelola data.

---

## 2. Fitur Sisi Publik (Warga, tanpa login)

| Fitur | Fungsi |
|---|---|
| **Halaman Beranda RT** | Profil RT: data ketua RT/RW, jumlah KK, jumlah warga (laki-laki/perempuan), jumlah alamat/rumah, berita terbaru. |
| **Berita/Pengumuman** | Warga bisa baca pengumuman resmi RT (kegiatan, info penting), tiap berita punya halaman detail sendiri. |
| **Cek Detail Alamat via QR Code** | Setiap rumah bisa dipasangi stiker QR code. Warga/tamu scan QR → langsung lihat info alamat tsb (RT, blok, dsb). |
| **Layanan Surat Online** | Warga ajukan permohonan surat keterangan RT secara online: isi NIK + PIN alamat rumah (PIN diset RT), lalu isi maksud & keperluan surat. Tidak perlu datang ke rumah Ketua RT. |
| **Landing page RW** | RW yang punya subdomain sendiri melihat halaman ringkasan gabungan RT-RT di bawahnya. |

---

## 3. Fitur Sisi Admin — Data Kependudukan

| Modul | Fungsi |
|---|---|
| **Kelola Warga** | Data lengkap tiap warga: NIK, KK, nama, alamat, pekerjaan, status dalam keluarga, status penduduk (tetap/pendatang/pindah), dll. Tambah, edit, lihat detail. Bisa **export** data warga (untuk siapa yang diizinkan). |
| **Kelola Alamat/Rumah** | Data tiap rumah/alamat di wilayah RT: alamat, blok, PIN rumah (dipakai warga untuk verifikasi saat ajukan surat online), dan **generate QR Code** per alamat untuk ditempel di rumah. |
| **Kelola Pekerjaan** | Master data daftar jenis pekerjaan (dipakai saat isi data warga). Data ini dipakai bersama semua RT (dikelola superadmin). |

---

## 4. Fitur Sisi Admin — Layanan Surat & Pengumuman

| Modul | Fungsi |
|---|---|
| **Persetujuan Surat** | Semua pengajuan surat dari warga (via halaman Layanan publik) masuk ke sini. Pengurus RT tinggal **lihat detail lalu setujui** — tidak perlu ketik ulang surat dari nol. |
| **Kelola Berita** | Pengurus RT tulis/edit/publish pengumuman & berita yang tampil di beranda publik. |

---

## 5. Fitur Sisi Admin — Kegiatan Warga

| Modul | Fungsi |
|---|---|
| **Presensi Acara** | Catat kehadiran warga di acara RT (kerja bakti, rapat, arisan, dll). Buat acara → tandai hadir/tidak hadir per warga. Bisa tambah warga baru langsung dari form kalau belum terdaftar. Ada opsi **scan e-KTP (RFID)** untuk absen lebih cepat tanpa cari nama manual. Hasil bisa di-**export ke Excel/PDF** untuk laporan/arsip. |
| **Kesehatan Lansia (Posyandu Lansia)** | Catat data & riwayat kegiatan kesehatan lansia per kegiatan (pemeriksaan, dsb): siapa peserta, catatan hasil per kunjungan. Sama seperti Presensi (scan e-KTP, tambah warga baru, export Excel/PDF, cetak/gambar), tapi mencatat detail per orang, bukan cuma hadir/tidak. Modul ini juga bisa diakses pengurus RW untuk pantau lintas RT di wilayahnya. |

**Nilai jual ke RT lain:** dua modul ini menghemat waktu rekap manual di kertas/Excel terpisah — semua kegiatan tercatat rapi, riwayat kehadiran per warga bisa dilihat kapan saja, laporan tinggal export/cetak.

---

## 6. Fitur Sisi Admin — Inventaris & Laporan RW

| Modul | Fungsi |
|---|---|
| **Inventaris** | Data barang milik RT (tenda, kursi, sound system, dll) lengkap foto, bisa tambah/edit/hapus — jadi ada catatan resmi aset RT. |
| **Rekap RW** | Khusus pengurus RW: lihat rekap jumlah warga per RT di wilayahnya (read-only, tanpa bisa ubah data RT orang lain), bisa export. |

---

## 7. Manajemen Sistem (Superadmin / Developer)

Bagian ini teknis, biasanya dipegang admin pusat/pengembang, tapi penting dijelaskan agar RT paham sistem ini terkelola rapi:

| Modul | Fungsi |
|---|---|
| **Manajemen Pengguna (Users)** | Atur akun pengurus tiap RT: siapa boleh login, jadi admin RT apa, menu apa saja yang boleh diakses (misal: RT A boleh akses Presensi tapi tidak Kesehatan). |
| **Manajemen RT/RW (Tenants)** | Tambah RT/RW baru ke sistem ini — jadi RT lain yang mau ikut gabung tinggal didaftarkan, tidak perlu bikin sistem dari nol. |
| **Sinkronisasi Database (DB Sync)** | Backup & pemulihan data database secara terjadwal/manual antara server lokal dan server produksi. |
| **Log Sistem** | Catatan error teknis untuk keperluan perbaikan/debug oleh developer. |

---

## 8. Hak Akses (Siapa Bisa Apa)

| Peran | Akses |
|---|---|
| **Superadmin** | Akses penuh ke semua RT, semua fitur, termasuk manajemen RT/RW dan pengguna. |
| **Admin (Pengurus RT)** | Kelola data RT-nya sendiri: warga, alamat, surat, berita, presensi, kesehatan, inventaris — sesuai menu yang diaktifkan untuk akunnya. |
| **Pengurus RW** | Akses read-only rekap seluruh RT di wilayah RW-nya + kelola modul Kesehatan Lansia & Presensi lintas RT. |
| **Developer** | Akses teknis penuh untuk pemeliharaan sistem. |

Setiap akun admin **hanya melihat data RT-nya sendiri** — data antar RT tidak bercampur, walau satu sistem dipakai bersama.

---

## 9. Kenapa RT Lain Sebaiknya Ikut Gabung?

1. **Tidak perlu bikin sistem sendiri** — tinggal didaftarkan sebagai tenant baru, langsung bisa pakai semua fitur di atas.
2. **Data warga rapi & terpusat** — tidak lagi tercecer di Excel/WhatsApp/kertas.
3. **Layanan surat online** — warga tidak perlu datang langsung ke rumah Ketua RT.
4. **Rekap kegiatan otomatis** — presensi & kesehatan lansia tinggal export, tidak perlu rekap manual.
5. **QR Code alamat** — memudahkan identifikasi rumah (kurir, tamu, layanan darurat).
6. **Biaya bersama** — satu sistem dipakai banyak RT, jadi biaya pengembangan & hosting ditanggung bersama, bukan sendiri-sendiri.
