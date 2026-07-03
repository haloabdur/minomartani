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
        $this->assertNotFilter('admin/warga', 'before', 'group:admin');
        $this->assertFilter('admin/warga', 'before', 'session');
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

    public function testRekapRoutesAllowRwAndSuperadminOnly(): void
    {
        $this->assertFilter('admin/rekap', 'before', 'group:rw,superadmin');
        $this->assertFilter('admin/rekap/warga/([0-9]+)', 'before', 'group:rw,superadmin');
    }
}
