<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\WargaModel;
use App\Models\BeritaModel;
use App\Models\SuratModel;
use App\Models\AlamatModel;

class Dashboard extends BaseController
{
    protected $wargaModel;
    protected $beritaModel;
    protected $suratModel;
    protected $alamatModel;

    public function __construct()
    {
        $this->wargaModel  = new WargaModel();
        $this->beritaModel = new BeritaModel();
        $this->suratModel  = new SuratModel();
        $this->alamatModel = new AlamatModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Dashboard Warga ' . (current_rt()->nama ?? 'RT 29 Minomartani');

        $data['warga']     = $this->wargaModel->count();
        $data['kk']        = $this->wargaModel->kk_count();
        $data['berita']    = $this->beritaModel->count();
        $data['surat']     = $this->suratModel->count();
        $data['laki']      = $this->wargaModel->laki_count();
        $data['perempuan'] = $this->wargaModel->perempuan_count();
        $data['alamat']    = $this->alamatModel->alamat_count();
        $data['kosong']    = $this->alamatModel->kosong_count();

        return $this->loadViews('admin/dashboard', $this->global, $data);
    }

    public function error_404()
    {
        $this->global['pageTitle'] = 'Error 404';
        return $this->loadViews('admin/error_404', $this->global);
    }

    public function switchTenant(int $idRt)
    {
        if (! auth()->user()->inGroup('superadmin')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Verify if the RT exists and is active
        $rt = model(\App\Models\RtModel::class)->find($idRt);
        if ($rt !== null && (int)$rt->is_aktif === 1) {
            session()->set('tenant_rt_id', $idRt);
            setFlashData('success', 'Berhasil beralih ke ' . $rt->nama);

            helper('subdomain');
            $rawHost = $this->request->getHeaderLine('Host');
            if ($rawHost === '') {
                $rawHost = (string) ($this->request->getServer('HTTP_HOST') ?? $this->request->getUri()->getHost());
            }

            $hostParts = explode(':', $rawHost, 2);
            $hostname  = strtolower(trim($hostParts[0]));
            $port      = isset($hostParts[1]) ? ':' . $hostParts[1] : '';

            $currentSubdomain = subdomain_label($hostname);
            if ($currentSubdomain !== null) {
                $parts      = explode('.', $hostname);
                $domainPart = implode('.', array_slice($parts, 1));

                if (! empty($rt->subdomain)) {
                    $newHost = $rt->subdomain . '.' . $domainPart . $port;
                } else {
                    $newHost = $domainPart . $port;
                }

                $scheme = $this->request->getUri()->getScheme();
                if ($scheme === '') {
                    $scheme = 'http';
                }

                return redirect()->to($scheme . '://' . $newHost . '/admin/dashboard');
            }
        }

        return redirect()->to('admin/dashboard');
    }
}
