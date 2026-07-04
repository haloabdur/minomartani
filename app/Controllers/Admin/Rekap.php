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
        $this->global['pageTitle'] = 'Rekap RW';

        // RW accounts see their own RTs; superadmin sees all.
        $rekap = $this->rtModel->rekap(current_rw_id());
        $data['rekap'] = $rekap;

        $idRts = array_map(static fn ($r) => (int) $r->id_rt, $rekap);
        $data['wargas'] = (new WargaModel())->byRtIds($idRts);

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
