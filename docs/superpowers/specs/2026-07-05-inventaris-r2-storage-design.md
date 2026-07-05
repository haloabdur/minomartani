# Inventaris & Berita photo uploads → Cloudflare R2 storage

## Context

`app/Controllers/Admin/Inventaris.php` and `app/Controllers/Admin/Berita.php` currently save uploaded photos to local disk and store a local reference in the DB:
- Inventaris `foto` column: relative path incl. directory, e.g. `public/inventaris/item-xxx.jpg`. Rendered with `base_url($item->foto)`.
- Berita `foto` column: bare filename only, e.g. `abc123.jpg` (no directory). Rendered in 3 places with `base_url('public/berita/' . $berita->foto)`.

This moves new uploads in both controllers to Cloudflare R2 (S3-compatible object storage) instead of local disk. The user already has an R2 bucket and API credentials (real values supplied directly, stored only in the gitignored `.env`, never committed).

## Decisions

- **Serving**: R2 bucket is public via a custom domain (`https://cdn.minomartani.com`, mapped to the `mino` bucket). The app stores the full public URL in the `foto` column and renders it directly — no presigning, no proxy route.
- **Scope**: Build a reusable storage service (`R2Storage`), wired into both Inventaris and Berita's store/update (and Inventaris's delete — Berita has no delete action). Other modules can adopt it later the same way.
- **Existing data**: Rows with a legacy local reference (either form above) are left untouched — no migration/backfill. Only new uploads and edits go to R2. Display logic must handle both the old and new forms, and Inventaris vs. Berita's differing legacy formats.
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

Env vars — real values go in the gitignored `.env` only; `.env.production.example` gets placeholders:
```
r2.accountId = a71d163dfae4657a360cc319c42fe838
r2.accessKeyId = 628c961a8f26e9f2b4c0bda6003ef0bd
r2.secretAccessKey = f5b630f3e8f7c517e6a99f1f07e7ac21476c704084253ac59f020fc008d5b019
r2.bucket = mino
r2.publicUrl = https://cdn.minomartani.com
```
(The supplied `R2_TOKEN` is an R2 API token, not needed by the S3-compatible upload/delete flow this design uses — not stored.)

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

### View rendering: mixed old/new paths, per-module legacy prefix

New helper function in `app/Helpers/kbw_helper.php`:
```php
function foto_url(string $path, string $localPrefix = ''): string
{
    return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
        ? $path
        : base_url($localPrefix . $path);
}
```
- Inventaris views (`inventaris.php`, `ubah_inventaris.php`): legacy values already include their directory (`public/inventaris/...`), so call `foto_url($item->foto)` with no prefix.
- Berita views (`berita.php`, `ubah_berita.php`, `berita_detail.php`): legacy values are bare filenames, so call `foto_url($berita->foto, 'public/berita/')` to reproduce today's `base_url('public/berita/' . $berita->foto)` behavior for old rows, while new rows (full R2 URLs) pass through unchanged.

`kbw_helper.php` is already autoloaded globally, so no additional wiring needed.

### Berita controller changes: `app/Controllers/Admin/Berita.php`

Same shape as Inventaris:
- Add `protected $r2Storage;` initialized in `__construct()`.
- `store()`: replace `$foto->move(FCPATH . 'public/berita', $newName); $data['foto'] = $newName;` with `$data['foto'] = $this->r2Storage->upload($foto, 'berita');`.
- `update()`: same upload swap. Replace the old-photo deletion block (which currently reads `foto_old` from POST and `unlink()`s it) with `$this->r2Storage->delete($this->request->getPost('foto_old'));` — safe no-op if that value isn't an R2 URL (i.e. still a bare legacy filename, which we intentionally don't touch).
- Berita has no `delete()` action today — none added.
- `ubah_berita.php`'s hidden `foto_old` field keeps working unchanged: it echoes whatever is currently in `$berita->foto` (old bare filename or new full URL), and `R2Storage::delete()` only acts on the latter.

### Dependency

`composer require async-aws/s3`

## Out of scope

- Migrating existing local `public/inventaris/*` or `public/berita/*` files to R2.
- Adopting this storage service in any other module (Warga, Alamat QR, etc.) — the service is written generically enough to support that later, but no other controller is touched in this change.
- Presigned/private-bucket access.
