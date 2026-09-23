<?php

namespace App\Libraries;

use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;
use Config\R2;

class R2Storage
{
    protected S3Client $client;
    protected R2 $config;

    public function __construct(?R2 $config = null)
    {
        $this->config = $config ?? config('R2');

        $this->client = new S3Client([
            'endpoint'        => "https://{$this->config->accountId}.r2.cloudflarestorage.com",
            'accessKeyId'     => $this->config->accessKeyId,
            'accessKeySecret' => $this->config->secretAccessKey,
            'region'          => 'auto',
        ]);
    }

    /**
     * Uploads a file to R2 and returns the full public URL to store in the DB.
     * Throws on any failure - callers decide the fallback behavior.
     */
    public function upload(\CodeIgniter\HTTP\Files\UploadedFile $file, string $prefix): string
    {
        $key = $prefix . '/item-' . date('ymd') . '-' . substr(md5(random_int(0, PHP_INT_MAX)), 0, 10) . '.' . $file->getExtension();

        $this->client->putObject(new PutObjectRequest([
            'Bucket'      => $this->config->bucket,
            'Key'         => $key,
            'Body'        => file_get_contents($file->getTempName()),
            'ContentType' => $file->getMimeType(),
        ]));

        return $this->config->publicUrl . '/' . $key;
    }

    /**
     * Deletes an R2 object given its stored public URL. No-op if the value
     * doesn't match this bucket's public URL prefix (e.g. a legacy local
     * path or filename) - safe to call unconditionally on any foto value.
     */
    public function delete(string $storedValue): void
    {
        if ($this->config->publicUrl === '' || !str_starts_with($storedValue, $this->config->publicUrl . '/')) {
            return;
        }

        $key = substr($storedValue, strlen($this->config->publicUrl) + 1);

        $this->client->deleteObject(new DeleteObjectRequest([
            'Bucket' => $this->config->bucket,
            'Key'    => $key,
        ]));
    }
}
