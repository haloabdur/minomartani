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

    // Warga
    $routes->get('warga', 'Admin\Warga::index');
    $routes->get('warga/add', 'Admin\Warga::add');
    $routes->post('warga/store', 'Admin\Warga::store');
    $routes->get('warga/view/(:num)', 'Admin\Warga::view/$1');
    $routes->get('warga/edit/(:num)', 'Admin\Warga::edit/$1');
    $routes->post('warga/update/(:num)', 'Admin\Warga::update/$1');
    $routes->get('warga/export', 'Admin\Warga::export');

    // Alamat
    $routes->get('alamat', 'Admin\Alamat::index');
    $routes->get('alamat/add', 'Admin\Alamat::add');
    $routes->post('alamat/store', 'Admin\Alamat::store');
    $routes->get('alamat/edit/(:num)', 'Admin\Alamat::edit/$1');
    $routes->post('alamat/update/(:num)', 'Admin\Alamat::update/$1');
    $routes->get('alamat/generate_qrcode/(:num)', 'Admin\Alamat::generate_qrcode/$1');

    // Berita
    $routes->get('berita', 'Admin\Berita::index');
    $routes->get('berita/add', 'Admin\Berita::add');
    $routes->post('berita/store', 'Admin\Berita::store');
    $routes->get('berita/edit/(:num)', 'Admin\Berita::edit/$1');
    $routes->post('berita/update/(:num)', 'Admin\Berita::update/$1');

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

    // Surat
    $routes->get('surat', 'Admin\Surat::index');
    $routes->get('surat/add', 'Admin\Surat::add');
    $routes->post('surat/store', 'Admin\Surat::store');
    $routes->get('surat/view/(:num)', 'Admin\Surat::view/$1');
    $routes->get('surat/edit/(:num)', 'Admin\Surat::edit/$1');
    $routes->post('surat/update/(:num)', 'Admin\Surat::update/$1');
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

    // RW rekap - read-only, rw group (and superadmin)
    $routes->group('rekap', ['filter' => 'group:rw,superadmin'], function ($routes) {
        $routes->get('/', 'Admin\Rekap::index');
        $routes->get('warga/(:num)', 'Admin\Rekap::warga/$1');
        $routes->get('warga/(:num)/export', 'Admin\Rekap::export/$1');
    });
});

// Slug-prefixed front-end routes (optional tenant routing)
$routes->get('(:segment)', 'Home::index/$1');
$routes->get('(:segment)/detail/(:any)', 'Home::alamat/$1/$2');
$routes->get('(:segment)/berita/(:any)', 'Home::berita/$1/$2');
$routes->get('(:segment)/layanan', 'Layanan::index/$1');
$routes->post('(:segment)/layanan/store', 'Layanan::store/$1');
$routes->get('(:segment)/layanan/sukses', 'Layanan::sukses/$1');

