<?php

use App\Libraries\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TenantHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('tenant');
        TenantContext::reset();
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function testCurrentRtIdIsNullWithoutContext(): void
    {
        $this->assertNull(current_rt_id());
    }

    public function testRequestOverrideWins(): void
    {
        tenant_set_rt(7);
        $this->assertSame(7, current_rt_id());

        tenant_set_rt(null);
        $this->assertNull(current_rt_id());
    }
}
