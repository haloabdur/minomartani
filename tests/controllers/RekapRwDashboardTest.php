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
 * Verifies that the redesigned Rekap RW aggregator dashboard renders
 * all executive cards, comparative charts, demographic breakdowns,
 * and RT tables properly without error.
 */
final class RekapRwDashboardTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    use AuthenticationTesting;

    protected $namespace = null;

    public function testRekapDashboardRendersSuccessfullyForRw(): void
    {
        helper('tenant');

        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'test_rekap_rw_user',
            'email'    => 'test_rekap_rw_user@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $userId = $userModel->getInsertID();

        $db = Database::connect();
        $rwRow = $db->table('rw')->get()->getRow();
        $idRw = $rwRow ? (int) $rwRow->id_rw : 1;

        $db->table('users')->where('id', $userId)->update(['id_rw' => $idRw]);

        $user = $userModel->findById($userId);
        $user->addGroup('rw');
        $user->syncPermissions('menu.rekap');

        $response = $this->actingAs($user)
            ->withSession(['tenant_rw_id' => $idRw])
            ->get('admin/rekap');

        $response->assertOK();
        $response->assertSee('Pusat Agregator Data RW');
        $response->assertSee('Perbandingan Antar-RT');
        $response->assertSee('Sosial Kependudukan');
        $response->assertSee('Bank Data Golongan Darah RW');
    }
}
