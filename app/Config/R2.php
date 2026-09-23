<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class R2 extends BaseConfig
{
    public string $accountId;
    public string $accessKeyId;
    public string $secretAccessKey;
    public string $bucket;
    public string $publicUrl;

    public function __construct()
    {
        parent::__construct();

        $this->accountId       = (string) env('r2.accountId', '');
        $this->accessKeyId     = (string) env('r2.accessKeyId', '');
        $this->secretAccessKey = (string) env('r2.secretAccessKey', '');
        $this->bucket          = (string) env('r2.bucket', '');
        $this->publicUrl       = rtrim((string) env('r2.publicUrl', ''), '/');
    }
}
