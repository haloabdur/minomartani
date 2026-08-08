<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FilterTestTrait;

/**
 * Regression guard for app/Config/Filters.php + app/Config/Routes.php:
 * confirms CSRF is actually wired to public POST routes, and that
 * admin/users/* is gated behind the 'admin' Shield group and not just
 * "logged in" (the routes previously only carried the 'session'
 * filter, letting any authenticated group reach user management).
 *
 * @internal
 */
final class RouteFilterTest extends CIUnitTestCase
{
    use FilterTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFilterTestTrait();
        // getFiltersForRoute() resolves route-specific (non-global)
        // filters by matching the collection's stored options for the
        // *current* HTTP verb (RouteCollection::getHTTPVerb()), which
        // defaults to whatever the CLI test runner's pseudo-request
        // verb is - not 'get'/'post'. setHTTPVerb() is the documented
        // testing override.
        $this->collection->setHTTPVerb('get');
    }

    public function testCsrfAppliesToPublicFormRoute(): void
    {
        $this->assertFilter('layanan/store', 'before', 'csrf');
    }

    public function testCsrfAppliesGlobally(): void
    {
        $this->collection->setHTTPVerb('post');
        $this->assertFilter('admin/warga/store', 'before', 'csrf');
    }

    public function testAdminUsersRouteRequiresSuperadminGroup(): void
    {
        $this->assertFilter('admin/users', 'before', 'group:superadmin');

        $this->collection->setHTTPVerb('get');
        // Dynamic routes are keyed by their registered pattern, not a
        // resolved example URI.
        $this->assertFilter('admin/users/delete/([0-9]+)', 'before', 'group:superadmin');
    }

    public function testLoginRouteRequiresTurnstileVerification(): void
    {
        $this->collection->setHTTPVerb('post');
        $this->assertFilter('login', 'before', 'turnstile');
    }

    public function testOrdinaryAdminRouteDoesNotRequireAdminGroup(): void
    {
        // Regular admin routes (warga, alamat, etc.) only require being
        // logged in (any Shield group) - group:admin is deliberately
        // scoped to the higher-blast-radius Users management surface.
        // Fine-grained access is per-user via the 'menuaccess' filter
        // instead (see testMenuGatedRoutesRequireMenuPermission).
        $this->assertNotFilter('admin/warga', 'before', 'group:admin');
        $this->assertFilter('admin/warga', 'before', 'session');
    }

    public function testMenuGatedRoutesRequireMenuPermission(): void
    {
        $this->assertFilter('admin/warga', 'before', 'menuaccess:warga');
        $this->assertFilter('admin/alamat', 'before', 'menuaccess:alamat');
        $this->assertFilter('admin/berita', 'before', 'menuaccess:berita');
        // Kesehatan Lansia is a plain 'menu.kesehatan' permission for
        // both 'admin' and 'rw' now - no group bypass argument.
        $this->assertFilter('admin/kesehatan', 'before', 'menuaccess:kesehatan');
    }

    public function testSyncControllerAndRouteWereRemoved(): void
    {
        $this->assertFalse(
            class_exists('App\Controllers\Admin\Sync'),
            'Admin\\Sync controller still exists - it was meant to be removed once real migrations replaced it',
        );

        $registeredUris = array_keys(service('routes')->loadRoutes()->getRoutes('get'));
        foreach ($registeredUris as $uri) {
            $this->assertStringNotContainsString('sync', $uri, "unexpected sync route still registered: {$uri}");
        }
    }

    public function testAdminRoutesRequireTenantContext(): void
    {
        $this->assertFilter('admin/warga', 'before', 'tenant');
        $this->assertFilter('admin/dashboard', 'before', 'tenant');

        $this->collection->setHTTPVerb('post');
        $this->assertFilter('admin/warga/store', 'before', 'tenant');
    }

    public function testRekapRoutesRequireMenuPermission(): void
    {
        // rw must be individually granted 'menu.rekap' (Admin\Users menu
        // access checkboxes); superadmin bypasses via the 'menu.*'
        // matrix wildcard. See testMenuGatedRoutesRequireMenuPermission.
        $this->assertFilter('admin/rekap', 'before', 'menuaccess:rekap');
        $this->assertFilter('admin/rekap/warga/([0-9]+)', 'before', 'menuaccess:rekap');
    }

    /**
     * Every route that hands resident data to the browser as a file is
     * gated by the separate 'menu.export' permission *in addition to* its
     * owning module's menu permission - being allowed to read a module on
     * screen is not the same as being allowed to walk away with a copy of
     * the data. CI4 merges group filters with per-route ones, so both
     * must show up here.
     */
    public function testDownloadRoutesRequireExportPermission(): void
    {
        $downloadRoutes = [
            'admin/warga/export'                              => 'menuaccess:warga',
            'admin/rekap/warga/([0-9]+)/export'               => 'menuaccess:rekap',
            'admin/kesehatan/kegiatan/([0-9]+)/export'        => 'menuaccess:kesehatan',
            'admin/kesehatan/kegiatan/([0-9]+)/export/pdf'    => 'menuaccess:kesehatan',
            'admin/kesehatan/kegiatan/([0-9]+)/cetak/([0-9]+)' => 'menuaccess:kesehatan',
            'admin/kesehatan/kegiatan/([0-9]+)/gambar/([0-9]+)' => 'menuaccess:kesehatan',
            'admin/presensi/acara/([0-9]+)/export'            => 'menuaccess:presensi',
            'admin/presensi/acara/([0-9]+)/export/pdf'        => 'menuaccess:presensi',
        ];

        foreach ($downloadRoutes as $route => $moduleFilter) {
            $this->assertFilter($route, 'before', 'menuaccess:export');
            // The module's own filter must survive the merge, otherwise
            // an 'rw' account granted only 'menu.export' could reach a
            // module it has no menu permission for.
            $this->assertFilter($route, 'before', $moduleFilter);
        }
    }

    /**
     * The inverse: reading a module on screen must NOT require the export
     * permission, or restricting downloads would lock users out of the
     * module entirely.
     */
    public function testModuleIndexRoutesDoNotRequireExportPermission(): void
    {
        $this->assertNotFilter('admin/warga', 'before', 'menuaccess:export');
        $this->assertNotFilter('admin/rekap', 'before', 'menuaccess:export');
        $this->assertNotFilter('admin/kesehatan', 'before', 'menuaccess:export');
        $this->assertNotFilter('admin/presensi', 'before', 'menuaccess:export');
    }
}
