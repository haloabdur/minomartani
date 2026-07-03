<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Turnstile extends BaseConfig
{
    public bool $enabled;
    public string $siteKey;
    public string $secretKey;

    public function __construct()
    {
        parent::__construct();

        $this->siteKey   = (string) env('turnstile.siteKey', '');
        $this->secretKey = (string) env('turnstile.secretKey', '');

        // Off by default outside production so local/dev environments
        // don't need real Cloudflare keys to log in. Set turnstile.enabled
        // explicitly in .env to override either way.
        $envEnabled     = env('turnstile.enabled');
        $this->enabled  = $envEnabled === null
            ? (ENVIRONMENT === 'production')
            : filter_var($envEnabled, FILTER_VALIDATE_BOOLEAN);
    }
}
