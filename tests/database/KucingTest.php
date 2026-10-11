<?php

use App\Libraries\TenantContext;
use App\Models\KucingModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Pendataan kucing: tenant isolation on every KucingModel query, and the
 * public listing never exposes the owner's name.
 *
 * @internal
 */
final class KucingTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    private int $rtB;
    private int $wargaA;
    private int $wargaB;
    private int $kucingA;
    private int $kucingB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        $this->seedKucing();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function seedKucing(): void
    {
        $db = Database::connect();

        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test']);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;

        $db->table('alamat')->insert(['alamat' => 'Mawar/12', 'id_rt' => 1]);
        $idAlamatA = $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'Melati/3', 'id_rt' => $this->rtB]);
        $idAlamatB = $db->insertID();

        $warga = static fn (string $nama, string $nik, int $idAlamat, int $idRt) => [
            'no_kk' => $nik, 'nama_warga' => $nama, 'nik' => $nik,
            'jenis_kelamin' => 'L', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
            'id_pekerjaan' => 1, 'id_alamat' => $idAlamat, 'id_rt' => $idRt,
        ];
        $db->table('warga')->insert($warga('Pemilik A', '3333333333333331', $idAlamatA, 1));
        $this->wargaA = $db->insertID();
        $db->table('warga')->insert($warga('Pemilik B', '3333333333333332', $idAlamatB, $this->rtB));
        $this->wargaB = $db->insertID();

        $db->table('kucing')->insert(['id_rt' => 1, 'id_warga' => $this->wargaA, 'nama' => 'Mimi']);
        $this->kucingA = $db->insertID();
        $db->table('kucing')->insert(['id_rt' => $this->rtB, 'id_warga' => $this->wargaB, 'nama' => 'Tom']);
        $this->kucingB = $db->insertID();
    }

    public function testAllOnlyReturnsCurrentTenantCats(): void
    {
        tenant_set_rt(1);

        $names = array_column((new KucingModel())->all(), 'nama');

        $this->assertContains('Mimi', $names);
        $this->assertNotContains('Tom', $names);
    }

    public function testDetailAndHapusIgnoreOtherTenantsCats(): void
    {
        tenant_set_rt(1);
        $model = new KucingModel();

        $this->assertNull($model->detail($this->kucingB));

        $model->hapus($this->kucingB);
        $this->assertSame(1, Database::connect()->table('kucing')->where('id_kucing', $this->kucingB)->countAllResults());

        $model->hapus($this->kucingA);
        $this->assertSame(0, Database::connect()->table('kucing')->where('id_kucing', $this->kucingA)->countAllResults());
    }

    public function testPemilikValidRejectsWargaFromAnotherTenant(): void
    {
        tenant_set_rt(1);
        $model = new KucingModel();

        $this->assertTrue($model->pemilikValid($this->wargaA));
        $this->assertFalse($model->pemilikValid($this->wargaB));
    }

    public function testPublicListingHidesOwnerNameAndShowsOnlyStreet(): void
    {
        tenant_set_rt(1);

        $rows = (new KucingModel())->publik();

        $this->assertCount(1, $rows);
        $this->assertSame('Mimi', $rows[0]->nama);
        $this->assertSame('Mawar', $rows[0]->jalan);
        $this->assertObjectNotHasProperty('nama_warga', $rows[0]);
        $this->assertObjectNotHasProperty('id_warga', $rows[0]);
    }

    public function testJumlahCountsOnlyCurrentTenantAndIsZeroForEmptyTenant(): void
    {
        tenant_set_rt(1);
        $this->assertSame(1, (new KucingModel())->jumlah());

        Database::connect()->table('kucing')->where('id_rt', $this->rtB)->delete();
        tenant_set_rt($this->rtB);
        $this->assertSame(0, (new KucingModel())->jumlah());
    }

    public function testPublicListingSearchFiltersByName(): void
    {
        tenant_set_rt(1);

        $this->assertCount(0, (new KucingModel())->publik('zzz'));
        $this->assertCount(1, (new KucingModel())->publik('mim'));
    }
}
