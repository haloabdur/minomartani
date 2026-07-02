<?php

namespace App\Controllers;

use App\Models\AlamatModel;
use App\Models\BeritaModel;
use App\Models\WargaModel;

class Home extends BaseController
{
    protected $alamatModel;
    protected $beritaModel;
    protected $wargaModel;

    public function __construct()
    {
        $this->alamatModel = new AlamatModel();
        $this->beritaModel = new BeritaModel();
        $this->wargaModel  = new WargaModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $data['ketuas']    = $db->table('ketua')->get()->getResult();
        $data['beritas']   = $db->table('berita')->where('is_status', 1)->orderBy('timestamp', 'desc')->limit(3)->get()->getResult();
        $data['kk']        = $this->wargaModel->kk_count();
        $data['laki']      = $this->wargaModel->laki_count();
        $data['perempuan'] = $this->wargaModel->perempuan_count();

        return $this->load_view('beranda', $data);
    }

    public function alamat($kode)
    {
        $data['alamat'] = $this->alamatModel->alamat_detail($kode);

        if (empty($data['alamat'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('alamat_detail', $data);
    }

    public function berita($slug)
    {
        $data['berita'] = $this->beritaModel->detail_berita($slug);

        if (empty($data['berita'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('berita_detail', $data);
    }
}
