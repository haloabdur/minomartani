# Inventaris & Berita R2 Storage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Inventaris and Berita photo uploads from local disk to Cloudflare R2 (S3-compatible object storage), serving photos from a public custom domain.

**Architecture:** A single reusable `App\Libraries\R2Storage` class wraps `AsyncAws\S3\S3Client`, exposing `upload()`/`delete()`. `Inventaris` and `Berita` controllers call it instead of `move()`/`unlink()`. Views use a new `foto_url()` helper so rows with a legacy local reference still render correctly alongside new rows storing a full R2 URL.

**Tech Stack:** CodeIgniter 4, `async-aws/s3` (PHP), Cloudflare R2 (S3-compatible API, path-style endpoint, `auto` region).

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-05-inventaris-r2-storage-design.md` — read it if anything below is ambiguous.
- **No automated test suite for this change.** Per project convention (this codebase's tests run manually, not via PHPUnit for feature work like this), every task's verification step is a manual action: run `php spark serve --port 8082`, drive the real admin UI in a browser, and confirm behavior/DB state/R2 bucket contents directly. Do not write PHPUnit tests for this plan.
- R2 credentials (already provisioned, real values — go in the gitignored `.env` only, never commit them):
  - Account ID: `a71d163dfae4657a360cc319c42fe838`
  - Access Key ID: `628c961a8f26e9f2b4c0bda6003ef0bd`
  - Secret Access Key: `f5b630f3e8f7c517e6a99f1f07e7ac21476c704084253ac59f020fc008d5b019`
  - Bucket: `mino`
  - Public domain: `https://cdn.minomartani.com`
  - S3 API endpoint: `https://a71d163dfae4657a360cc319c42fe838.r2.cloudflarestorage.com`
  - Region for R2: literal string `auto`
  - R2 requires **path-style** addressing (`pathStyleEndpoint => true`) — it does not support virtual-hosted-style bucket URLs.
- Existing local-disk uploads (Inventaris' `public/inventaris/...` relative paths, Berita's bare filenames) are left untouched in the DB — no backfill/migration task in this plan.
- Package to install: `async-aws/s3` (not the full `aws/aws-sdk-php`).

---

### Task 1: Install async-aws/s3 and add R2 configuration

**Files:**
- Modify: `composer.json` (via `composer require`)
- Create: `app/Config/R2.php`
- Modify: `.env`
- Modify: `.env.production.example`

**Interfaces:**
- Produces: `Config\R2` with public properties `accountId`, `accessKeyId`, `secretAccessKey`, `bucket`, `publicUrl` (string, no trailing slash) — consumed by `R2Storage` in Task 2.

- [ ] **Step 1: Install the package**

Run: `composer require async-aws/s3`
Expected: composer resolves and installs `async-aws/s3` and its dependencies (e.g. `async-aws/core`) without error, `composer.json`'s `require` block gains `"async-aws/s3": "^..."`.

- [ ] **Step 2: Add R2 env vars to `.env`**

Append this block to the end of `d:\dev\rt29mino\.env`:

```
#--------------------------------------------------------------------
# CLOUDFLARE R2 (Inventaris & Berita photo storage)
#--------------------------------------------------------------------

r2.accountId = a71d163dfae4657a360cc319c42fe838
r2.accessKeyId = 628c961a8f26e9f2b4c0bda6003ef0bd
r2.secretAccessKey = f5b630f3e8f7c517e6a99f1f07e7ac21476c704084253ac59f020fc008d5b019
r2.bucket = mino
r2.publicUrl = https://cdn.minomartani.com
```

- [ ] **Step 3: Add placeholder R2 block to `.env.production.example`**

Append this block to the end of `d:\dev\rt29mino\.env.production.example`:

```
#--------------------------------------------------------------------
# CLOUDFLARE R2 (Inventaris & Berita photo storage)
#--------------------------------------------------------------------

# S3-compatible credentials from the Cloudflare dashboard - R2 - Manage
# API Tokens. accountId is your Cloudflare account ID; publicUrl is the
# custom domain (or r2.dev subdomain) mapped to the bucket for public
# read access.
r2.accountId = REPLACE_WITH_R2_ACCOUNT_ID
r2.accessKeyId = REPLACE_WITH_R2_ACCESS_KEY_ID
r2.secretAccessKey = REPLACE_WITH_R2_SECRET_ACCESS_KEY
r2.bucket = REPLACE_WITH_R2_BUCKET_NAME
r2.publicUrl = REPLACE_WITH_R2_PUBLIC_URL
```

- [ ] **Step 4: Create `app/Config/R2.php`**

```php
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
```

- [ ] **Step 5: Verify the app still boots with the new config and dependency**

Run: `php spark list`
Expected: command list prints with no fatal errors (confirms autoloading of the new package and config class both work).

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock app/Config/R2.php .env.production.example
git commit -m "feat: add R2 config and async-aws/s3 dependency"
```

Note: `.env` is gitignored and won't be staged by this command — that's correct, real credentials must never be committed.

---

### Task 2: Create the R2Storage library

**Files:**
- Create: `app/Libraries/R2Storage.php`

**Interfaces:**
- Consumes: `Config\R2` (Task 1) — `accountId`, `accessKeyId`, `secretAccessKey`, `bucket`, `publicUrl`.
- Produces: `App\Libraries\R2Storage` with:
  - `upload(\CodeIgniter\HTTP\Files\UploadedFile $file, string $prefix): string` — returns the full public URL of the uploaded object.
  - `delete(?string $storedValue): void` — no-op if `$storedValue` is empty or isn't an R2 public URL.

- [ ] **Step 1: Write `app/Libraries/R2Storage.php`**

```php
<?php

namespace App\Libraries;

use AsyncAws\S3\S3Client;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\R2 as R2Config;

class R2Storage
{
    protected S3Client $client;
    protected R2Config $config;

    public function __construct(?R2Config $config = null)
    {
        $this->config = $config ?? config('R2');

        $this->client = new S3Client([
            'endpoint'          => sprintf('https://%s.r2.cloudflarestorage.com', $this->config->accountId),
            'region'            => 'auto',
            'accessKeyId'       => $this->config->accessKeyId,
            'accessKeySecret'   => $this->config->secretAccessKey,
            'pathStyleEndpoint' => true,
        ]);
    }

    public function upload(UploadedFile $file, string $prefix): string
    {
        $key = trim($prefix, '/') . '/' . $file->getRandomName();

        $this->client->putObject([
            'Bucket'      => $this->config->bucket,
            'Key'         => $key,
            'Body'        => fopen($file->getPathname(), 'rb'),
            'ContentType' => $file->getMimeType(),
        ]);

        return $this->config->publicUrl . '/' . $key;
    }

    public function delete(?string $storedValue): void
    {
        if (empty($storedValue)) {
            return;
        }

        $prefix = $this->config->publicUrl . '/';

        if (!str_starts_with($storedValue, $prefix)) {
            return;
        }

        $key = substr($storedValue, strlen($prefix));

        $this->client->deleteObject([
            'Bucket' => $this->config->bucket,
            'Key'    => $key,
        ]);
    }
}
```

- [ ] **Step 2: Commit**

No standalone verification for this step — `R2Storage` is exercised end-to-end once wired into a controller (Task 4's manual verification in Task 5 covers upload, and delete is covered by Task 5's edit/delete checks).

```bash
git add app/Libraries/R2Storage.php
git commit -m "feat: add R2Storage library for S3-compatible upload/delete"
```

---

### Task 3: Add `foto_url()` view helper

**Files:**
- Modify: `app/Helpers/kbw_helper.php`

**Interfaces:**
- Produces: global function `foto_url(string $path, string $localPrefix = ''): string`.

- [ ] **Step 1: Add the helper function**

Add this function anywhere in `app/Helpers/kbw_helper.php`, following the existing `if (!function_exists(...))` guard pattern used by every other function in that file (e.g. see `pre()` at the top of the file):

```php
/**
 * Render a stored photo reference (R2 public URL or legacy local path) as a browser-usable URL
 */
if (!function_exists('foto_url')) {
    function foto_url(string $path, string $localPrefix = '')
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return base_url($localPrefix . $path);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Helpers/kbw_helper.php
git commit -m "feat: add foto_url() helper for mixed R2/local photo paths"
```

---

### Task 4: Wire R2Storage into Inventaris (controller + views)

**Files:**
- Modify: `app/Controllers/Admin/Inventaris.php:1-145`
- Modify: `app/Views/admin/inventaris.php:32`
- Modify: `app/Views/admin/ubah_inventaris.php:21`

**Interfaces:**
- Consumes: `App\Libraries\R2Storage::upload()` / `::delete()` (Task 2), `foto_url()` (Task 3).

- [ ] **Step 1: Update `app/Controllers/Admin/Inventaris.php`**

Add the property and initialize it in the constructor:

```php
use App\Libraries\R2Storage;

class Inventaris extends BaseController
{
    protected $inventarisModel;
    protected $r2Storage;

    public function __construct()
    {
        $this->inventarisModel = new InventarisModel();
        $this->r2Storage       = new R2Storage();
    }
```

In `store()`, replace:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = 'item-' . date('ymd') . '-' . substr(md5(rand()), 0, 10) . '.' . $foto->getExtension();

            $path = FCPATH . 'public/inventaris';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $foto->move($path, $newName);
            $data['foto'] = 'public/inventaris/' . $newName;
        }
```

with:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $data['foto'] = $this->r2Storage->upload($foto, 'inventaris');
        }
```

In `update()`, replace:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = 'item-' . date('ymd') . '-' . substr(md5(rand()), 0, 10) . '.' . $foto->getExtension();

            $path = FCPATH . 'public/inventaris';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $foto->move($path, $newName);
            $data['foto'] = 'public/inventaris/' . $newName;

            // Delete old photo
            $oldItem = $this->inventarisModel->detail($id);
            if (!empty($oldItem->foto) && file_exists(FCPATH . $oldItem->foto)) {
                unlink(FCPATH . $oldItem->foto);
            }
        }
```

with:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $data['foto'] = $this->r2Storage->upload($foto, 'inventaris');

            $oldItem = $this->inventarisModel->detail($id);
            $this->r2Storage->delete($oldItem->foto ?? null);
        }
```

In `delete()`, replace:

```php
    public function delete($id)
    {
        $item = $this->inventarisModel->detail($id);
        if (!empty($item)) {
            if (!empty($item->foto) && file_exists(FCPATH . $item->foto)) {
                unlink(FCPATH . $item->foto);
            }
            $this->inventarisModel->hapus($id);
            setFlashData('success', 'Data inventaris berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }
        return redirect()->to('admin/inventaris');
    }
```

with:

```php
    public function delete($id)
    {
        $item = $this->inventarisModel->detail($id);
        if (!empty($item)) {
            $this->r2Storage->delete($item->foto ?? null);
            $this->inventarisModel->hapus($id);
            setFlashData('success', 'Data inventaris berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }
        return redirect()->to('admin/inventaris');
    }
```

- [ ] **Step 2: Update `app/Views/admin/inventaris.php`**

Line 32, replace:

```php
<img src="<?php echo base_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="showImage('<?php echo base_url($item->foto); ?>', '<?php echo $item->nama_barang; ?>')">
```

with:

```php
<img src="<?php echo foto_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="showImage('<?php echo foto_url($item->foto); ?>', '<?php echo $item->nama_barang; ?>')">
```

- [ ] **Step 3: Update `app/Views/admin/ubah_inventaris.php`**

Line 21, replace:

```php
<img src="<?php echo base_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 100px; height: 100px; object-fit: cover;" class="mb-2">
```

with:

```php
<img src="<?php echo foto_url($item->foto); ?>" alt="<?php echo $item->nama_barang; ?>" style="width: 100px; height: 100px; object-fit: cover;" class="mb-2">
```

- [ ] **Step 4: Commit**

```bash
git add app/Controllers/Admin/Inventaris.php app/Views/admin/inventaris.php app/Views/admin/ubah_inventaris.php
git commit -m "feat: upload Inventaris photos to R2 instead of local disk"
```

---

### Task 5: Manual verification — Inventaris R2 flow

**Files:** none (verification only)

- [ ] **Step 1: Start the dev server**

Run: `php spark serve --port 8082`

- [ ] **Step 2: Verify a pre-existing item still renders (legacy local path)**

In a browser, log in as an admin and open `admin/inventaris`. Confirm any item that already had a photo before this change still shows its thumbnail (this exercises `foto_url()`'s local-path fallback branch).

- [ ] **Step 3: Verify new upload goes to R2**

Go to `admin/inventaris/add`, fill in the form with a photo, submit. Confirm:
- Redirect to `admin/inventaris` with the success flash message.
- The new row's thumbnail loads and its `<img src>` (view page source or inspect element) is a `https://cdn.minomartani.com/inventaris/...` URL, not a local path.

- [ ] **Step 4: Verify edit replaces the R2 object**

Edit the item just created, upload a different photo, submit. Confirm:
- The new photo displays, again via a `cdn.minomartani.com/inventaris/...` URL.
- In the Cloudflare R2 dashboard (or via `wrangler r2 object list mino` if available), the old object key is gone and only the new one remains under `inventaris/`.

- [ ] **Step 5: Verify delete removes the R2 object**

Delete the item from `admin/inventaris`. Confirm the row disappears and the corresponding object is no longer present in the `mino` bucket under `inventaris/`.

- [ ] **Step 6: No commit for this task** (verification only, no file changes)

---

### Task 6: Wire R2Storage into Berita (controller + views)

**Files:**
- Modify: `app/Controllers/Admin/Berita.php:1-106`
- Modify: `app/Views/admin/berita.php:29`
- Modify: `app/Views/admin/ubah_berita.php:52`
- Modify: `app/Views/berita_detail.php:13`

**Interfaces:**
- Consumes: `App\Libraries\R2Storage::upload()` / `::delete()` (Task 2), `foto_url()` (Task 3).

- [ ] **Step 1: Update `app/Controllers/Admin/Berita.php`**

Add the property and initialize it in the constructor:

```php
use App\Libraries\R2Storage;

class Berita extends BaseController
{
    protected $beritaModel;
    protected $r2Storage;

    public function __construct()
    {
        $this->beritaModel = new BeritaModel();
        $this->r2Storage   = new R2Storage();
    }
```

In `store()`, replace:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'public/berita', $newName);

            $data['foto']      = $newName;
            $data['is_status'] = 0;

            $this->beritaModel->insert($data);
            setFlashData('success', 'Success uploading File');
            return redirect()->to('admin/berita');
        } else {
            setFlashData('error', 'Error karna tidak ada file');
            return redirect()->to(back());
        }
```

with:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $data['foto']      = $this->r2Storage->upload($foto, 'berita');
            $data['is_status'] = 0;

            $this->beritaModel->insert($data);
            setFlashData('success', 'Success uploading File');
            return redirect()->to('admin/berita');
        } else {
            setFlashData('error', 'Error karna tidak ada file');
            return redirect()->to(back());
        }
```

In `update()`, replace:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'public/berita', $newName);

            $data['foto'] = $newName;

            // Delete old photo
            $oldFoto = $this->request->getPost('foto_old');
            if ($oldFoto && file_exists(FCPATH . 'public/berita/' . $oldFoto)) {
                unlink(FCPATH . 'public/berita/' . $oldFoto);
            }
        }
```

with:

```php
        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $data['foto'] = $this->r2Storage->upload($foto, 'berita');

            $this->r2Storage->delete($this->request->getPost('foto_old'));
        }
```

- [ ] **Step 2: Update `app/Views/admin/berita.php`**

Line 29, replace:

```php
<td width="1"><img class="rounded" src="<?php echo base_url('public/berita/'.$berita->foto); ?>" width="56" ></td>
```

with:

```php
<td width="1"><img class="rounded" src="<?php echo foto_url($berita->foto, 'public/berita/'); ?>" width="56" ></td>
```

- [ ] **Step 3: Update `app/Views/admin/ubah_berita.php`**

Line 52, replace:

```php
<img class="pr-2 rounded" src="<?php echo base_url('public/berita/'.$berita->foto) ?>" width="70" >
```

with:

```php
<img class="pr-2 rounded" src="<?php echo foto_url($berita->foto, 'public/berita/'); ?>" width="70" >
```

- [ ] **Step 4: Update `app/Views/berita_detail.php`**

Line 13, replace:

```php
<img src="<?= base_url('public/berita/' . $berita->foto) ?>" class="card-img-top rounded" alt="Gambar">
```

with:

```php
<img src="<?= foto_url($berita->foto, 'public/berita/') ?>" class="card-img-top rounded" alt="Gambar">
```

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/Admin/Berita.php app/Views/admin/berita.php app/Views/admin/ubah_berita.php app/Views/berita_detail.php
git commit -m "feat: upload Berita photos to R2 instead of local disk"
```

---

### Task 7: Manual verification — Berita R2 flow

**Files:** none (verification only)

- [ ] **Step 1: Verify a pre-existing berita item still renders (legacy filename)**

With `php spark serve --port 8082` still running, open `admin/berita`. Confirm an existing news item's thumbnail still displays, and open its public detail page (`berita/<slug>` or the tenant-prefixed equivalent) and confirm the image displays there too.

- [ ] **Step 2: Verify new upload goes to R2**

Go to `admin/berita/add`, fill in the form with a photo, submit. Confirm:
- Redirect to `admin/berita` with the success flash message.
- The new row's thumbnail loads from a `https://cdn.minomartani.com/berita/...` URL.
- The public detail page for this new berita also renders the photo correctly via `foto_url()`.

- [ ] **Step 3: Verify edit replaces the R2 object**

Edit the item just created (`admin/berita/edit/<id>`), upload a different photo, submit. Confirm:
- The new photo displays via a `cdn.minomartani.com/berita/...` URL.
- The old object is gone from the `mino` bucket under `berita/` (dashboard or `wrangler r2 object list mino`).

- [ ] **Step 4: No commit for this task** (verification only, no file changes)
