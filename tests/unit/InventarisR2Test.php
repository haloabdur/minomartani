<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthGroups;

/**
 * Tests for Inventaris R2 photo helpers, configuration, and menu permissions.
 *
 * @internal
 */
final class InventarisR2Test extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['kbw', 'menu']);
    }

    public function testFotoUrlHandlesEmptyPath(): void
    {
        $this->assertSame('', foto_url(null));
        $this->assertSame('', foto_url(''));
    }

    public function testFotoUrlPassesThroughFullHttpUrls(): void
    {
        $r2Url = 'https://cdn.minomartani.com/inventaris/rt29/item-261008-abc1234567.webp';
        $this->assertSame($r2Url, foto_url($r2Url, 'inventaris'));

        $httpUrl = 'http://example.com/photo.jpg';
        $this->assertSame($httpUrl, foto_url($httpUrl));
    }

    public function testFotoUrlHandlesLegacyPublicPrefix(): void
    {
        $legacyPath = 'public/inventaris/item-260307-a1b2c3d4e5.jpg';
        $expected = base_url('public/inventaris/item-260307-a1b2c3d4e5.jpg');
        $this->assertSame($expected, foto_url($legacyPath, 'inventaris'));
    }

    public function testFotoUrlHandlesBareFilenames(): void
    {
        $bareFile = 'item-261008-xyz.webp';
        $expected = base_url('public/inventaris/item-261008-xyz.webp');
        $this->assertSame($expected, foto_url($bareFile, 'inventaris'));
    }

    public function testAuthGroupsDefinesInventarisMenuPermission(): void
    {
        $config = new AuthGroups();
        $this->assertArrayHasKey('menu.inventaris', $config->permissions);
    }

    public function testUsersControllerDefinesInventarisMenuPermission(): void
    {
        $reflection = new ReflectionClass(\App\Controllers\Admin\Users::class);
        $adminMenuPermissions = $reflection->getConstant('ADMIN_MENU_PERMISSIONS');

        $this->assertIsArray($adminMenuPermissions);
        $this->assertArrayHasKey('menu.inventaris', $adminMenuPermissions);
        $this->assertSame('Inventaris RT', $adminMenuPermissions['menu.inventaris']);
    }
}
