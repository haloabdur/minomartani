<?php

namespace Tests\Controllers;

use App\Libraries\TenantContext;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Test subdomain-based tenant isolation for admin access.
 * Ensures that RT/RW admin accounts cannot access the wrong tenant's
 * subdomain, even with correct credentials for their own tenant.
 *
 * @internal
 */
final class SubdomainAdminIsolationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    use AuthenticationTesting;

    protected $namespace = null;
    private int $rt29Id = 1;
    private int $rt28Id;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();
        helper('subdomain');

        $db = Database::connect();
        $rwId = 1; // Default RW from migrations

        // Seed RT 28 next to RT 29
        if ($db->table('rt')->where('slug', 'rt28-test')->countAllResults() === 0) {
            $db->table('rt')->insert([
                'id_rw' => $rwId,
                'nama' => 'RT 28 Test',
                'slug' => 'rt28-test',
                'subdomain' => 'rt28-test',
                'is_aktif' => 1,
            ]);
        }
        $this->rt28Id = (int) $db->table('rt')->where('slug', 'rt28-test')->get()->getRow()->id_rt;

        // Ensure RT 29 has the subdomain set
        $db->table('rt')->where('id_rt', $this->rt29Id)->update(['subdomain' => 'rt29']);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function testRt28AdminRejectedOnRt29Subdomain(): void
    {
        $rt28Admin = $this->createUserWithRT('rt28admin@test.local', $this->rt28Id);

        $result = $this->actingAs($rt28Admin)
            ->withSession()
            ->withHeaders(['Host' => 'rt29.minomartani.com'])
            ->get('admin/dashboard');

        $result->assertRedirectTo('login');
    }

    public function testRt29AdminAllowedOnRt29Subdomain(): void
    {
        $rt29Admin = $this->createUserWithRT('rt29admin@test.local', $this->rt29Id);

        $result = $this->actingAs($rt29Admin)
            ->withSession()
            ->withHeaders(['Host' => 'rt29.minomartani.com'])
            ->get('admin/dashboard');

        $result->assertOK();
    }

    public function testRt28AdminAllowedOnOwnSubdomain(): void
    {
        $rt28Admin = $this->createUserWithRT('rt28admin2@test.local', $this->rt28Id);

        $result = $this->actingAs($rt28Admin)
            ->withSession()
            ->withHeaders(['Host' => 'rt28-test.minomartani.com'])
            ->get('admin/dashboard');

        $result->assertOK();
    }

    public function testSuperadminAutoScopedOnRtSubdomain(): void
    {
        $superadmin = $this->createSuperadmin('super@test.local');

        $result = $this->actingAs($superadmin)
            ->withSession()
            ->withHeaders(['Host' => 'rt28-test.minomartani.com'])
            ->get('admin/dashboard');

        $result->assertOK();
        // Session should have been set to rt28's id
        $this->assertSame($this->rt28Id, session('tenant_rt_id'));
    }

    public function testSuperadminKeepsDropdownBehaviorOnCentralDomain(): void
    {
        $superadmin = $this->createSuperadmin('super2@test.local');

        // Central domain (apex or unmatched host) should trigger the dropdown logic
        $result = $this->actingAs($superadmin)
            ->withSession()
            ->withHeaders(['Host' => 'minomartani.com'])
            ->get('admin/dashboard');

        $result->assertOK();
        // Should have seeded the first active RT
        $this->assertNotNull(session('tenant_rt_id'));
    }

    private function createUserWithRT(string $email, int $idRt): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => str_replace('@', '_at_', $email),
            'email' => $email,
            'password' => 'secret123',
            'id_rt' => $idRt,
        ]);
        $userModel->save($user);
        $user = $userModel->findById($userModel->getInsertID());
        $user->addGroup('admin');

        return $user;
    }

    public function testSuperadminSwitchTenantRedirectsToSubdomain(): void
    {
        $superadmin = $this->createSuperadmin('super_switch_subdomain@test.local');

        $db = Database::connect();
        $db->table('rt')->where('id_rt', $this->rt28Id)->update(['subdomain' => 'rt28-test']);

        $result = $this->actingAs($superadmin)
            ->withSession()
            ->withHeaders(['Host' => 'rt29.minomartani.com'])
            ->get('admin/switch-tenant/' . $this->rt28Id);

        $result->assertRedirectTo('http://rt28-test.minomartani.com/admin/dashboard');
        $this->assertSame($this->rt28Id, session('tenant_rt_id'));
    }

    public function testSuperadminSwitchTenantToNoSubdomainRedirectsToCentralDomain(): void
    {
        $superadmin = $this->createSuperadmin('super_switch_no_subdomain@test.local');

        $db = Database::connect();
        $db->table('rt')->where('id_rt', $this->rt28Id)->update(['subdomain' => null]);

        $result = $this->actingAs($superadmin)
            ->withSession()
            ->withHeaders(['Host' => 'rt29.minomartani.com'])
            ->get('admin/switch-tenant/' . $this->rt28Id);

        $result->assertRedirectTo('http://minomartani.com/admin/dashboard');
        $this->assertSame($this->rt28Id, session('tenant_rt_id'));
    }

    private function createSuperadmin(string $email): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => str_replace('@', '_at_', $email),
            'email' => $email,
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $user = $userModel->findById($userModel->getInsertID());
        $user->addGroup('superadmin');

        return $user;
    }
}

