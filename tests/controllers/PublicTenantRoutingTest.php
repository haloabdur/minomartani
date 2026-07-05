<?php

use App\Libraries\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class PublicTenantRoutingTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = null;
    private int $rtA; // rt29 (id = 1)
    private int $rtB; // rt30 (new)
    private int $idRw;

    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        helper('subdomain');
        TenantContext::reset();

        $db = Database::connect();
        $this->rtA = 1;
        $this->idRw = (int) $db->table('rw')->where('slug', 'rw-minomartani')->get()->getRow()->id_rw;

        // Seed a second RT next to the default (1)
        if ($db->table('rt')->where('slug', 'rt30-test')->countAllResults() === 0) {
            $db->table('rt')->insert(['id_rw' => $this->idRw, 'nama' => 'RT 30 Test', 'slug' => 'rt30-test', 'is_aktif' => 1]);
        }
        $this->rtB = (int) $db->table('rt')->where('slug', 'rt30-test')->get()->getRow()->id_rt;

        // Clean table first to avoid spillover from other tests
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        $db->table('warga')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS=1');

        // Seed warga for RT A
        $db->table('warga')->insert([
            'id_rt' => $this->rtA,
            'nama_warga' => 'Warga RT 29 A',
            'nik' => '1111111111111111',
            'no_kk' => '1111111111111112',
            'jenis_kelamin' => 'L',
            'status_warga' => 1,
        ]);
        $db->table('warga')->insert([
            'id_rt' => $this->rtA,
            'nama_warga' => 'Warga RT 29 B',
            'nik' => '2222222222222222',
            'no_kk' => '1111111111111112',
            'jenis_kelamin' => 'P',
            'status_warga' => 1,
        ]);

        // Seed warga for RT B
        $db->table('warga')->insert([
            'id_rt' => $this->rtB,
            'nama_warga' => 'Warga RT 30 A',
            'nik' => '3333333333333333',
            'no_kk' => '3333333333333334',
            'jenis_kelamin' => 'L',
            'status_warga' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function testDefaultRouteResolvesToRt29(): void
    {
        $result = $this->controller(\App\Controllers\Home::class)
            ->execute('index');

        $this->assertSame(200, $result->response()->getStatusCode());
        // Verify RT A data counts are displayed
        $this->assertStringContainsString('<h3>1</h3><span class="align-self-center ms-2">KK</span>', $result->response()->getBody());
        $this->assertStringContainsString('<h3>1</h3><span class="align-self-center ms-2">Pria</span>', $result->response()->getBody());
        $this->assertStringContainsString('<h3>1</h3><span class="align-self-center ms-2">Wanita</span>', $result->response()->getBody());
    }

    public function testSluggedRouteResolvesToRt30(): void
    {
        $result = $this->controller(\App\Controllers\Home::class)
            ->execute('index', 'rt30-test');

        $this->assertSame(200, $result->response()->getStatusCode());
        // Verify RT B data counts are displayed
        $this->assertStringContainsString('<h3>1</h3><span class="align-self-center ms-2">KK</span>', $result->response()->getBody());
        $this->assertStringContainsString('<h3>1</h3><span class="align-self-center ms-2">Pria</span>', $result->response()->getBody());
        $this->assertStringContainsString('<h3>0</h3><span class="align-self-center ms-2">Wanita</span>', $result->response()->getBody()); // 0 Perempuan in RT 30
    }

    public function testRtHostResolvesByHostInsteadOfSlug(): void
    {
        $db = Database::connect();
        $db->table('rt')->where('id_rt', $this->rtB)->update(['subdomain' => 'rt30host-test']);

        $this->request->setHeader('Host', 'rt30host-test.minomartani.com');

        $result = $this->controller(\App\Controllers\Home::class)
            ->execute('index');

        $this->assertSame(200, $result->response()->getStatusCode());
        // Verify RT B data counts are displayed (host wins over empty slug)
        $this->assertStringContainsString('<h3>0</h3><span class="align-self-center ms-2">Wanita</span>', $result->response()->getBody());
    }

    public function testRwHostDispatchesToRwLandingPage(): void
    {
        $db = Database::connect();
        $db->table('rw')->where('id_rw', $this->idRw)->update(['subdomain' => 'rw-test-host']);

        $this->request->setHeader('Host', 'rw-test-host.minomartani.com');

        $result = $this->controller(\App\Controllers\Home::class)
            ->execute('index');

        $this->assertSame(200, $result->response()->getStatusCode());
        $this->assertStringContainsString('RT 30 Test', $result->response()->getBody());
    }

    public function testRwHostReturns404OnAlamatRoute(): void
    {
        $db = Database::connect();
        $db->table('rw')->where('id_rw', $this->idRw)->update(['subdomain' => 'rw-test-host2']);

        $this->request->setHeader('Host', 'rw-test-host2.minomartani.com');

        $result = $this->controller(\App\Controllers\Home::class)
            ->execute('alamat', 'rt29', 'some-kode');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
