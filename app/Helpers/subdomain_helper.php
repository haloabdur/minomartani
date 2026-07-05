<?php

use App\Models\RtModel;
use App\Models\RwModel;
use CodeIgniter\HTTP\RequestInterface;

/**
 * Subdomain-based tenant resolution. Reads the raw Host header, not
 * $request->getUri()->getHost(): SiteURI only trusts a hostname that is
 * an exact entry in Config\App::$allowedHostnames (see
 * SiteURIFactory::getValidHost()), which can't track subdomains added
 * at runtime through Admin\Tenants, and it never reflects a custom host
 * in HTTP feature tests either (FeatureTestTrait::setupRequest() never
 * passes $host into `new SiteURI()`). The raw Host header IS honoured
 * by withHeaders(['Host' => ...]) / setHeader('Host', ...) in tests.
 */

if (! function_exists('request_host')) {
    function request_host(RequestInterface $request): string
    {
        $host = $request->getHeaderLine('Host');

        if ($host === '') {
            $host = (string) ($request->getServer('HTTP_HOST') ?? $request->getUri()->getHost());
        }

        return strtolower(trim(explode(':', $host, 2)[0]));
    }
}

if (! function_exists('subdomain_label')) {
    /**
     * Leftmost DNS label of $host, or null if $host has no meaningful
     * subdomain position (bare hostname, IP literal, empty). Doesn't
     * need to know the apex domain: an unrecognised label just fails
     * the DB lookup below and falls back to legacy behaviour, so no
     * TLD/apex parsing is needed (Config\Hostnames::TWO_PART_TLDS stays
     * unused dead scaffold code — consider deleting it separately).
     */
    function subdomain_label(string $host): ?string
    {
        $host = strtolower(trim($host));

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        $parts = explode('.', $host);

        if (count($parts) < 2 || $parts[0] === '') {
            return null;
        }

        return $parts[0];
    }
}

if (! function_exists('resolve_tenant_by_host')) {
    /**
     * @return array{type: 'rt', rt: object}|array{type: 'rw', rw: object}|null
     */
    function resolve_tenant_by_host(string $host): ?array
    {
        $label = subdomain_label($host);

        if ($label === null) {
            return null;
        }

        if (($rt = model(RtModel::class)->bySubdomain($label)) !== null) {
            return ['type' => 'rt', 'rt' => $rt];
        }

        if (($rw = model(RwModel::class)->bySubdomain($label)) !== null) {
            return ['type' => 'rw', 'rw' => $rw];
        }

        return null;
    }
}
