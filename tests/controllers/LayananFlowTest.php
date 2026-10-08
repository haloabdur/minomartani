<?php

namespace Tests\Controllers;

use App\Libraries\PinThrottle;
use App\Libraries\SuratPemohon;
use App\Libraries\TenantContext;
use App\Models\SuratModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Public Layanan flow (alamat + PIN -> pick resident -> prefilled,
 * editable form -> snapshot on `surat`) and the admin review actions.
 *
 * @internal
 */
final class LayananFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;
    use AuthenticationTesting;

    protected $namespace = null;

    private const RT = 1;
    private int $alamatA;
    private int $alamatB;
    private int $wargaA;
    private int $wargaB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();

        // CSRF is covered by RouteFilterTest; take it out of the way here.
        $filters = config('Filters');
        foreach ($filters->globals['before'] ?? [] as $key => $value) {
            if ($value === 'csrf' || $key === 'csrf') {
                unset($filters->globals['before'][$key]);
            }
        }

        $db = Database::connect();

        $db->table('alamat')->insert(['alamat' => 'TEST-LAYANAN/1', 'kode_rumah' => '4321', 'id_rt' => self::RT]);
        $this->alamatA = (int) $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'TEST-LAYANAN/2', 'kode_rumah' => '9999', 'id_rt' => self::RT]);
        $this->alamatB = (int) $db->insertID();

        $this->wargaA = $this->seedWarga('Budi Layanan', '9100000000000001', $this->alamatA);
        $this->wargaB = $this->seedWarga('Sari Tetangga', '9100000000000002', $this->alamatB);

        $throttle = new PinThrottle();
        $throttle->reset(self::RT, $this->alamatA);
        $throttle->reset(self::RT, $this->alamatB);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function seedWarga(string $nama, string $nik, int $idAlamat): int
    {
        $db = Database::connect();
        $db->table('warga')->insert([
            'id_rt'         => self::RT,
            'id_alamat'     => $idAlamat,
            'nama_warga'    => $nama,
            'nik'           => $nik,
            'no_kk'         => $nik,
            'tempat_lahir'  => 'Sleman',
            'tanggal_lahir' => '1990-05-17',
            'agama'         => 'Islam',
            'no_hp'         => '81234567890',
            'id_pekerjaan'  => 1,
            'status_warga'  => 1,
            'is_hidup'      => 1,
        ]);

        return (int) $db->insertID();
    }

    private function sessionFor(int $idAlamat): array
    {
        return ['layanan_alamat' => ['id_alamat' => $idAlamat, 'id_rt' => self::RT, 'exp' => time() + 600]];
    }

    private function formPost(int $idWarga, array $override = []): array
    {
        return array_merge([
            'id_warga'       => $idWarga,
            'nama_pemohon'   => 'Budi Layanan',
            'nik_pemohon'    => '9100000000000001',
            'alamat_pemohon' => 'Jl. TEST-LAYANAN/1 ' . SuratPemohon::ALAMAT_SUFFIX,
            'tempat_lahir'   => 'Sleman',
            'tanggal_lahir'  => '1990-05-17',
            'agama'          => 'Islam',
            'no_hp'          => '0812-3456-7890',
            'maksut'         => 'Surat pengantar KTP',
            'perlu'          => 'Mengurus KTP',
            'lampiran'       => ['FC KK', 'FC KTP', ''],
        ], $override);
    }

    private function suratRows(int $idWarga): array
    {
        return Database::connect()->table('surat')->where('id_warga', $idWarga)->get()->getResult();
    }

    // ---- step 1: alamat + PIN -------------------------------------------

    public function testWrongPinGoesBackWithoutVerifying(): void
    {
        $response = $this->post('layanan/verifikasi', ['id_alamat' => $this->alamatA, 'pin' => '0000']);

        $response->assertRedirectTo('layanan');
    }

    public function testCorrectPinMovesToPickStep(): void
    {
        $response = $this->post('layanan/verifikasi', ['id_alamat' => $this->alamatA, 'pin' => '4321']);

        $response->assertRedirectTo('layanan/pilih');
    }

    public function testPinOfAnotherAddressIsRejected(): void
    {
        $response = $this->post('layanan/verifikasi', ['id_alamat' => $this->alamatA, 'pin' => '9999']);

        $response->assertRedirectTo('layanan');
    }

    public function testFiveWrongPinsLockTheAddressEvenForTheRightPin(): void
    {
        for ($i = 0; $i < PinThrottle::MAX_FAILURES; $i++) {
            $this->post('layanan/verifikasi', ['id_alamat' => $this->alamatA, 'pin' => 'salah' . $i])
                ->assertRedirectTo('layanan');
        }

        $response = $this->post('layanan/verifikasi', ['id_alamat' => $this->alamatA, 'pin' => '4321']);

        $response->assertRedirectTo('layanan');
    }

    public function testAddressWithoutPinCanNeverBeVerified(): void
    {
        $db = Database::connect();
        $db->table('alamat')->insert(['alamat' => 'TEST-LAYANAN/KOSONG', 'kode_rumah' => null, 'id_rt' => self::RT]);
        $idKosong = (int) $db->insertID();

        $this->post('layanan/verifikasi', ['id_alamat' => $idKosong, 'pin' => ''])->assertRedirectTo('layanan');
        $this->post('layanan/verifikasi', ['id_alamat' => $idKosong, 'pin' => '0'])->assertRedirectTo('layanan');
    }

    public function testAddressPickerListsLabelsOnlyNeverPins(): void
    {
        $response = $this->get('layanan');

        $response->assertOK();
        $response->assertSee('TEST-LAYANAN/1');
        $response->assertDontSee('4321');
        $response->assertDontSee('Budi Layanan');
    }

    // ---- step 2: pick resident ------------------------------------------

    public function testPickStepNeedsVerification(): void
    {
        $this->get('layanan/pilih')->assertRedirectTo('layanan');
    }

    public function testPickStepListsOnlyResidentsOfTheVerifiedAddress(): void
    {
        $response = $this->withSession($this->sessionFor($this->alamatA))->get('layanan/pilih');

        $response->assertOK();
        $response->assertSee('Budi Layanan');
        $response->assertDontSee('Sari Tetangga');
    }

    public function testExpiredVerificationIsRejected(): void
    {
        $session = ['layanan_alamat' => ['id_alamat' => $this->alamatA, 'id_rt' => self::RT, 'exp' => time() - 1]];

        $this->withSession($session)->get('layanan/pilih')->assertRedirectTo('layanan');
    }

    // ---- step 3: form + store -------------------------------------------

    public function testFormIsPrefilledFromWarga(): void
    {
        $response = $this->withSession($this->sessionFor($this->alamatA))->get('layanan/form/' . $this->wargaA);

        $response->assertOK();
        $response->assertSee('9100000000000001');
        $response->assertSee('081234567890');
    }

    public function testCannotOpenFormForResidentOfAnotherAddress(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->withSession($this->sessionFor($this->alamatA))->get('layanan/form/' . $this->wargaB);
    }

    public function testStoreSavesSnapshotAndFlagsOnlyTheDifferentField(): void
    {
        $post = $this->formPost($this->wargaA, ['tempat_lahir' => 'Bantul']);

        $response = $this->withSession($this->sessionFor($this->alamatA))->post('layanan/store', $post);

        $response->assertRedirectTo('layanan/sukses');

        $rows = $this->suratRows($this->wargaA);
        $this->assertCount(1, $rows);
        $this->assertSame('Bantul', $rows[0]->tempat_lahir);
        $this->assertSame('81234567890', $rows[0]->no_hp, 'phone stored normalized, no leading 0');
        $this->assertSame(['FC KK', 'FC KTP'], SuratPemohon::parseLampiran($rows[0]->lampiran));

        tenant_set_rt(self::RT);
        $detail = (new SuratModel())->detail($rows[0]->id_surat);
        $banding = SuratPemohon::bandingkan($detail);

        $this->assertFalse($banding['tempat_lahir']['sama']);
        foreach (['nama', 'nik', 'alamat', 'tanggal_lahir', 'agama', 'no_hp'] as $field) {
            $this->assertTrue($banding[$field]['sama'], $field . ' should match the RT data');
        }
        $this->assertSame(1, SuratPemohon::jumlahBeda($detail));
    }

    public function testWargaDataIsNeverOverwrittenBySubmission(): void
    {
        $post = $this->formPost($this->wargaA, ['nama_pemohon' => 'Nama Lain', 'nik_pemohon' => '1']);

        $this->withSession($this->sessionFor($this->alamatA))->post('layanan/store', $post);

        $warga = Database::connect()->table('warga')->where('id_warga', $this->wargaA)->get()->getRow();
        $this->assertSame('Budi Layanan', $warga->nama_warga);
        $this->assertSame('9100000000000001', $warga->nik);
    }

    public function testSecondRequestWhilePendingIsBlocked(): void
    {
        $session = $this->sessionFor($this->alamatA);

        $this->withSession($session)->post('layanan/store', $this->formPost($this->wargaA));
        $response = $this->withSession($session)->post('layanan/store', $this->formPost($this->wargaA));

        $response->assertRedirectTo('layanan/pilih');
        $this->assertCount(1, $this->suratRows($this->wargaA));
    }

    public function testCanRequestAgainOnceTheFirstIsDecided(): void
    {
        $session = $this->sessionFor($this->alamatA);
        $this->withSession($session)->post('layanan/store', $this->formPost($this->wargaA));

        Database::connect()->table('surat')->where('id_warga', $this->wargaA)->update(['status_surat' => SuratModel::STATUS_DITOLAK]);

        $this->withSession($session)->post('layanan/store', $this->formPost($this->wargaA))->assertRedirectTo('layanan/sukses');
        $this->assertCount(2, $this->suratRows($this->wargaA));
    }

    public function testStoreForResidentOfAnotherAddressIsRejected(): void
    {
        try {
            $this->withSession($this->sessionFor($this->alamatA))
                ->post('layanan/store', $this->formPost($this->wargaB));
            $this->fail('Expected a 404 for a resident of another address.');
        } catch (PageNotFoundException $e) {
            $this->assertCount(0, $this->suratRows($this->wargaB));
        }
    }

    public function testStoreWithoutVerificationIsRejected(): void
    {
        $response = $this->post('layanan/store', $this->formPost($this->wargaA));

        $response->assertRedirectTo('layanan');
        $this->assertCount(0, $this->suratRows($this->wargaA));
    }

    public function testStoreRequiresAtLeastOneLampiran(): void
    {
        $post = $this->formPost($this->wargaA, ['lampiran' => ['', '  ']]);

        $this->withSession($this->sessionFor($this->alamatA))->post('layanan/store', $post);

        $this->assertCount(0, $this->suratRows($this->wargaA));
    }

    // ---- admin ------------------------------------------------------------

    private function createAdmin(string $suffix, array $menuPermissions): User
    {
        $userModel = model(UserModel::class);
        $user = new User([
            'username' => 'surat_admin_' . $suffix,
            'email'    => 'surat_admin_' . $suffix . '@example.com',
            'password' => 'secret123',
        ]);
        $userModel->save($user);
        $userId = $userModel->getInsertID();

        Database::connect()->table('users')->where('id', $userId)->update(['id_rt' => self::RT]);

        $user = $userModel->findById($userId);
        $user->addGroup('admin');
        $user->syncPermissions(...$menuPermissions);

        return $user;
    }

    private function seedPending(): int
    {
        $this->withSession($this->sessionFor($this->alamatA))->post('layanan/store', $this->formPost($this->wargaA, ['tempat_lahir' => 'Bantul']));

        return (int) $this->suratRows($this->wargaA)[0]->id_surat;
    }

    public function testAdminWithoutSuratPermissionIsBlocked(): void
    {
        $admin = $this->createAdmin('noperm', ['menu.warga']);

        $response = $this->actingAs($admin)->withSession(['tenant_rt_id' => self::RT])->get('admin/surat');

        $response->assertRedirectTo('admin/warga');
    }

    public function testAdminSeesRedBadgeOnDifferingRequest(): void
    {
        $id    = $this->seedPending();
        $admin = $this->createAdmin('badge', ['menu.surat']);

        $list   = $this->actingAs($admin)->withSession(['tenant_rt_id' => self::RT])->get('admin/surat');
        $detail = $this->actingAs($admin)->withSession(['tenant_rt_id' => self::RT])->get('admin/surat/view/' . $id);

        $list->assertOK();
        $list->assertSee('1 data tidak sama dengan data RT');
        $detail->assertOK();
        $detail->assertSee('Tidak sama dengan data RT');
    }

    public function testApproveWorksOnceAndCannotBeUndoneByRejecting(): void
    {
        $id    = $this->seedPending();
        $admin = $this->createAdmin('approve', ['menu.surat']);
        $call  = fn () => $this->actingAs($admin)->withSession(['tenant_rt_id' => self::RT]);

        $call()->post('admin/surat/setuju/' . $id)->assertRedirectTo('admin/surat');
        $call()->post('admin/surat/tolak/' . $id, ['alasan_tolak' => 'terlambat'])->assertRedirectTo('admin/surat');

        $row = Database::connect()->table('surat')->where('id_surat', $id)->get()->getRow();
        $this->assertSame(SuratModel::STATUS_DISETUJUI, (int) $row->status_surat);
        $this->assertNull($row->alasan_tolak);
    }

    public function testRejectNeedsAReason(): void
    {
        $id    = $this->seedPending();
        $admin = $this->createAdmin('reject', ['menu.surat']);
        $call  = fn () => $this->actingAs($admin)->withSession(['tenant_rt_id' => self::RT]);

        $call()->post('admin/surat/tolak/' . $id, ['alasan_tolak' => '  '])->assertRedirectTo('admin/surat/view/' . $id);
        $this->assertSame(SuratModel::STATUS_MENUNGGU, (int) Database::connect()->table('surat')->where('id_surat', $id)->get()->getRow()->status_surat);

        $call()->post('admin/surat/tolak/' . $id, ['alasan_tolak' => 'Berkas kurang'])->assertRedirectTo('admin/surat');
        $row = Database::connect()->table('surat')->where('id_surat', $id)->get()->getRow();
        $this->assertSame(SuratModel::STATUS_DITOLAK, (int) $row->status_surat);
        $this->assertSame('Berkas kurang', $row->alasan_tolak);
    }
}
