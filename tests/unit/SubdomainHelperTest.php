<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

final class SubdomainHelperTest extends CIUnitTestCase
{
    public function testSubdomainLabelExtractsLeftmostLabel(): void
    {
        $this->assertSame('rt29', subdomain_label('rt29.minomartani.com'));
        $this->assertSame('rw06', subdomain_label('rw06.minomartani.com'));
        $this->assertSame('test', subdomain_label('test.example.co.uk'));
    }

    public function testSubdomainLabelReturnsNullForBareHostname(): void
    {
        $this->assertNull(subdomain_label('localhost'));
    }

    public function testSubdomainLabelReturnsLeftmostLabelEvenForApexDomain(): void
    {
        // The function doesn't validate whether it's a "real" subdomain or the apex —
        // it just returns the leftmost label. Apex domains like example.com → example.
        // The DB lookup will fail to match and fall back to legacy behavior.
        $this->assertSame('example', subdomain_label('example.com'));
        $this->assertSame('minomartani', subdomain_label('minomartani.com'));
    }

    public function testSubdomainLabelReturnsNullForIpLiteral(): void
    {
        $this->assertNull(subdomain_label('127.0.0.1'));
        $this->assertNull(subdomain_label('192.168.1.1'));
        $this->assertNull(subdomain_label('::1'));
    }

    public function testSubdomainLabelReturnsNullForEmptyString(): void
    {
        $this->assertNull(subdomain_label(''));
        $this->assertNull(subdomain_label('   '));
    }

    public function testSubdomainLabelReturnsNullForLeadingDot(): void
    {
        $this->assertNull(subdomain_label('.example.com'));
    }

    public function testSubdomainLabelIsCaseInsensitive(): void
    {
        $this->assertSame('rt29', subdomain_label('RT29.MINOMARTANI.COM'));
        $this->assertSame('test', subdomain_label('TEST.example.COM'));
    }
}
