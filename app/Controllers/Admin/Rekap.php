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
        $data['rekap'] = $this->rtModel->rekap(current_rw_id());

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
}
