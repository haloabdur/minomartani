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

    public function index($slug = null)
    {
        $this->resolveTenant($slug);
        return $this->load_view('layanan');
    }

    public function store($slug = null)
    {
        $this->resolveTenant($slug);

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
            'lampiran' => $this->request->getPost('lampiran'),
            'id_rt'    => current_rt_id(),
        ];

        $this->suratModel->insert($data);

        $redirectUrl = 'layanan/sukses';
        if (!empty($slug)) {
            $redirectUrl = $slug . '/' . $redirectUrl;
        }

        return redirect()->to($redirectUrl);
    }

    public function sukses($slug = null)
    {
        $this->resolveTenant($slug);
        return $this->load_view('layanan_sukses');
    }
}
