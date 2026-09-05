<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PapanInformasiModel;

class PapanInformasi extends BaseController
{
    protected $papanInformasiModel;

    public function __construct()
    {
        $this->papanInformasiModel = new PapanInformasiModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Papan Informasi';
        $data['papans'] = $this->papanInformasiModel->all();
        return $this->loadViews('admin/papan_informasi', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Papan Informasi';
        return $this->loadViews('admin/tambah_papan_informasi', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'judul'      => $this->request->getPost('judul'),
            'isi'        => $this->request->getPost('isi'),
            'lampiran'   => $this->request->getPost('lampiran'),
            'is_status'  => 0,
            'created_by' => auth()->user() ? auth()->user()->id : 0,
            'id_rt'      => current_rt_id(),
        ];

        $this->papanInformasiModel->insert($data);
        setFlashData('success', 'Papan informasi berhasil ditambahkan');
        return redirect()->to('admin/papan-informasi');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Papan Informasi';
        $data['papan'] = $this->papanInformasiModel->detail($id);

        if ($data['papan'] === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_papan_informasi', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // PapanInformasiModel::update() is the base Model method - it only
        // filters by primary key, not id_rt. detail() is tenant-scoped,
        // so a mismatched or nonexistent id resolves to null here.
        if ($this->papanInformasiModel->detail($id) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'judul'     => $this->request->getPost('judul'),
            'isi'       => $this->request->getPost('isi'),
            'lampiran'  => $this->request->getPost('lampiran'),
            'is_status' => $this->request->getPost('is_status'),
        ];

        $this->papanInformasiModel->update($id, $data);
        setFlashData('success', 'Papan informasi berhasil diubah');
        return redirect()->to('admin/papan-informasi');
    }

    public function delete($id)
    {
        // Same tenant gate as update(): base Model::delete() only filters
        // by primary key, so this stops a cross-tenant id guess from
        // deleting another RT's item.
        if ($this->papanInformasiModel->detail($id) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->papanInformasiModel->delete($id);
        setFlashData('success', 'Papan informasi berhasil dihapus');
        return redirect()->to('admin/papan-informasi');
    }
}
