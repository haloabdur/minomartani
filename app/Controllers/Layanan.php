<?php

namespace App\Controllers;

use App\Models\SuratModel;
use App\Models\WargaModel;

class Layanan extends BaseController
{
    protected $suratModel;
    protected $wargaModel;

    public function __construct()
    {
        $this->suratModel = new SuratModel();
        $this->wargaModel = new WargaModel();
    }

    public function index()
    {
        return $this->load_view('layanan');
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $nik = $this->wargaModel->nik($this->request->getPost('nik'));

        if (!empty($nik)) {
            if ($this->request->getPost('pin') != $nik->kode_rumah) {
                setFlashData('error', 'Data NIK atau PIN Anda salah!');
                return redirect()->to(back());
            }
        } else {
            setFlashData('error', 'Data NIK tidak terdaftar!');
            return redirect()->to(back());
        }

        $data = [
            'id_warga' => $nik->id_warga,
            'maksut'   => $this->request->getPost('maksut'),
            'perlu'    => $this->request->getPost('perlu'),
            'lampiran' => $this->request->getPost('lampiran')
        ];

        $this->suratModel->insert($data);

        return redirect()->to('layanan/sukses');
    }

    public function sukses()
    {
        return $this->load_view('layanan_sukses');
    }
}
