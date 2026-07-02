<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PekerjaanModel;

class Pekerjaan extends BaseController
{
    protected $pekerjaanModel;

    public function __construct()
    {
        $this->pekerjaanModel = new PekerjaanModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Pekerjaan';
        $data['pekerjaans'] = $this->pekerjaanModel->all();
        return $this->loadViews('admin/pekerjaan', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Pekerjaan';
        return $this->loadViews('admin/tambah_pekerjaan', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_pekerjaan' => 'required|is_unique[pekerjaan.nama_pekerjaan]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            setFlashData('error', 'Data pekerjaan sudah ada dalam database!');
            return redirect()->to(back());
        }

        $data = [
            'nama_pekerjaan' => $this->request->getPost('nama_pekerjaan')
        ];

        $this->pekerjaanModel->insert($data);
        setFlashData('success', 'Data pekerjaan berhasil di tambahkan!');
        return redirect()->to('admin/pekerjaan');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah pekerjaan';
        $data['pekerjaan'] = $this->pekerjaanModel->detail($id);
        return $this->loadViews('admin/ubah_pekerjaan', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_pekerjaan' => 'required|is_unique[pekerjaan.nama_pekerjaan]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            setFlashData('error', 'Data pekerjaan sudah ada dalam database!');
            return redirect()->to(back());
        }

        $data = [
            'nama_pekerjaan' => $this->request->getPost('nama_pekerjaan')
        ];

        $this->pekerjaanModel->update($id, $data);
        setFlashData('success', 'Data pekerjaan berhasil di ubah!');
        return redirect()->to('admin/pekerjaan');
    }
}
