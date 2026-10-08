<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\SuratPemohon;
use App\Models\KetuaModel;
use App\Models\SuratModel;

/**
 * Requests are created by residents via the public Layanan form
 * (App\Controllers\Layanan); admins review them here: compare what the
 * applicant submitted with the RT's warga data, approve or reject, and
 * print the sheet. A manual add/edit path used to exist but wrote to
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

    /** Tenant-scoped detail() or 404 - the id may belong to another RT. */
    private function findOrFail($id)
    {
        $surat = $this->suratModel->detail($id);

        if ($surat === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $surat;
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Surat';
        $data['surats'] = $this->suratModel->all();
        return $this->loadViews('admin/surat', $this->global, $data);
    }

    public function view($id)
    {
        $surat = $this->findOrFail($id);

        $this->global['pageTitle'] = 'Detail Pengajuan Surat';
        $data['surat']     = $surat;
        $data['banding']   = SuratPemohon::bandingkan($surat);
        $data['lampiran']  = SuratPemohon::parseLampiran($surat->lampiran);

        return $this->loadViews('admin/surat_detail', $this->global, $data);
    }

    public function cetak($id)
    {
        $surat = $this->findOrFail($id);
        $rt    = current_rt();

        $this->global['pageTitle'] = 'Surat Keterangan';
        $data['surat']    = $surat;
        $data['pemohon']  = SuratPemohon::dataCetak($surat);
        $data['lampiran'] = SuratPemohon::parseLampiran($surat->lampiran);
        $data['ketuaRt']  = (new KetuaModel())->namaKetuaSekarang();
        $data['ketuaRw']  = $rt->nama_ketua_rw ?? null;
        $data['dukuh']    = $rt->nama_dukuh ?? null;

        return $this->loadViews('admin/surat_keterangan', $this->global, $data);
    }

    public function setuju($id)
    {
        // putuskan() filters by id_rt and by status = menunggu, so a
        // mismatched / foreign id or an already-decided request changes
        // nothing; findOrFail() turns the foreign/nonexistent case into
        // a 404 first.
        $this->findOrFail($id);

        if ($this->suratModel->putuskan((int) $id, SuratModel::STATUS_DISETUJUI)) {
            setFlashData('success', 'Data Surat berhasil di setujui!');
        } else {
            setFlashData('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        return redirect()->to('admin/surat');
    }

    public function tolak($id)
    {
        $this->findOrFail($id);

        $alasan = trim((string) $this->request->getPost('alasan_tolak'));
        if ($alasan === '' || mb_strlen($alasan) > 255) {
            setFlashData('error', 'Alasan penolakan wajib diisi (maksimal 255 karakter).');
            return redirect()->to('admin/surat/view/' . (int) $id);
        }

        if ($this->suratModel->putuskan((int) $id, SuratModel::STATUS_DITOLAK, $alasan)) {
            setFlashData('success', 'Pengajuan surat ditolak.');
        } else {
            setFlashData('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        return redirect()->to('admin/surat');
    }
}
