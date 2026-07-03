<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SuratModel;
use App\Models\AlamatModel;

class Surat extends BaseController
{
    protected $suratModel;
    protected $alamatModel;

    public function __construct()
    {
        $this->suratModel  = new SuratModel();
        $this->alamatModel = new AlamatModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Surat';
        $data['surats'] = $this->suratModel->all();
        return $this->loadViews('admin/surat', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Surat';
        $data['alamats'] = $this->alamatModel->all();
        return $this->loadViews('admin/tambah_surat', $this->global, $data);
    }

    public function view($id)
    {
        $this->global['pageTitle'] = 'Surat Keterangan';
        $data['surat'] = $this->suratModel->detail($id);
        return $this->loadViews('admin/surat_keterangan', $this->global, $data);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'no_surat' => 'required|is_unique[surat.no_surat]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            setFlashData('error', 'Nomor Surat sudah ada dalam database!');
            return redirect()->to(back());
        }

        $data = [
            'no_surat'  => $this->request->getPost('no_surat'),
            'id_alamat' => $this->request->getPost('id_alamat'),
            'id_rt'     => current_rt_id(),
        ];

        $this->suratModel->insert($data);
        setFlashData('success', 'Data Surat berhasil di tambahkan!');
        return redirect()->to('admin/surat');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah surat';
        $data['surat']   = $this->suratModel->detail($id);
        $data['alamats'] = $this->alamatModel->all();
        return $this->loadViews('admin/ubah_surat', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'no_surat' => 'required|is_unique[surat.no_surat]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            setFlashData('error', 'Nomor Surat sudah ada dalam database!');
            return redirect()->to(back());
        }

        $data = [
            'no_surat'  => $this->request->getPost('no_surat'),
            'id_alamat' => $this->request->getPost('id_alamat')
        ];

        $this->suratModel->update($id, $data);
        setFlashData('success', 'Data Surat berhasil di update!');
        return redirect()->to('admin/surat');
    }

    public function setuju($id)
    {
        $data = [
            'status_surat' => 1
        ];

        $this->suratModel->update($id, $data);
        setFlashData('success', 'Data Surat berhasil di setujui!');
        return redirect()->to('admin/surat');
    }
}
