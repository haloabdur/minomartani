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
        if (!auth()->user()->inGroup('superadmin')) {
            setFlashData('error', 'Akses ditolak! Fitur ini khusus untuk superadmin.');
            return redirect()->to('admin/alamat');
        }
        $this->global['pageTitle'] = 'Tambah Alamat';
        $data['rws'] = model(\App\Models\RwModel::class)->aktif();
        $data['rts'] = model(\App\Models\RtModel::class)->aktif();
        return $this->loadViews('admin/tambah_alamat', $this->global, $data);
    }

    public function store()
    {
        if (!auth()->user()->inGroup('superadmin')) {
            setFlashData('error', 'Akses ditolak! Fitur ini khusus untuk superadmin.');
            return redirect()->to('admin/alamat');
        }
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $idRt = (int) $this->request->getPost('id_rt');

        $cek = $this->alamatModel->cek_alamat(
            $this->request->getPost('jalan'),
            $this->request->getPost('nomor'),
            $idRt
        );

        if ($cek) {
            setFlashData('error', 'Alamat telah digunakan!');
            return redirect()->to(back());
        }

        $rt = model(\App\Models\RtModel::class)->find($idRt);
        if ($rt === null || (int)$rt->is_aktif !== 1) {
            setFlashData('error', 'RT tidak valid atau tidak aktif!');
            return redirect()->to(back());
        }

        $kode = str_replace(" ", "", $this->request->getPost('jalan') . $this->request->getPost('nomor'));
        $alamatString = $this->request->getPost('jalan') . '/' . $this->request->getPost('nomor');

        $kodeRumah = trim((string) $this->request->getPost('kode_rumah'));

        $data = [
            'alamat'     => $alamatString,
            'qrcode'     => $kode,
            'kode_rumah' => $kodeRumah !== '' ? $kodeRumah : null,
            'id_rt'      => $idRt,
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

        $kodeRumah = trim((string) $this->request->getPost('kode_rumah'));

        $data = [
            'alamat'     => $this->request->getPost('alamat'),
            'kode_rumah' => $kodeRumah !== '' ? $kodeRumah : null,
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
