<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * @internal
 */
final class TenantManagementCrudTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSuperadminCanCreateRwAndRt(): void
    {
        // Log in a superadmin to perform actions
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => 'superadmin_tenant_crud_test',
            'email'    => 'super_crud@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');
        auth()->login($superadmin);

        $db = Database::connect();

        // Create RW
        \Config\Services::resetSingle('request');
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'nama' => 'RW 09 Test',
                'subdomain' => 'rw09-test',
            ]);

        $result = $this->withRequest($request)
            ->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('storeRw');

        $this->assertTrue($result->isRedirect());

        $rw = $db->table('rw')->where('slug', 'rw-09-test')->get()->getRow();
        $this->assertNotNull($rw);
        $this->assertSame('RW 09 Test', $rw->nama);
        $this->assertSame('rw09-test', $rw->subdomain);

        // Create RT under that RW
        \Config\Services::resetSingle('request');
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'nama' => 'RT 99 Test',
                'id_rw' => $rw->id_rw,
                'subdomain' => 'rt99-test',
                'is_aktif' => 1,
            ]);

        $result = $this->withRequest($request)
            ->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('storeRt');

        $this->assertTrue($result->isRedirect());

        $rt = $db->table('rt')->where('slug', 'rt-99-test')->get()->getRow();
        $this->assertNotNull($rt);
        $this->assertSame('RT 99 Test', $rt->nama);
        $this->assertSame('rt99-test', $rt->subdomain);
        $this->assertSame((int)$rw->id_rw, (int)$rt->id_rw);
    }

    public function testStoreRtRequiresSubdomain(): void
    {
        $this->loginSuperadmin('super_crud_nosub@example.com');

        $db = Database::connect();
        $idRw = $db->table('rw')->get()->getRow()->id_rw;

        \Config\Services::resetSingle('request');
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'nama' => 'RT NoSub Test',
                'id_rw' => $idRw,
            ]);

        $result = $this->withRequest($request)
            ->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('storeRt');

        $this->assertTrue($result->isRedirect());
        $this->assertNull($db->table('rt')->where('slug', 'rt-nosub-test')->get()->getRow());
    }

    public function testStoreRtRejectsReservedSubdomain(): void
    {
        $this->loginSuperadmin('super_crud_reserved@example.com');

        $db = Database::connect();
        $idRw = $db->table('rw')->get()->getRow()->id_rw;

        \Config\Services::resetSingle('request');
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'nama' => 'RT Reserved Test',
                'id_rw' => $idRw,
                'subdomain' => 'admin',
            ]);

        $result = $this->withRequest($request)
            ->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('storeRt');

        $this->assertTrue($result->isRedirect());
        $this->assertNull($db->table('rt')->where('slug', 'rt-reserved-test')->get()->getRow());
    }

    public function testStoreRtRejectsDuplicateSubdomainAcrossRtAndRw(): void
    {
        $this->loginSuperadmin('super_crud_dup@example.com');

        $db = Database::connect();
        $idRw = $db->table('rw')->get()->getRow()->id_rw;

        // Seed an RW that already owns the target subdomain
        if ($db->table('rw')->where('subdomain', 'shared-dup-test')->countAllResults() === 0) {
            $db->table('rw')->insert([
                'nama' => 'RW Dup Test',
                'slug' => 'rw-dup-test',
                'subdomain' => 'shared-dup-test',
                'is_aktif' => 1,
            ]);
        }

        \Config\Services::resetSingle('request');
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'nama' => 'RT Dup Test',
                'id_rw' => $idRw,
                'subdomain' => 'shared-dup-test',
            ]);

        $result = $this->withRequest($request)
            ->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('storeRt');

        $this->assertTrue($result->isRedirect());
        $this->assertNull($db->table('rt')->where('slug', 'rt-dup-test')->get()->getRow());
    }

    private function loginSuperadmin(string $email): void
    {
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => str_replace(['@', '.'], '_', $email),
            'email'    => $email,
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');
        auth()->login($superadmin);
    }

    public function testRegularAdminAccessRedirects(): void
    {
        // Log in a normal admin
        $userModel = model(UserModel::class);
        $adminUser = new User([
            'username' => 'normal_admin_tenant_test',
            'email'    => 'normal_admin_t@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($adminUser);
        $adminUser = $userModel->findById($userModel->getInsertID());
        $adminUser->addGroup('admin');
        auth()->login($adminUser);

        // We expect RedirectException to be thrown due to initController guard
        $this->expectException(\CodeIgniter\HTTP\Exceptions\RedirectException::class);

        $this->controller(\App\Controllers\Admin\Tenants::class)
            ->execute('index');
    }
}
