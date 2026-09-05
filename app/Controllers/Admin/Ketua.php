<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KetuaModel;

class Ketua extends BaseController
{
    protected $ketuaModel;

    public function __construct()
    {
        $this->ketuaModel = new KetuaModel();
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
            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'public/ketua', $newName);

            $data['foto_ketua'] = $newName;
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
        if ($this->ketuaModel->detail($id) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'nama_ketua' => $this->request->getPost('nama_ketua'),
            'mulai'      => $this->request->getPost('mulai'),
            'selesai'    => $this->request->getPost('selesai'),
        ];

        $foto = $this->request->getFile('foto_ketua');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'public/ketua', $newName);

            $data['foto_ketua'] = $newName;

            $oldFoto = $this->request->getPost('foto_ketua_old');
            if ($oldFoto && file_exists(FCPATH . 'public/ketua/' . $oldFoto)) {
                unlink(FCPATH . 'public/ketua/' . $oldFoto);
            }
        }

        $this->ketuaModel->update($id, $data);
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

        if (!empty($ketua->foto_ketua) && file_exists(FCPATH . 'public/ketua/' . $ketua->foto_ketua)) {
            unlink(FCPATH . 'public/ketua/' . $ketua->foto_ketua);
        }

        $this->ketuaModel->delete($id);
        setFlashData('success', 'Ketua RT berhasil dihapus');
        return redirect()->to('admin/ketua');
    }
}
