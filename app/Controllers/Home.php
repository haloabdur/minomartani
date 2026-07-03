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

    public function index(string $slug = null)
    {
        $this->resolveTenant($slug);

        $db = \Config\Database::connect();

        $data['ketuas']    = $db->table('ketua')->where('id_rt', current_rt_id())->get()->getResult();
        $data['beritas']   = $db->table('berita')->where('id_rt', current_rt_id())->where('is_status', 1)->orderBy('timestamp', 'desc')->limit(3)->get()->getResult();
        $data['kk']        = $this->wargaModel->kk_count();
        $data['laki']      = $this->wargaModel->laki_count();
        $data['perempuan'] = $this->wargaModel->perempuan_count();

        return $this->load_view('beranda', $data);
    }

    public function alamat($param1, $param2 = null)
    {
        if ($param2 === null) {
            // Unslugged: $param1 is $kode
            $slug = null;
            $kode = $param1;
        } else {
            // Slugged: $param1 is $slug, $param2 is $kode
            $slug = $param1;
            $kode = $param2;
        }

        $this->resolveTenant($slug);

        $data['alamat'] = $this->alamatModel->alamat_detail($kode);

        if (empty($data['alamat'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('alamat_detail', $data);
    }

    public function berita($param1, $param2 = null)
    {
        if ($param2 === null) {
            // Unslugged: $param1 is $newsSlug
            $slug = null;
            $newsSlug = $param1;
        } else {
            // Slugged: $param1 is $slug, $param2 is $newsSlug
            $slug = $param1;
            $newsSlug = $param2;
        }

        $this->resolveTenant($slug);

        $data['berita'] = $this->beritaModel->detail_berita($newsSlug);

        if (empty($data['berita'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->load_view('berita_detail', $data);
    }
}
