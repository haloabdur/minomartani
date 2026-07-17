<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SuratModel;

/**
 * Requests are created by residents via the public Layanan form
 * (App\Controllers\Layanan::store()); admins only review and approve
 * them here. A manual add/edit path used to exist but wrote to
 * no_surat/id_alamat columns that don't exist on the surat table and
 * rendered admin/tambah_surat.php / admin/ubah_surat.php views that
 * were never created - it was removed rather than fixed since the
 * real intake flow already works without it.
 */
class Surat extends BaseController
{
    protected $suratModel;

    public function __construct()
    {
        $this->suratModel = new SuratModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Surat';
        $data['surats'] = $this->suratModel->all();
        return $this->loadViews('admin/surat', $this->global, $data);
    }

    public function view($id)
    {
        $this->global['pageTitle'] = 'Surat Keterangan';
        $data['surat'] = $this->suratModel->detail($id);
        return $this->loadViews('admin/surat_keterangan', $this->global, $data);
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
