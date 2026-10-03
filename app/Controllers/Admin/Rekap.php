<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\WargaModel;

/**
 * Read-only recap for RW accounts: aggregate numbers per RT plus a
 * read-only warga drill-down. No mutation routes exist on purpose.
 */
class Rekap extends BaseController
{
    protected $rtModel;

    public function __construct()
    {
        $this->rtModel = new RtModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Pusat Agregator Data RW';

        // RW accounts see their own RTs; superadmin sees all.
        $rwId = current_rw_id();
        $rekap = $this->rtModel->rekap($rwId);
        $data['rekap'] = $rekap;

        // Current RW model details
        $rwInfo = null;
        if ($rwId !== null) {
            $rwInfo = (new \App\Models\RwModel())->find($rwId);
        } elseif (! empty($rekap) && ! empty($rekap[0]->id_rw)) {
            $rwInfo = (new \App\Models\RwModel())->find($rekap[0]->id_rw);
        }
        $data['rwInfo'] = $rwInfo;

        $idRts = array_map(static fn ($r) => (int) $r->id_rt, $rekap);
        $data['wargas'] = (new WargaModel())->byRtIds($idRts);

        // Supporting datasets for complete RW aggregation:
        $db = \Config\Database::connect();

        // 1. Summary of Surat requests across RTs
        $suratSummary = [];
        if (! empty($idRts)) {
            $suratSummary = $db->table('surat')
                ->select('surat.maksut, COUNT(*) as total')
                ->whereIn('surat.id_rt', $idRts)
                ->groupBy('surat.maksut')
                ->orderBy('total', 'DESC')
                ->get()->getResult();
        }
        $data['suratSummary'] = $suratSummary;

        // 2. Health check / Posyandu sessions
        $kesehatanKegiatan = [];
        if (! empty($idRts)) {
            $builder = $db->table('kesehatan_kegiatan')
                ->select('kesehatan_kegiatan.*, rt.nama as nama_rt,
                    (SELECT COUNT(*) FROM kesehatan_catatan kc WHERE kc.id_kegiatan = kesehatan_kegiatan.id_kegiatan) as total_peserta')
                ->join('rt', 'rt.id_rt = kesehatan_kegiatan.id_rt', 'left')
                ->groupStart()
                    ->whereIn('kesehatan_kegiatan.id_rt', $idRts);
            if ($rwId !== null) {
                $builder->orWhere('kesehatan_kegiatan.id_rw', $rwId);
            }
            $builder->groupEnd()
                ->orderBy('tanggal_kegiatan', 'DESC')
                ->limit(6);
            $kesehatanKegiatan = $builder->get()->getResult();
        }
        $data['kesehatanKegiatan'] = $kesehatanKegiatan;

        // 3. Inventaris across RTs
        $inventarisList = [];
        if (! empty($idRts)) {
            $inventarisList = $db->table('inventaris')
                ->select('inventaris.*, rt.nama as nama_rt')
                ->join('rt', 'rt.id_rt = inventaris.id_rt', 'left')
                ->whereIn('inventaris.id_rt', $idRts)
                ->orderBy('rt.nama, inventaris.nama_barang')
                ->get()->getResult();
        }
        $data['inventarisList'] = $inventarisList;

        return $this->loadViews('admin/rekap', $this->global, $data);
    }

    public function warga($idRt)
    {
        $rt = $this->rtModel->find($idRt);

        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // RW may only open RTs inside their RW.
        if (current_rw_id() !== null && (int) $rt->id_rw !== current_rw_id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        tenant_set_rt((int) $rt->id_rt);

        $this->global['pageTitle'] = 'Warga ' . $rt->nama;
        $data['rt']     = $rt;
        $data['wargas'] = (new WargaModel())->all();

        return $this->loadViews('admin/rekap_warga', $this->global, $data);
    }

    public function export($idRt)
    {
        $rt = $this->rtModel->find($idRt);

        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // RW may only export RTs inside their RW.
        if (current_rw_id() !== null && (int) $rt->id_rw !== current_rw_id()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        tenant_set_rt((int) $rt->id_rt);

        $type  = $this->request->getGet('type');
        $value = $this->request->getGet('value');

        $data['columns'] = WargaModel::resolveExportColumns($this->request->getGet('columns'));
        $includeDeceased = $this->request->getGet('include_deceased') === '1';

        $data['content'] = (new WargaModel())->export($type, $value, $includeDeceased);

        return view('admin/export_warga', $data);
    }
}
