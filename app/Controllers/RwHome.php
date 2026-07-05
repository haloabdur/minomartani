<?php

namespace App\Controllers;

use App\Models\RtModel;
use App\Models\RwModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RwHome extends BaseController
{
    public function index()
    {
        $host = request_host($this->request);
        $label = subdomain_label($host) ?? '';
        $rw = (new RwModel())->bySubdomain($label);

        if ($rw === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $data['rw']  = $rw;
        $data['rts'] = (new RtModel())->byRw((int) $rw->id_rw);

        return $this->load_view('rw_beranda', $data);
    }
}
