<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ImageCompressor;
use App\Libraries\R2Storage;
use App\Models\KucingModel;

class Kucing extends BaseController
{
    protected $kucingModel;
    protected $r2Storage;
    protected $imageCompressor;

    public function __construct()
    {
        $this->kucingModel     = new KucingModel();
        $this->r2Storage       = new R2Storage();
        $this->imageCompressor = new ImageCompressor();
    }

    /** R2 key prefix "kucing/{rt_slug}", grouped by tenant in the bucket. */
    private function r2Prefix(): string
    {
        $rt = current_rt();

        return $rt !== null ? 'kucing/' . $rt->slug : 'kucing';
    }

    /**
     * Compresses to WebP and uploads to R2; falls back to local disk
     * (public/kucing) if the R2 call fails. Throws RuntimeException if the
     * image cannot be compressed.
     */
    private function storeFoto($foto): string
    {
        $foto = $this->imageCompressor->compress($foto);

        try {
            return $this->r2Storage->upload($foto, $this->r2Prefix());
        } catch (\Throwable $e) {
            log_message('error', 'R2 upload failed for kucing foto, falling back to local disk: ' . $e->getMessage());

            $newName = $foto->getRandomName();
            if (!is_dir(FCPATH . 'public/kucing')) {
                mkdir(FCPATH . 'public/kucing', 0755, true);
            }
            rename($foto->getTempName(), FCPATH . 'public/kucing/' . $newName);

            return 'public/kucing/' . $newName;
        } finally {
            if (is_file($foto->getTempName())) {
                @unlink($foto->getTempName());
            }
        }
    }

    /** Deletes a kucing photo, routing to R2 or local disk by stored format. */
    private function deleteFoto(?string $oldFoto): void
    {
        if (empty($oldFoto)) {
            return;
        }

        if (str_starts_with($oldFoto, 'http://') || str_starts_with($oldFoto, 'https://')) {
            try {
                $this->r2Storage->delete($oldFoto);
            } catch (\Throwable $e) {
                log_message('error', 'R2 delete failed for kucing foto ' . $oldFoto . ': ' . $e->getMessage());
            }
        } elseif (file_exists(FCPATH . $oldFoto)) {
            unlink(FCPATH . $oldFoto);
        } elseif (file_exists(FCPATH . 'public/kucing/' . basename($oldFoto))) {
            unlink(FCPATH . 'public/kucing/' . basename($oldFoto));
        }
    }

    private function rules(): array
    {
        return [
            'id_warga'      => 'required|is_natural_no_zero',
            'nama'          => 'required|max_length[100]',
            'warna_ciri'    => 'permit_empty|max_length[255]',
            'jenis_kelamin' => 'permit_empty|in_list[jantan,betina]',
            'foto'          => 'permit_empty|is_image[foto]|max_size[foto,5120]',
        ];
    }

    private function formData(): array
    {
        return [
            'pemilik' => $this->kucingModel->pemilikOptions(),
        ];
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Pendataan Kucing';
        $data['kucings'] = $this->kucingModel->all();
        return $this->loadViews('admin/kucing', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Kucing';
        return $this->loadViews('admin/tambah_kucing', $this->global, $this->formData());
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules($this->rules());

        if (!$validation->withRequest($this->request)->run()) {
            return $this->add();
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        if (!$this->kucingModel->pemilikValid($idWarga)) {
            setFlashData('error', 'Pemilik tidak valid!');
            return redirect()->back()->withInput();
        }

        $data = [
            'id_rt'         => current_rt_id(),
            'id_warga'      => $idWarga,
            'nama'          => $this->request->getPost('nama'),
            'warna_ciri'    => $this->request->getPost('warna_ciri') ?: null,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin') ?: null,
            'steril'        => $this->request->getPost('steril') ? 1 : 0,
            'vaksin'        => $this->request->getPost('vaksin') ? 1 : 0,
            'created_at'    => date('Y-m-d H:i:s'),
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

        $this->kucingModel->insert($data);
        setFlashData('success', 'Data kucing berhasil ditambahkan!');
        return redirect()->to('admin/kucing');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Kucing';
        $data = $this->formData();
        $data['item'] = $this->kucingModel->detail($id);

        if (empty($data['item'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_kucing', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // KucingModel::update() only filters by primary key; detail() is
        // tenant-scoped, so a foreign or nonexistent id resolves to empty.
        $oldItem = $this->kucingModel->detail($id);
        if (empty($oldItem)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules($this->rules());

        if (!$validation->withRequest($this->request)->run()) {
            return $this->edit($id);
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        if (!$this->kucingModel->pemilikValid($idWarga)) {
            setFlashData('error', 'Pemilik tidak valid!');
            return redirect()->back()->withInput();
        }

        $data = [
            'id_warga'      => $idWarga,
            'nama'          => $this->request->getPost('nama'),
            'warna_ciri'    => $this->request->getPost('warna_ciri') ?: null,
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin') ?: null,
            'steril'        => $this->request->getPost('steril') ? 1 : 0,
            'vaksin'        => $this->request->getPost('vaksin') ? 1 : 0,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            try {
                $data['foto'] = $this->storeFoto($foto);
            } catch (\RuntimeException $e) {
                setFlashData('error', $e->getMessage());
                return redirect()->back()->withInput();
            }

            $this->deleteFoto($oldItem->foto);
        }

        $this->kucingModel->update($id, $data);
        setFlashData('success', 'Data kucing berhasil diubah!');
        return redirect()->to('admin/kucing');
    }

    public function delete($id)
    {
        $item = $this->kucingModel->detail($id);
        if (!empty($item)) {
            $this->deleteFoto($item->foto);
            $this->kucingModel->hapus($id);
            setFlashData('success', 'Data kucing berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }
        return redirect()->to('admin/kucing');
    }
}
