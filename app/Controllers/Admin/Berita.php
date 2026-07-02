<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BeritaModel;

class Berita extends BaseController
{
    protected $beritaModel;

    public function __construct()
    {
        $this->beritaModel = new BeritaModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Berita';
        $data['beritas'] = $this->beritaModel->all();
        return $this->loadViews('admin/berita', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Berita';
        return $this->loadViews('admin/tambah_berita', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'judul'      => $this->request->getPost('judul'),
            'slug'       => url_title($this->request->getPost('judul'), '-', true),
            'deskripsi'  => $this->request->getPost('deskripsi'),
            'lampiran'   => $this->request->getPost('lampiran'),
            'kategori'   => $this->request->getPost('kategori'),
            'created_by' => auth()->user() ? auth()->user()->id : 0
        ];

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
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah berita';
        $data['berita'] = $this->beritaModel->detail($id);
        return $this->loadViews('admin/ubah_berita', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'judul'     => $this->request->getPost('judul'),
            'slug'      => url_title($this->request->getPost('judul'), '-', true),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'lampiran'  => $this->request->getPost('lampiran'),
            'is_status' => $this->request->getPost('is_status'),
            'kategori'  => $this->request->getPost('kategori')
        ];

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

        $this->beritaModel->update($id, $data);
        setFlashData('success', 'Success update data berita');
        return redirect()->to('admin/berita');
    }
}
