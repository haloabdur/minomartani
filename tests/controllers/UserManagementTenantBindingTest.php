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
final class UserManagementTenantBindingTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = null;
    private int $rtId;
    private int $rwId;

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        // Get seeded RT/RW ids
        $this->rtId = (int) $db->table('rt')->where('slug', 'rt29')->get()->getRow()->id_rt;
        $this->rwId = (int) $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        // Log in a superadmin to perform actions
        $userModel = model(UserModel::class);
        $superadmin = new User([
            'username' => 'superadmin_user_test',
            'email'    => 'super_user@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($superadmin);
        $superadmin = $userModel->findById($userModel->getInsertID());
        $superadmin->addGroup('superadmin');
        auth()->login($superadmin);
    }

    public function testStoreUserBindsTenantAndGroup(): void
    {
        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'username'  => 'bound_admin',
                'email'     => 'bound_admin@example.com',
                'password'  => 'secret1234',
                'cpassword' => 'secret1234',
                'id_rt'     => $this->rtId,
            ]);

        $result = $this->controller(\App\Controllers\Admin\Users::class)
            ->withRequest($request)
            ->execute('store');

        if (!$result->isRedirect()) {
            var_dump($result->response()->getStatusCode());
            var_dump($result->response()->getHeaders());
            var_dump($result->response()->getBody());
        }
        $this->assertTrue($result->isRedirect());

        $userModel = model(UserModel::class);
        $user = $userModel->findByCredentials(['email' => 'bound_admin@example.com']);

        $this->assertNotNull($user);
        $this->assertSame($this->rtId, (int) $user->id_rt);
        $this->assertNull($user->id_rw);
        $this->assertTrue($user->inGroup('admin'));
        $this->assertFalse($user->inGroup('superadmin'));
    }

    public function testUpdateUserChangesTenantAndGroup(): void
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'bound_admin_up',
            'email'    => 'bound_admin_up@example.com',
            'password' => 'secret1234',
            'id_rt'    => $this->rtId,
        ]);
        $userModel->save($user);
        $user = $userModel->findById($userModel->getInsertID());
        $user->addGroup('admin');

        $request = service('request')
            ->withMethod('post')
            ->setGlobal('post', [
                'username'  => 'bound_admin_up',
                'password'  => '',
                'id_rw'     => $this->rwId,
                'id_rt'     => '',
            ]);

        $result = $this->controller(\App\Controllers\Admin\Users::class)
            ->withRequest($request)
            ->execute('update', $user->id);

        if (!$result->isRedirect()) {
            var_dump($result->response()->getStatusCode());
            var_dump($result->response()->getHeaders());
            var_dump($result->response()->getBody());
        }
        $this->assertTrue($result->isRedirect());

        $user = $userModel->findById($user->id);
        $this->assertNull($user->id_rt);
        $this->assertSame($this->rwId, (int) $user->id_rw);
        $this->assertTrue($user->inGroup('rw'));
        $this->assertFalse($user->inGroup('admin'));
    }
}
