# Inventaris photo uploads → Cloudflare R2 storage

## Context

`app/Controllers/Admin/Inventaris.php` currently saves uploaded `foto` files to local disk (`FCPATH . 'public/inventaris'`) and stores a relative path (`public/inventaris/item-xxx.jpg`) in the `foto` column. Views render it with `base_url($item->foto)`.

This moves new uploads to Cloudflare R2 (S3-compatible object storage) instead of local disk. The user already has an R2 bucket and API credentials.

## Decisions

- **Serving**: R2 bucket has public access enabled (R2.dev subdomain or custom domain). The app stores the full public URL in the `foto` column and renders it directly — no presigning, no proxy route.
- **Scope**: Build a reusable storage service, but only wire it into Inventaris for now. Other modules (Warga, Berita, etc.) can adopt it later.
- **Existing data**: Rows with a local `public/inventaris/...` path are left untouched — no migration/backfill. Only new uploads and edits go to R2. Display logic must handle both forms.
- **Client library**: `async-aws/s3` (lightweight S3-compatible client) rather than the full `aws/aws-sdk-php`.

## Design

### Config: `app/Config/R2.php`

Follows the existing `Config/Turnstile.php` pattern (env-backed `BaseConfig` subclass):

```php
class R2 extends BaseConfig
{
    public string $accountId;
    public string $accessKeyId;
    public string $secretAccessKey;
    public string $bucket;
    public string $publicUrl; // e.g. https://pub-xxxx.r2.dev or a custom domain, no trailing slash
}
```

Env vars (added to `.env` and `.env.production.example`):
```
r2.accountId = ...
r2.accessKeyId = ...
r2.secretAccessKey = ...
r2.bucket = ...
r2.publicUrl = https://...
```

### Service: `app/Libraries/R2Storage.php`

Reusable upload/delete wrapper around `AsyncAws\S3\S3Client`, constructed with:
- `endpoint`: `https://<accountId>.r2.cloudflarestorage.com`
- `accessKeyId` / `accessKeySecret` from config
- `region`: `auto` (R2 requirement)

Public API:
- `upload(UploadedFile $file, string $prefix): string`
  Generates a unique object key `<prefix>/item-<ymd>-<10 char random hex>.<ext>` (same naming convention as the current local implementation), issues a `PutObject` call, returns the full public URL (`config->publicUrl . '/' . $key`) for storage in the DB.
- `delete(string $storedValue): void`
  If `$storedValue` starts with the configured `publicUrl`, strips that prefix to recover the object key and issues a `DeleteObject` call. If it doesn't match (e.g. it's a legacy local path like `public/inventaris/...`), this is a no-op — legacy local files are never touched by this class.

### Controller changes: `app/Controllers/Admin/Inventaris.php`

- Add `protected $r2Storage;` initialized in `__construct()`.
- `store()`: replace the local `move()`/`mkdir` block with:
  ```php
  if ($foto && $foto->isValid() && !$foto->hasMoved()) {
      $data['foto'] = $this->r2Storage->upload($foto, 'inventaris');
  }
  ```
- `update()`: same upload swap. Replace the old-photo-deletion block:
  ```php
  $oldItem = $this->inventarisModel->detail($id);
  if (!empty($oldItem->foto)) {
      $this->r2Storage->delete($oldItem->foto);
  }
  ```
  (delete is safe to call unconditionally on any non-empty value; it self-guards against non-R2 paths)
- `delete()`: replace `unlink()` block with `$this->r2Storage->delete($item->foto)`, same self-guard behavior.
- Remove the now-unused `FCPATH`/`is_dir`/`mkdir`/`file_exists`/`unlink` local-disk logic entirely.

### View rendering: mixed old/new paths

New helper function `foto_url(string $path): string` added to `app/Helpers/kbw_helper.php`:
```php
function foto_url(string $path): string
{
    return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
        ? $path
        : base_url($path);
}
```
`inventaris.php` and `ubah_inventaris.php` views swap `base_url($item->foto)` → `foto_url($item->foto)`. `kbw_helper.php` is already autoloaded globally, so no additional wiring needed.

### Dependency

`composer require async-aws/s3`

## Out of scope

- Migrating existing local `public/inventaris/*` files to R2.
- Adopting this storage service in any other module (Warga, Berita, Alamat QR, etc.) — the service is written generically enough to support that later, but no other controller is touched in this change.
- Presigned/private-bucket access.
