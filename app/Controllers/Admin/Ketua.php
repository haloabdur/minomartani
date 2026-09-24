<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageCompressor;
use App\Libraries\R2Storage;
use App\Models\KetuaModel;

class Ketua extends BaseController
{
    protected $ketuaModel;
    protected $r2Storage;
    protected $imageCompressor;

    public function __construct()
    {
        $this->ketuaModel      = new KetuaModel();
        $this->r2Storage       = new R2Storage();
        $this->imageCompressor = new ImageCompressor();
    }

    /**
     * Compresses to WebP and uploads to R2 under "ketua/{rt_slug}"; falls
     * back to local disk (public/ketua, bare filename) if the R2 call
     * fails, same as Admin\Berita::storeFoto(). Throws RuntimeException
     * (from ImageCompressor) if the image can't be compressed - callers
     * must catch it before touching the DB.
     */
    private function storeFoto($foto): string
    {
        $foto = $this->imageCompressor->compress($foto);
        $rt   = current_rt();

        try {
            return $this->r2Storage->upload($foto, $rt !== null ? 'ketua/' . $rt->slug : 'ketua');
        } catch (\Throwable $e) {
            log_message('error', 'R2 upload failed for ketua foto, falling back to local disk: ' . $e->getMessage());

            // The compressed file is a WRITEPATH temp file, not a real HTTP
            // upload, so UploadedFile::move() (move_uploaded_file()) would
            // refuse it - rename() it into place instead.
            $newName = $foto->getRandomName();
            if (!is_dir(FCPATH . 'public/ketua')) {
                mkdir(FCPATH . 'public/ketua', 0755, true);
            }
            rename($foto->getTempName(), FCPATH . 'public/ketua/' . $newName);

            return $newName;
        } finally {
            if (is_file($foto->getTempName())) {
                @unlink($foto->getTempName());
            }
        }
    }

    /**
     * Deletes a stored foto, routing to R2 or local disk based on the
     * value's own format (full URL vs bare filename).
     */
    private function deleteFoto(?string $oldFoto): void
    {
        if (empty($oldFoto)) {
            return;
        }

        if (str_starts_with($oldFoto, 'http://') || str_starts_with($oldFoto, 'https://')) {
            try {
                $this->r2Storage->delete($oldFoto);
            } catch (\Throwable $e) {
                log_message('error', 'R2 delete failed for ketua foto ' . $oldFoto . ': ' . $e->getMessage());
            }
        } elseif (is_file(FCPATH . 'public/ketua/' . basename($oldFoto))) {
            unlink(FCPATH . 'public/ketua/' . basename($oldFoto));
        }
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Ketua RT';
        $data['ketuas'] = $this->ketuaModel->all();
        return $this->loadViews('admin/ketua', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Ketua RT';
        return $this->loadViews('admin/tambah_ketua', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'nama_ketua' => $this->request->getPost('nama_ketua'),
            'mulai'      => $this->request->getPost('mulai'),
            'selesai'    => $this->request->getPost('selesai'),
            'id_rt'      => current_rt_id(),
        ];

        $foto = $this->request->getFile('foto_ketua');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            try {
                $data['foto_ketua'] = $this->storeFoto($foto);
            } catch (\RuntimeException $e) {
                setFlashData('error', $e->getMessage());
                return redirect()->to(back());
            }
        }

        $this->ketuaModel->insert($data);
        setFlashData('success', 'Ketua RT berhasil ditambahkan');
        return redirect()->to('admin/ketua');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Ketua RT';
        $data['ketua'] = $this->ketuaModel->detail($id);

        if ($data['ketua'] === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_ketua', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // KetuaModel::update() is the base Model method - it only filters
        // by primary key, not id_rt. detail() is tenant-scoped, so a
        // mismatched or nonexistent id resolves to null here.
        $ketua = $this->ketuaModel->detail($id);

        if ($ketua === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'nama_ketua' => $this->request->getPost('nama_ketua'),
            'mulai'      => $this->request->getPost('mulai'),
            'selesai'    => $this->request->getPost('selesai'),
        ];

        $foto = $this->request->getFile('foto_ketua');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            try {
                $data['foto_ketua'] = $this->storeFoto($foto);
            } catch (\RuntimeException $e) {
                setFlashData('error', $e->getMessage());
                return redirect()->to(back());
            }
        }

        $this->ketuaModel->update($id, $data);

        // Old foto comes from the DB row, not a client-supplied hidden
        // field, so a tampered form can't point deletion at another
        // tenant's object or an arbitrary local path.
        if (isset($data['foto_ketua'])) {
            $this->deleteFoto($ketua->foto_ketua);
        }
        setFlashData('success', 'Ketua RT berhasil diubah');
        return redirect()->to('admin/ketua');
    }

    public function delete($id)
    {
        // Same tenant gate as update(): base Model::delete() only filters
        // by primary key, so this stops a cross-tenant id guess from
        // deleting another RT's item.
        $ketua = $this->ketuaModel->detail($id);

        if ($ketua === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->ketuaModel->delete($id);
        $this->deleteFoto($ketua->foto_ketua);
        setFlashData('success', 'Ketua RT berhasil dihapus');
        return redirect()->to('admin/ketua');
    }
}
