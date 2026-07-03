<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Turnstile;

/**
 * Regression guard for app/Config/Turnstile.php: confirms Turnstile is
 * off by default outside production (so dev doesn't need real Cloudflare
 * keys to log in), and that turnstile.enabled in .env can force it either
 * way.
 *
 * @internal
 */
final class TurnstileConfigTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_ENV['turnstile.enabled'], $_SERVER['turnstile.enabled']);
        putenv('turnstile.enabled');
    }

    public function testDisabledByDefaultOutsideProduction(): void
    {
        $this->assertNotSame('production', ENVIRONMENT, 'this test assumes it does not run under CI_ENVIRONMENT=production');

        $config = new Turnstile();

        $this->assertFalse($config->enabled);
    }

    public function testEnvOverrideCanForceItOnOutsideProduction(): void
    {
        putenv('turnstile.enabled=true');
        $_ENV['turnstile.enabled'] = 'true';

        $config = new Turnstile();

        $this->assertTrue($config->enabled);
    }

    public function testEnvOverrideCanForceItOffEvenInProduction(): void
    {
        putenv('turnstile.enabled=false');
        $_ENV['turnstile.enabled'] = 'false';

        $config = new Turnstile();

        $this->assertFalse($config->enabled);
    }
}
