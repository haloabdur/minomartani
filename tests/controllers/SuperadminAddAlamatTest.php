<?php

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use Config\Database;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\AlamatModel;

/**
 * @internal
 */
final class SuperadminAddAlamatTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    use AuthenticationTesting;

    protected $namespace = null;
    private int $rtA;
    private int $rtB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');

        // Disable CSRF filter for tests
        $filters = config('Filters');
        if (isset($filters->globals['before'])) {
            foreach ($filters->globals['before'] as $key => $value) {
                if ($value === 'csrf' || $key === 'csrf') {
                    unset($filters->globals['before'][$key]);
                }
            }
        }

        $db = Database::connect();
        
        // Ensure rtA is RT 29
        $this->rtA = (int) $db->table('rt')->where('slug', 'rt29')->get()->getRow()->id_rt;

        // Seed a second RT (RT 30) for testing if not exists
        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test', 'is_aktif' => 1]);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;
    }

    private function createSuperadmin(string $suffix): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'superadmin_alamat_test_' . $suffix,
            'email'    => 'super_alamat_' . $suffix . '@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $userId = $userModel->getInsertID();

        $user = $userModel->findById($userId);
        $user->addGroup('superadmin');
        return $user;
    }

    private function createAdmin(int $rtId, string $suffix): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'admin_alamat_test_' . $suffix,
            'email'    => 'admin_alamat_' . $suffix . '@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $userId = $userModel->getInsertID();

        // Update tenant columns directly
        Database::connect()->table('users')
            ->where('id', $userId)
            ->update([
                'id_rt' => $rtId,
            ]);

        $user = $userModel->findById($userId);
        $user->addGroup('admin');
        return $user;
    }

    public function testSuperadminCanSeeRwRtFields(): void
    {
        $superadmin = $this->createSuperadmin('see');

        $response = $this->actingAs($superadmin)
            ->withSession([
                'tenant_rt_id' => $this->rtA,
            ])
            ->get('admin/alamat/add');

        $response->assertOK();
        $response->assertSee('id="select-rw"');
        $response->assertSee('id="select-rt"');
    }

    public function testAdminCannotAccessAddPage(): void
    {
        $admin = $this->createAdmin($this->rtA, 'cantsee');

        $response = $this->actingAs($admin)
            ->withSession([
                'tenant_rt_id' => $this->rtA,
            ])
            ->get('admin/alamat/add');

        $response->assertRedirectTo('admin/alamat');
    }

    public function testSuperadminCanStoreAlamatForSpecificRt(): void
    {
        $superadmin = $this->createSuperadmin('store');

        $response = $this->actingAs($superadmin)
            ->withSession([
                'tenant_rt_id' => $this->rtA,
            ])
            ->post('admin/alamat/store', [
                'jalan' => 'BANDENG 2',
                'nomor' => '77',
                'id_rt' => $this->rtB, // explicitly specify RT B
            ]);

        $response->assertRedirectTo('admin/alamat');

        // Check it was stored under RT B
        $db = Database::connect();
        $row = $db->table('alamat')->where('alamat', 'BANDENG 2/77')->get()->getRow();
        $this->assertNotNull($row);
        $this->assertSame($this->rtB, (int)$row->id_rt);
    }

    public function testAdminCannotStoreAlamat(): void
    {
        $admin = $this->createAdmin($this->rtA, 'storeown');

        $response = $this->actingAs($admin)
            ->withSession([
                'tenant_rt_id' => $this->rtA,
            ])
            ->post('admin/alamat/store', [
                'jalan' => 'BANDENG 3',
                'nomor' => '88',
                'id_rt' => $this->rtB,
            ]);

        $response->assertRedirectTo('admin/alamat');

        // Check that the address was NOT stored
        $db = Database::connect();
        $row = $db->table('alamat')->where('alamat', 'BANDENG 3/88')->get()->getRow();
        $this->assertNull($row);
    }
}
