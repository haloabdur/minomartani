<?php

use App\Libraries\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * @internal
 */
final class DashboardTenantSwitchTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = null;
    private int $rtB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();

        // Seed a second RT next to the default (1)
        $db = Database::connect();
        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test', 'is_aktif' => 1]);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function testSuperadminCanSwitchTenant(): void
    {
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => 'superadmin_switch_test',
            'email'    => 'super_switch@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');

        auth()->login($superadmin);

        $result = $this->controller(\App\Controllers\Admin\Dashboard::class)
            ->execute('switchTenant', $this->rtB);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($this->rtB, session('tenant_rt_id'));
    }

    public function testNonSuperadminCannotSwitchTenant(): void
    {
        $userModel = model(UserModel::class);
        $admin = new User([
            'username' => 'rt_admin_switch_test',
            'email'    => 'rt_switch@example.com',
            'password' => 'secret123',
            'id_rt'    => 1,
        ]);
        $userModel->save($admin);
        $admin = $userModel->findById($userModel->getInsertID());
        $admin->addGroup('admin');

        auth()->login($admin);

        $result = $this->controller(\App\Controllers\Admin\Dashboard::class)
            ->execute('switchTenant', $this->rtB);

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
