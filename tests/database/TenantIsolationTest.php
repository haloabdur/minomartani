<?php

use App\Libraries\TenantContext;
use App\Models\AlamatModel;
use App\Models\BeritaModel;
use App\Models\WargaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * The core multi-tenancy guarantee: a query executed in tenant A's
 * context never returns tenant B's rows.
 *
 * @internal
 */
final class TenantIsolationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    private int $rtB;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
        $this->seedTwoTenants();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    private function seedTwoTenants(): void
    {
        $db = Database::connect();

        // Second tenant next to the seeded RT 29 (id_rt = 1).
        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $idRw = $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;
            $db->table('rt')->insert(['id_rw' => $idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test']);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;

        $db->table('berita')->insertBatch([
            ['judul' => 'Berita A', 'slug' => 'berita-a', 'deskripsi' => 'x', 'is_status' => 1, 'id_rt' => 1],
            ['judul' => 'Berita B', 'slug' => 'berita-b', 'deskripsi' => 'x', 'is_status' => 1, 'id_rt' => $this->rtB],
        ]);

        // id_alamat is set on both warga rows so the nik() lookup's
        // INNER JOIN to alamat cannot mask a missing id_rt filter.
        $db->table('alamat')->insert(['alamat' => 'Jalan A1', 'id_rt' => 1]);
        $idAlamatA = $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'Jalan B1', 'id_rt' => $this->rtB]);
        $idAlamatB = $db->insertID();
        $db->table('alamat')->insert(['alamat' => 'Jalan B2', 'id_rt' => $this->rtB]);

        $db->table('warga')->insertBatch([
            [
                'no_kk' => '111', 'nama_warga' => 'Warga A', 'nik' => '1111111111111111',
                'jenis_kelamin' => 'L', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
                'id_pekerjaan' => 1, 'id_alamat' => $idAlamatA, 'id_rt' => 1,
            ],
            [
                'no_kk' => '222', 'nama_warga' => 'Warga B', 'nik' => '2222222222222222',
                'jenis_kelamin' => 'P', 'tempat_lahir' => 'Sleman', 'tanggal_lahir' => '1990-01-01',
                'id_pekerjaan' => 1, 'id_alamat' => $idAlamatB, 'id_rt' => $this->rtB,
            ],
        ]);
    }

    public function testBeritaIsIsolatedPerTenant(): void
    {
        tenant_set_rt(1);
        $judul = array_column((array) (new BeritaModel())->all(), 'judul');
        $this->assertContains('Berita A', $judul);
        $this->assertNotContains('Berita B', $judul, 'tenant A sees tenant B berita!');

        tenant_set_rt($this->rtB);
        $judul = array_column((array) (new BeritaModel())->all(), 'judul');
        $this->assertContains('Berita B', $judul);
        $this->assertNotContains('Berita A', $judul, 'tenant B sees tenant A berita!');
    }

    public function testWargaCountsAreIsolatedPerTenant(): void
    {
        $model = new WargaModel();

        tenant_set_rt($this->rtB);
        $this->assertSame(1, $model->count());
        $this->assertSame(0, $model->laki_count(), 'RT B has no male warga');
        $this->assertSame(1, $model->perempuan_count());
    }

    public function testAlamatCountIsIsolatedPerTenant(): void
    {
        $model = new AlamatModel();

        tenant_set_rt($this->rtB);
        $this->assertSame(2, $model->alamat_count());
    }

    public function testNikLookupIsIsolatedPerTenant(): void
    {
        // Layanan (public) verifies warga by NIK - must not leak
        // across tenants.
        tenant_set_rt(1);
        $this->assertNull((new WargaModel())->nik('2222222222222222'), 'NIK lookup crossed tenants');
    }

    public function testRekapOnlyCountsRtsOfTheGivenRw(): void
    {
        $db = Database::connect();

        // A second RW with its own RT and one warga.
        if ($db->table('rw')->where('slug', 'rw-lain')->countAllResults() === 0) {
            $db->table('rw')->insert(['nama' => 'RW Lain', 'slug' => 'rw-lain']);
        }
        $idRwLain = (int) $db->table('rw')->where('slug', 'rw-lain')->get()->getRow()->id_rw;

        if ($db->table('rt')->where('slug', 'rt99-test')->countAllResults() === 0) {
            $db->table('rt')->insert(['id_rw' => $idRwLain, 'nama' => 'RT 99 Test', 'slug' => 'rt99-test']);
        }

        $idRwUtama = (int) $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        $rekap = (new \App\Models\RtModel())->rekap($idRwUtama);
        $slugs = array_column($rekap, 'slug');

        $this->assertContains('rt29', $slugs);
        $this->assertContains('rt30-test', $slugs);
        $this->assertNotContains('rt99-test', $slugs, 'rekap leaked an RT from another RW');

        $rekapAll = (new \App\Models\RtModel())->rekap(null);
        $this->assertContains('rt99-test', array_column($rekapAll, 'slug'), 'superadmin rekap must include all RTs');
    }
}
