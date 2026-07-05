<?php

namespace Tests\Database;

use App\Models\RtModel;
use App\Models\RwModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class SubdomainResolutionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    public function testResolveTenantByHostMatchesRtSubdomain(): void
    {
        $this->seedFromMigration();

        $result = resolve_tenant_by_host('rt29.minomartani.com');

        $this->assertNotNull($result);
        $this->assertSame('rt', $result['type']);
        $this->assertSame('1', (string) $result['rt']->id_rt);
        $this->assertSame('rt29', $result['rt']->subdomain);
    }

    public function testResolveTenantByHostMatchesRwSubdomain(): void
    {
        $this->seedFromMigration();

        $rwModel = model(RwModel::class);

        $rw = $rwModel->first();
        $rw->subdomain = 'rw-test';
        $rwModel->save($rw);

        $result = resolve_tenant_by_host('rw-test.minomartani.com');

        $this->assertNotNull($result);
        $this->assertSame('rw', $result['type']);
        $this->assertSame($rw->id_rw, $result['rw']->id_rw);
    }

    public function testResolveTenantByHostReturnsNullForUnmatchedHost(): void
    {
        $this->seedFromMigration();

        $result = resolve_tenant_by_host('unknown.minomartani.com');
        $this->assertNull($result);

        $result = resolve_tenant_by_host('example.com');
        $this->assertNull($result);

        $result = resolve_tenant_by_host('127.0.0.1');
        $this->assertNull($result);

        $result = resolve_tenant_by_host('localhost');
        $this->assertNull($result);
    }

    public function testResolveTenantRtWinsOverRwOnLabelCollision(): void
    {
        $this->seedFromMigration();

        $rwModel = model(RwModel::class);

        $rw = $rwModel->first();
        $rw->subdomain = 'rt29';
        $rwModel->save($rw);

        $result = resolve_tenant_by_host('rt29.minomartani.com');

        $this->assertNotNull($result);
        $this->assertSame('rt', $result['type']);
        $this->assertSame('1', (string) $result['rt']->id_rt);
    }

    public function testResolveTenantIgnoresInactiveRt(): void
    {
        $this->seedFromMigration();

        $rtModel = model(RtModel::class);
        $rt = $rtModel->find(1);
        $rt->is_aktif = 0;
        $rtModel->save($rt);

        $result = resolve_tenant_by_host('rt29.minomartani.com');
        $this->assertNull($result);
    }

    public function testResolveTenantIgnoresInactiveRw(): void
    {
        $this->seedFromMigration();

        $rwModel = model(RwModel::class);
        $rw = $rwModel->first();
        $rw->subdomain = 'rw-test';
        $rw->is_aktif = 0;
        $rwModel->save($rw);

        $result = resolve_tenant_by_host('rw-test.minomartani.com');
        $this->assertNull($result);
    }

    private function seedFromMigration(): void
    {
        // Migrations create the rw and rt tables with seed data:
        // rw (id_rw=1, slug='rw-minomartani', is_aktif=1)
        // rt (id_rt=1, id_rw=1, slug='rt29', subdomain='rt29', is_aktif=1)
    }
}
