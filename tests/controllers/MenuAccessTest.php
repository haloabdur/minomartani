<?php

namespace Tests\Controllers;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Per-user menu access: an 'admin'/'rw' group user only reaches a menu
 * (Warga/Alamat/Berita/Kesehatan/Rekap) if explicitly granted the
 * matching 'menu.*' permission (Admin\Users menu access checkboxes),
 * enforced by App\Filters\MenuAccessFilter. superadmin/developer bypass
 * everything via a 'menu.*' matrix wildcard (Config\AuthGroups).
 * Kesehatan Lansia is independently assignable to either 'admin' or
 * 'rw' - no group-wide bypass for either.
 *
 * @internal
 */
final class MenuAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    use AuthenticationTesting;

    protected $namespace = null;
    private int $rtId;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');

        $db = Database::connect();
        $this->rtId = (int) $db->table('rt')->where('slug', 'rt29')->get()->getRow()->id_rt;
    }

    private function createAdmin(string $suffix, array $menuPermissions = []): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'menu_admin_' . $suffix,
            'email'    => 'menu_admin_' . $suffix . '@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $userId = $userModel->getInsertID();

        Database::connect()->table('users')
            ->where('id', $userId)
            ->update(['id_rt' => $this->rtId]);

        $user = $userModel->findById($userId);
        $user->addGroup('admin');
        if ($menuPermissions !== []) {
            $user->syncPermissions(...$menuPermissions);
        }

        return $user;
    }

    public function testAdminWithoutPermissionIsBlockedFromWarga(): void
    {
        $admin = $this->createAdmin('nowarga');

        $response = $this->actingAs($admin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/warga');

        $response->assertRedirectTo('admin/dashboard');
    }

    public function testAdminWithPermissionCanReachWarga(): void
    {
        $admin = $this->createAdmin('haswarga', ['menu.warga']);

        $response = $this->actingAs($admin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/warga');

        $response->assertOK();
    }

    public function testWargaPermissionDoesNotGrantAlamatAccess(): void
    {
        $admin = $this->createAdmin('onlywarga', ['menu.warga']);

        $response = $this->actingAs($admin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/alamat');

        // Denied here, but not dumped onto a blanket dashboard: bounced
        // to the one menu this admin actually has (see
        // testAdminLandsOnFirstAssignedMenuNotDashboard).
        $response->assertRedirectTo('admin/warga');
    }

    public function testSuperadminReachesWargaWithoutExplicitPermission(): void
    {
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => 'menu_superadmin',
            'email'    => 'menu_superadmin@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');

        $response = $this->actingAs($superadmin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/warga');

        $response->assertOK();
    }

    private function createRw(string $suffix, array $menuPermissions = []): User
    {
        $userModel = model(UserModel::class);
        $rw = new User([
            'username' => 'menu_rw_' . $suffix,
            'email'    => 'menu_rw_' . $suffix . '@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($rw);
        $rwId = $userModel->getInsertID();

        $idRw = (int) Database::connect()->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
        Database::connect()->table('users')->where('id', $rwId)->update(['id_rw' => $idRw]);

        $rw = $userModel->findById($rwId);
        $rw->addGroup('rw');
        if ($menuPermissions !== []) {
            $rw->syncPermissions(...$menuPermissions);
        }

        return $rw;
    }

    public function testRwWithKesehatanPermissionCanReachKesehatan(): void
    {
        $rw = $this->createRw('kesehatan', ['menu.kesehatan']);

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/kesehatan');

        $response->assertOK();
    }

    public function testRwWithoutKesehatanPermissionIsBlockedFromKesehatan(): void
    {
        $rw = $this->createRw('nokesehatan', ['menu.rekap']);

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/kesehatan');

        // Has 'menu.rekap' but not 'menu.kesehatan': bounced to the one
        // menu they actually have, not a blanket dashboard.
        $response->assertRedirectTo('admin/rekap');
    }

    public function testRwWithoutRekapPermissionIsBlockedFromRekap(): void
    {
        $rw = $this->createRw('norekap', ['menu.kesehatan']);

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/rekap');

        // Has 'menu.kesehatan' but not 'menu.rekap': bounced to the one
        // menu they actually have, not a blanket admin/dashboard they
        // can't reach either.
        $response->assertRedirectTo('admin/kesehatan');
    }

    public function testRwWithRekapPermissionCanReachRekap(): void
    {
        $rw = $this->createRw('hasrekap', ['menu.rekap']);

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/rekap');

        $response->assertOK();
    }

    public function testRwWithNoMenuPermissionsIsLoggedOut(): void
    {
        // Neither 'menu.rekap' nor 'menu.kesehatan' assigned - a state
        // Admin\Users' "at least one menu" validation should prevent,
        // but TenantFilter must not infinite-redirect if it ever
        // happens (e.g. an account edited directly in the DB).
        $rw = $this->createRw('nomenus');

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/dashboard');

        $response->assertRedirectTo('login');
    }

    public function testAdminLandsOnFirstAssignedMenuNotDashboard(): void
    {
        $admin = $this->createAdmin('landing', ['menu.alamat']);

        $response = $this->actingAs($admin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/dashboard');

        $response->assertRedirectTo('admin/alamat');
    }

    public function testRwWithoutRekapLandsOnKesehatan(): void
    {
        $rw = $this->createRw('landing', ['menu.kesehatan']);

        $response = $this->actingAs($rw)
            ->withSession(['tenant_rw_id' => $rw->id_rw])
            ->get('admin/dashboard');

        $response->assertRedirectTo('admin/kesehatan');
    }

    public function testSuperadminStillLandsOnDashboard(): void
    {
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => 'menu_superadmin_landing',
            'email'    => 'menu_superadmin_landing@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');

        $response = $this->actingAs($superadmin)
            ->withSession(['tenant_rt_id' => $this->rtId])
            ->get('admin/dashboard');

        $response->assertOK();
    }
}
