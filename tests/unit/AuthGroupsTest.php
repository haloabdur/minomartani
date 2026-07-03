<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthGroups;

/**
 * Guard for the tenant-related Shield group config.
 *
 * @internal
 */
final class AuthGroupsTest extends CIUnitTestCase
{
    public function testRwGroupIsDefined(): void
    {
        $config = new AuthGroups();

        $this->assertArrayHasKey('rw', $config->groups, "the 'rw' Shield group is missing");
        $this->assertArrayHasKey('rw', $config->matrix, "the 'rw' group has no permissions matrix entry");
        $this->assertSame([], $config->matrix['rw'], 'rw accounts are read-only: no admin permissions');
    }
}
