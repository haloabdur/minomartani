<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageCompressor;
use App\Libraries\R2Storage;
use App\Models\InventarisModel;

class Inventaris extends BaseController
{
    protected $inventarisModel;
    protected $r2Storage;
    protected $imageCompressor;

    public function __construct()
    {
        $this->inventarisModel = new InventarisModel();
        $this->r2Storage       = new R2Storage();
        $this->imageCompressor = new ImageCompressor();
    }

    /**
     * R2 key prefix "inventaris/{rt_slug}", so new uploads land grouped by
     * tenant in the bucket (e.g. inventaris/rt29/...). Falls back to bare
     * "inventaris" if no tenant is resolvable.
     */
    private function r2Prefix(): string
    {
        $rt = current_rt();

        return $rt !== null ? 'inventaris/' . $rt->slug : 'inventaris';
    }

    /**
     * Compresses to WebP and uploads to R2 under "inventaris/{rt_slug}"; falls
     * back to local disk (public/inventaris) if the R2 call fails. Throws
     * RuntimeException if the image cannot be compressed.
     */
    private function storeFoto($foto): string
    {
        $foto = $this->imageCompressor->compress($foto);

        try {
            return $this->r2Storage->upload($foto, $this->r2Prefix());
        } catch (\Throwable $e) {
            log_message('error', 'R2 upload failed for inventaris foto, falling back to local disk: ' . $e->getMessage());

            $newName = $foto->getRandomName();
            if (!is_dir(FCPATH . 'public/inventaris')) {
                mkdir(FCPATH . 'public/inventaris', 0755, true);
            }
            rename($foto->getTempName(), FCPATH . 'public/inventaris/' . $newName);

            return 'public/inventaris/' . $newName;
        } finally {
            if (is_file($foto->getTempName())) {
                @unlink($foto->getTempName());
            }
        }
    }

    /**
     * Deletes an inventaris photo, routing to R2 or local disk based
     * on the stored value's format.
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
                log_message('error', 'R2 delete failed for inventaris foto ' . $oldFoto . ': ' . $e->getMessage());
            }
        } elseif (file_exists(FCPATH . $oldFoto)) {
            unlink(FCPATH . $oldFoto);
        } elseif (file_exists(FCPATH . 'public/inventaris/' . basename($oldFoto))) {
            unlink(FCPATH . 'public/inventaris/' . basename($oldFoto));
        }
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Inventaris RT';
        $data['inventaris'] = $this->inventarisModel->all();
        return $this->loadViews('admin/inventaris', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Inventaris';
        return $this->loadViews('admin/tambah_inventaris', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_barang' => 'required',
            'stok'        => 'required|numeric',
            'foto'        => 'permit_empty|is_image[foto]|max_size[foto,5120]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->add();
        }

        $data = [
            'nama_barang' => $this->request->getPost('nama_barang'),
            'stok'        => $this->request->getPost('stok'),
            'created_at'  => date('Y-m-d H:i:s'),
            'id_rt'       => current_rt_id(),
        ];

        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            try {
                $data['foto'] = $this->storeFoto($foto);
            } catch (\RuntimeException $e) {
                setFlashData('error', $e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        $this->inventarisModel->insert($data);
        setFlashData('success', 'Data inventaris berhasil ditambahkan!');
        return redirect()->to('admin/inventaris');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Inventaris';
        $data['item'] = $this->inventarisModel->detail($id);

        if (empty($data['item'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_inventaris', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // InventarisModel::update() is the base Model method - it only
        // filters by primary key, not id_rt. detail() is tenant-scoped,
        // so a mismatched or nonexistent id resolves to null/empty here.
        $oldItem = $this->inventarisModel->detail($id);
        if (empty($oldItem)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_barang' => 'required',
            'stok'        => 'required|numeric',
            'foto'        => 'permit_empty|is_image[foto]|max_size[foto,5120]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->edit($id);
        }

        $data = [
            'nama_barang' => $this->request->getPost('nama_barang'),
            'stok'        => $this->request->getPost('stok'),
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            try {
                $data['foto'] = $this->storeFoto($foto);
            } catch (\RuntimeException $e) {
                setFlashData('error', $e->getMessage());
                return redirect()->back()->withInput();
            }

            // Delete old photo
            if (!empty($oldItem->foto)) {
                $this->deleteFoto($oldItem->foto);
            }
        }

        $this->inventarisModel->update($id, $data);
        setFlashData('success', 'Data inventaris berhasil diubah!');
        return redirect()->to('admin/inventaris');
    }

    public function delete($id)
    {
        $item = $this->inventarisModel->detail($id);
        if (!empty($item)) {
            if (!empty($item->foto)) {
                $this->deleteFoto($item->foto);
            }
            $this->inventarisModel->hapus($id);
            setFlashData('success', 'Data inventaris berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }
        return redirect()->to('admin/inventaris');
    }
}
