<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\WargaModel;
use App\Models\PekerjaanModel;
use App\Models\AlamatModel;

class Warga extends BaseController
{
    protected $wargaModel;
    protected $pekerjaanModel;
    protected $alamatModel;

    public function __construct()
    {
        $this->wargaModel     = new WargaModel();
        $this->pekerjaanModel = new PekerjaanModel();
        $this->alamatModel    = new AlamatModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Warga';
        $data['wargas'] = $this->wargaModel->all();
        return $this->loadViews('admin/warga', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Warga';
        $data['pekerjaans']       = $this->pekerjaanModel->all();
        $data['alamats']          = $this->alamatModel->all();
        $data['status_keluargas'] = $this->wargaModel->get_status_keluarga();
        $data['status_penduduks'] = $this->wargaModel->get_status_penduduk();
        $data['wargas']           = $this->wargaModel->all();
        return $this->loadViews('admin/tambah_warga', $this->global, $data);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Check unique NIK
        $validation = \Config\Services::validation();
        $validation->setRules([
            'nik' => 'required|is_unique[warga.nik]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            setFlashData('error', 'Nomor NIK sudah ada dalam database!');
            return redirect()->to(back());
        }

        $data = [
            'nama_warga'        => $this->request->getPost('nama_warga'),
            'no_kk'             => $this->request->getPost('no_kk'),
            'id_alamat'         => $this->request->getPost('id_alamat'),
            'alamat_lengkap'    => $this->request->getPost('alamat_lengkap'),
            'nik'               => $this->request->getPost('nik'),
            'jenis_kelamin'     => $this->request->getPost('jenis_kelamin'),
            'tempat_lahir'      => $this->request->getPost('tempat_lahir'),
            'tanggal_lahir'     => $this->request->getPost('tanggal_lahir'),
            'gol_darah'         => $this->request->getPost('gol_darah') == 'tidak' ? null : $this->request->getPost('gol_darah'),
            'agama'             => $this->request->getPost('agama'),
            'pendidikan'        => $this->request->getPost('pendidikan'),
            'id_pekerjaan'      => $this->request->getPost('id_pekerjaan'),
            'status_kawin'      => $this->request->getPost('status_kawin'),
            'tanggal_kawin'     => $this->request->getPost('tanggal_kawin') ?: null,
            'id_status_keluarga' => $this->request->getPost('id_status_keluarga'),
            'ayah'              => $this->request->getPost('ayah'),
            'ibu'               => $this->request->getPost('ibu'),
            'no_hp'             => $this->request->getPost('no_hp'),
            'email'             => $this->request->getPost('email'),
            'status_warga'      => $this->request->getPost('status_warga'),
            'id_status_penduduk' => $this->request->getPost('id_status_penduduk'),
            'sumber_air'        => $this->request->getPost('sumber_air'),
            'id_rt'             => current_rt_id(),
        ];

        $this->wargaModel->insert($data);
        setFlashData('success', 'Data Warga berhasil di tambahkan!');
        return redirect()->to('admin/warga');
    }

    public function view($id)
    {
        $this->global['pageTitle'] = 'Lihat Warga';
        $data['warga']     = $this->wargaModel->detail($id);
        $data['pekerjaan'] = $this->pekerjaanModel->detail($data['warga']->id_pekerjaan);
        return $this->loadViews('admin/lihat_warga', $this->global, $data);
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Warga';
        $data['warga']            = $this->wargaModel->detail($id);
        $data['pekerjaans']       = $this->pekerjaanModel->all();
        $data['status_keluargas'] = $this->wargaModel->get_status_keluarga();
        $data['alamats']          = $this->alamatModel->all();
        $data['status_penduduks'] = $this->wargaModel->get_status_penduduk();
        $data['wargas']           = $this->wargaModel->all();
        return $this->loadViews('admin/ubah_warga', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'nama_warga'        => $this->request->getPost('nama_warga'),
            'id_alamat'         => $this->request->getPost('id_alamat'),
            'no_kk'             => $this->request->getPost('no_kk'),
            'alamat_lengkap'    => $this->request->getPost('alamat_lengkap'),
            'jenis_kelamin'     => $this->request->getPost('jenis_kelamin'),
            'tempat_lahir'      => $this->request->getPost('tempat_lahir'),
            'tanggal_lahir'     => $this->request->getPost('tanggal_lahir'),
            'gol_darah'         => $this->request->getPost('gol_darah'),
            'agama'             => $this->request->getPost('agama'),
            'pendidikan'        => $this->request->getPost('pendidikan'),
            'id_pekerjaan'      => $this->request->getPost('id_pekerjaan'),
            'status_kawin'      => $this->request->getPost('status_kawin'),
            'id_status_keluarga' => $this->request->getPost('id_status_keluarga'),
            'ayah'              => $this->request->getPost('ayah'),
            'ibu'               => $this->request->getPost('ibu'),
            'no_hp'             => $this->request->getPost('no_hp'),
            'email'             => $this->request->getPost('email'),
            'is_hidup'          => $this->request->getPost('is_hidup'),
            'status_warga'      => $this->request->getPost('status_warga'),
            'sumber_air'        => $this->request->getPost('sumber_air')
        ];

        if ($this->request->getPost('nik') != $this->request->getPost('onik')) {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'nik' => 'required|is_unique[warga.nik]'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                setFlashData('error', 'Nomor NIK sudah ada dalam database!');
                return redirect()->to(back());
            }

            $data['nik'] = $this->request->getPost('nik');
        }

        $this->wargaModel->update($id, $data);
        setFlashData('success', 'Data Warga berhasil di diubah!');
        return redirect()->to('admin/warga');
    }

    public function export()
    {
        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');

        $data['columns'] = WargaModel::resolveExportColumns($this->request->getGet('columns'));
        $includeDeceased = $this->request->getGet('include_deceased') === '1';

        $data['content'] = $this->wargaModel->export($type, $value, $includeDeceased);
        return view('admin/export_warga', $data);
    }
}
