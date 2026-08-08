<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Front-end routes
$routes->get('/', 'Home::index');
$routes->get('detail/(:any)', 'Home::alamat/$1');
$routes->get('berita/(:any)', 'Home::berita/$1');
$routes->get('layanan', 'Layanan::index');
$routes->post('layanan/store', 'Layanan::store');
$routes->get('layanan/sukses', 'Layanan::sukses');

// DB Sync API Endpoint (Token-protected, bypasses session filters)
$routes->post('api/dbsync', 'Admin\DbSync::api');

// Shield auth routes (login, register, logout)
service('auth')->routes($routes);

// Admin routes (protected by Shield session filter and tenant filter)
$routes->group('admin', ['filter' => ['session', 'tenant']], function ($routes) {
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('dashboard', 'Admin\Dashboard::index');
    $routes->get('switch-tenant/(:num)', 'Admin\Dashboard::switchTenant/$1');

    // Warga - per-user menu access, see Config\AuthGroups + Admin\Users.
    // The 'export' route carries a second filter: downloading resident
    // data is its own per-user permission ('menu.export'), granted
    // separately from merely being able to read the module on screen.
    // CI4 merges group filters with per-route filters, so both run.
    $routes->group('warga', ['filter' => 'menuaccess:warga'], function ($routes) {
        $routes->get('/', 'Admin\Warga::index');
        $routes->get('add', 'Admin\Warga::add');
        $routes->post('store', 'Admin\Warga::store');
        $routes->get('view/(:num)', 'Admin\Warga::view/$1');
        $routes->get('edit/(:num)', 'Admin\Warga::edit/$1');
        $routes->post('update/(:num)', 'Admin\Warga::update/$1');
        $routes->get('export', 'Admin\Warga::export', ['filter' => 'menuaccess:export']);
    });

    // Alamat - per-user menu access, see Config\AuthGroups + Admin\Users
    $routes->group('alamat', ['filter' => 'menuaccess:alamat'], function ($routes) {
        $routes->get('/', 'Admin\Alamat::index');
        $routes->get('add', 'Admin\Alamat::add');
        $routes->post('store', 'Admin\Alamat::store');
        $routes->get('edit/(:num)', 'Admin\Alamat::edit/$1');
        $routes->post('update/(:num)', 'Admin\Alamat::update/$1');
        $routes->get('generate_qrcode/(:num)', 'Admin\Alamat::generate_qrcode/$1');
    });

    // Berita - per-user menu access, see Config\AuthGroups + Admin\Users
    $routes->group('berita', ['filter' => 'menuaccess:berita'], function ($routes) {
        $routes->get('/', 'Admin\Berita::index');
        $routes->get('add', 'Admin\Berita::add');
        $routes->post('store', 'Admin\Berita::store');
        $routes->get('edit/(:num)', 'Admin\Berita::edit/$1');
        $routes->post('update/(:num)', 'Admin\Berita::update/$1');
    });

    // Inventaris
    $routes->get('inventaris', 'Admin\Inventaris::index');
    $routes->get('inventaris/add', 'Admin\Inventaris::add');
    $routes->post('inventaris/store', 'Admin\Inventaris::store');
    $routes->get('inventaris/edit/(:num)', 'Admin\Inventaris::edit/$1');
    $routes->post('inventaris/update/(:num)', 'Admin\Inventaris::update/$1');
    $routes->get('inventaris/delete/(:num)', 'Admin\Inventaris::delete/$1');

    // Pekerjaan - shared lookup table (no id_rt), so edits affect every
    // tenant; restrict to the 'superadmin' Shield group like other
    // cross-tenant master data (users, tenants, dbsync)
    $routes->group('pekerjaan', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Pekerjaan::index');
        $routes->get('add', 'Admin\Pekerjaan::add');
        $routes->post('store', 'Admin\Pekerjaan::store');
        $routes->get('edit/(:num)', 'Admin\Pekerjaan::edit/$1');
        $routes->post('update/(:num)', 'Admin\Pekerjaan::update/$1');
    });

    // Surat - requests come in via the public Layanan form; admin only
    // reviews and approves them here. The old manual add/edit routes
    // were removed (they pointed at no_surat/id_alamat columns that
    // don't exist in the surat table, and at admin/tambah_surat.php /
    // admin/ubah_surat.php views that were never created).
    $routes->get('surat', 'Admin\Surat::index');
    $routes->get('surat/view/(:num)', 'Admin\Surat::view/$1');
    $routes->get('surat/setuju/(:num)', 'Admin\Surat::setuju/$1');

    // Users - highest blast-radius admin surface, restricted to the
    // 'superadmin' Shield group
    $routes->group('users', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Users::index');
        $routes->get('add', 'Admin\Users::add');
        $routes->post('store', 'Admin\Users::store');
        $routes->get('edit/(:num)', 'Admin\Users::edit/$1');
        $routes->post('update/(:num)', 'Admin\Users::update/$1');
        $routes->get('delete/(:num)', 'Admin\Users::delete/$1');
    });

    // Tenant Management (Superadmin only)
    $routes->group('tenants', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Tenants::index');
        $routes->get('add-rt', 'Admin\Tenants::addRt');
        $routes->post('store-rt', 'Admin\Tenants::storeRt');
        $routes->get('edit-rt/(:num)', 'Admin\Tenants::editRt/$1');
        $routes->post('update-rt/(:num)', 'Admin\Tenants::updateRt/$1');
        
        $routes->get('add-rw', 'Admin\Tenants::addRw');
        $routes->post('store-rw', 'Admin\Tenants::storeRw');
        $routes->get('edit-rw/(:num)', 'Admin\Tenants::editRw/$1');
        $routes->post('update-rw/(:num)', 'Admin\Tenants::updateRw/$1');
    });

    // Database Sync Group (Superadmin only)
    $routes->group('dbsync', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\DbSync::index');
        $routes->get('export', 'Admin\DbSync::export');
        $routes->post('import', 'Admin\DbSync::import');
        $routes->post('push', 'Admin\DbSync::push');
        $routes->post('pull', 'Admin\DbSync::pull');
        $routes->post('migrate', 'Admin\DbSync::migrate');
        $routes->post('check-migrations', 'Admin\DbSync::checkMigrations');
    });

    // CI error log viewer (Superadmin only)
    $routes->group('logs', ['filter' => 'group:superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Logs::index');
        $routes->post('delete', 'Admin\Logs::delete');
    });

    // RW rekap - read-only; per-user menu access for 'rw' (see
    // Config\AuthGroups + Admin\Users), superadmin bypasses via the
    // 'menu.*' matrix wildcard.
    $routes->group('rekap', ['filter' => 'menuaccess:rekap'], function ($routes) {
        $routes->get('/', 'Admin\Rekap::index');
        $routes->get('warga/(:num)', 'Admin\Rekap::warga/$1');
        $routes->get('warga/(:num)/export', 'Admin\Rekap::export/$1', ['filter' => 'menuaccess:export']);
    });

    // Kesehatan Lansia - per-user menu access for both 'admin' and 'rw'
    // (see Config\AuthGroups + Admin\Users), superadmin bypasses via the
    // 'menu.*' matrix wildcard.
    $routes->group('kesehatan', ['filter' => 'menuaccess:kesehatan'], function ($routes) {
        $routes->get('/', 'Admin\Kesehatan::index');
        $routes->get('add', 'Admin\Kesehatan::add');
        $routes->post('store', 'Admin\Kesehatan::store');
        $routes->get('kegiatan/(:num)', 'Admin\Kesehatan::kegiatan/$1');
        $routes->get('kegiatan/(:num)/export', 'Admin\Kesehatan::exportExcel/$1', ['filter' => 'menuaccess:export']);
        $routes->get('kegiatan/(:num)/export/pdf', 'Admin\Kesehatan::exportPdf/$1', ['filter' => 'menuaccess:export']);
        $routes->get('kegiatan/(:num)/cetak/(:num)', 'Admin\Kesehatan::cetakPdf/$1/$2', ['filter' => 'menuaccess:export']);
        $routes->get('kegiatan/(:num)/gambar/(:num)', 'Admin\Kesehatan::cetakGambar/$1/$2', ['filter' => 'menuaccess:export']);
        $routes->get('kegiatan/(:num)/edit', 'Admin\Kesehatan::editKegiatan/$1');
        $routes->post('kegiatan/(:num)/update', 'Admin\Kesehatan::updateKegiatan/$1');
        $routes->post('kegiatan/(:num)/simpan', 'Admin\Kesehatan::simpanCatatan/$1');
        $routes->post('kegiatan/(:num)/tambah-peserta', 'Admin\Kesehatan::tambahPeserta/$1');
        $routes->post('kegiatan/(:num)/tambah-warga-baru', 'Admin\Kesehatan::tambahWargaBaru/$1');
        $routes->get('kegiatan/(:num)/catatan/(:num)/hapus', 'Admin\Kesehatan::hapusCatatan/$1/$2');
        $routes->get('kegiatan/(:num)/scan-rfid', 'Admin\Kesehatan::scanRfid/$1');
        $routes->post('kegiatan/(:num)/daftar-rfid', 'Admin\Kesehatan::daftarRfid/$1');
        $routes->get('warga/(:num)', 'Admin\Kesehatan::warga/$1');
    });

    // Presensi Acara - per-user menu access for both 'admin' and 'rw'
    // (see Config\AuthGroups + Admin\Users), superadmin bypasses via the
    // 'menu.*' matrix wildcard.
    $routes->group('presensi', ['filter' => 'menuaccess:presensi'], function ($routes) {
        $routes->get('/', 'Admin\Presensi::index');
        $routes->get('add', 'Admin\Presensi::add');
        $routes->post('store', 'Admin\Presensi::store');
        $routes->get('acara/(:num)', 'Admin\Presensi::acara/$1');
        $routes->get('acara/(:num)/export', 'Admin\Presensi::exportExcel/$1', ['filter' => 'menuaccess:export']);
        $routes->get('acara/(:num)/export/pdf', 'Admin\Presensi::exportPdf/$1', ['filter' => 'menuaccess:export']);
        $routes->get('acara/(:num)/edit', 'Admin\Presensi::editAcara/$1');
        $routes->post('acara/(:num)/update', 'Admin\Presensi::updateAcara/$1');
        $routes->post('acara/(:num)/tandai', 'Admin\Presensi::tandai/$1');
        $routes->get('acara/(:num)/hapus/(:num)', 'Admin\Presensi::hapusKehadiran/$1/$2');
        $routes->post('acara/(:num)/tambah-warga-baru', 'Admin\Presensi::tambahWargaBaru/$1');
        $routes->get('acara/(:num)/scan-rfid', 'Admin\Presensi::scanRfid/$1');
        $routes->post('acara/(:num)/daftar-rfid', 'Admin\Presensi::daftarRfid/$1');
        $routes->get('warga/(:num)', 'Admin\Presensi::warga/$1');
    });
});

// Slug-prefixed front-end routes (optional tenant routing)
$routes->get('(:segment)', 'Home::index/$1');
$routes->get('(:segment)/detail/(:any)', 'Home::alamat/$1/$2');
$routes->get('(:segment)/berita/(:any)', 'Home::berita/$1/$2');
$routes->get('(:segment)/layanan', 'Layanan::index/$1');
$routes->post('(:segment)/layanan/store', 'Layanan::store/$1');
$routes->get('(:segment)/layanan/sukses', 'Layanan::sukses/$1');

