<?php

use App\Libraries\TenantContext;
use App\Models\ActivityLogModel;
use App\Models\WargaModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class AktivitasTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        tenant_set_rt(1);

        $db = Database::connect();
        $db->table('activity_log')->truncate();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function loginSuperadmin(): User
    {
        $userModel = model(UserModel::class);
        $user = $userModel->findByCredentials(['username' => 'superadmin']);
        if (! $user) {
            $user = new User([
                'username' => 'superadmin',
                'email'    => 'superadmin@example.com',
                'password' => 'secret123',
            ]);
            $userModel->save($user);
            $user = $userModel->findById($userModel->getInsertID());
            $user->addGroup('superadmin');
        }

        auth()->login($user);

        return $user;
    }

    public function testIndexRendersSuccessfullyWithStatsAndFilters(): void
    {
        $this->loginSuperadmin();

        $wargaModel = new WargaModel();
        $id = $wargaModel->insert([
            'no_kk'         => '111',
            'nama_warga'    => 'Siti Aminah',
            'nik'           => '8888000011112222',
            'jenis_kelamin' => 'P',
            'tempat_lahir'  => 'Sleman',
            'tanggal_lahir' => '1995-05-10',
            'gol_darah'     => 'O',
            'id_pekerjaan'  => 1,
            'no_hp'         => '08123456789',
            'id_rt'         => 1,
        ]);

        $wargaModel->update($id, ['tempat_lahir' => 'Yogyakarta', 'gol_darah' => 'A']);

        $request = service('request')->withMethod('get');
        $result = $this->controller(\App\Controllers\Admin\Aktivitas::class)
            ->withRequest($request)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $body = $result->getBody();

        $this->assertStringContainsString('Log Aktivitas', $body);
        $this->assertStringContainsString('Total Log RT', $body);
        $this->assertStringContainsString('Filter &amp; Pencarian Log', $body);
        $this->assertStringContainsString('Siti Aminah', $body);
        $this->assertStringContainsString('Tempat Lahir', $body);
        $this->assertStringContainsString('Yogyakarta', $body);
        $this->assertStringContainsString('bootstrap_pagination', $body);
    }

    public function testSearchFilterByKeyword(): void
    {
        $this->loginSuperadmin();

        $db = Database::connect();
        $db->table('activity_log')->insert([
            'id_rt'        => 1,
            'user_name'    => 'superadmin',
            'module'       => 'warga',
            'record_id'    => 101,
            'record_label' => 'Budi Santoso',
            'action'       => 'update',
            'changes'      => json_encode(['tempat_lahir' => ['Solo', 'Sleman']]),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        $db->table('activity_log')->insert([
            'id_rt'        => 1,
            'user_name'    => 'superadmin',
            'module'       => 'warga',
            'record_id'    => 102,
            'record_label' => 'Ahmad Dahlan',
            'action'       => 'update',
            'changes'      => json_encode(['tempat_lahir' => ['Klaten', 'Jogja']]),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $request = service('request')
            ->withMethod('get')
            ->setGlobal('get', ['q' => 'Budi']);

        $result = $this->controller(\App\Controllers\Admin\Aktivitas::class)
            ->withRequest($request)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $body = $result->getBody();
        $this->assertStringContainsString('Budi Santoso', $body);
        $this->assertStringNotContainsString('Ahmad Dahlan', $body);
    }
}
