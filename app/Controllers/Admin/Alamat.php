<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AlamatModel;

class Alamat extends BaseController
{
    protected $alamatModel;

    public function __construct()
    {
        $this->alamatModel = new AlamatModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Alamat';
        $data['alamats'] = $this->alamatModel->all();
        $data['slug'] = current_rt()->slug ?? 'rt29';
        return $this->loadViews('admin/alamat', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Alamat';
        return $this->loadViews('admin/tambah_alamat', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $cek = $this->alamatModel->cek_alamat($this->request->getPost('jalan'), $this->request->getPost('nomor'));

        if ($cek) {
            setFlashData('error', 'Alamat telah digunakan!');
            return redirect()->to(back());
        }

        $kode = str_replace(" ", "", $this->request->getPost('jalan') . $this->request->getPost('nomor'));

        $data = [
            'jalan'      => $this->request->getPost('jalan'),
            'nomor'      => $this->request->getPost('nomor'),
            'kode_rumah' => $kode,
            'qrcode'     => $kode,
            'id_rt'      => current_rt_id(),
        ];

        $this->alamatModel->insert($data);
        setFlashData('success', 'Berhasil menambahkan alamat');
        return redirect()->to('admin/alamat');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Alamat';
        $data['alamat'] = $this->alamatModel->detail($id);
        $data['slug'] = current_rt()->slug ?? 'rt29';
        return $this->loadViews('admin/ubah_alamat', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'alamat' => $this->request->getPost('alamat')
        ];

        $this->alamatModel->update($id, $data);
        setFlashData('success', 'Data alamat berhasil diubah!');
        return redirect()->to('admin/alamat');
    }

    public function generate_qrcode($id)
    {
        $alamat = $this->alamatModel->detail($id);
        $kode   = "mino_" . $alamat->id_alamat;

        $data = [
            'qrcode' => $kode
        ];

        $this->alamatModel->update($id, $data);
        setFlashData('success', 'QR Code berhasil di-generate!');
        return redirect()->to('admin/alamat');
    }
}
