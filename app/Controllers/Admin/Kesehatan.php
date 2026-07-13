<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KesehatanCatatanModel;
use App\Models\KesehatanKegiatanModel;
use App\Models\RtModel;
use App\Models\WargaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Kesehatan extends BaseController
{
    protected $kegiatanModel;
    protected $catatanModel;
    protected $wargaModel;
    protected $rtModel;

    public function __construct()
    {
        $this->kegiatanModel = new KesehatanKegiatanModel();
        $this->catatanModel  = new KesehatanCatatanModel();
        $this->wargaModel    = new WargaModel();
        $this->rtModel       = new RtModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kesehatan Lansia';
        $data['kegiatans'] = $this->kegiatanModel->forCurrentScope();
        return $this->loadViews('admin/kesehatan', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Kegiatan';
        return $this->loadViews('admin/tambah_kegiatan', $this->global, []);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw PageNotFoundException::forPageNotFound();
        }

        $namaKegiatan    = trim((string) $this->request->getPost('nama_kegiatan'));
        $tanggalKegiatan = trim((string) $this->request->getPost('tanggal_kegiatan'));

        if ($namaKegiatan === '' || $tanggalKegiatan === '') {
            setFlashData('error', 'Nama kegiatan dan tanggal wajib diisi!');
            return redirect()->to(back());
        }

        $data = [
            'nama_kegiatan'    => $namaKegiatan,
            'tanggal_kegiatan' => $tanggalKegiatan,
            'catatan'          => $this->request->getPost('catatan'),
            'id_user'          => auth()->user()->id,
        ];

        $rwId = current_rw_id();
        if ($rwId !== null) {
            $data['id_rw'] = $rwId;
        } else {
            $data['id_rt'] = current_rt_id();
        }

        $idKegiatan = $this->kegiatanModel->insert($data);
        setFlashData('success', 'Kegiatan berhasil dibuat, silakan catat peserta.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
    }

    public function editKegiatan($id)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $id);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Ubah Kegiatan';
        $data['kegiatan'] = $kegiatan;
        return $this->loadViews('admin/ubah_kegiatan', $this->global, $data);
    }

    public function updateKegiatan($id)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $id);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $namaKegiatan    = trim((string) $this->request->getPost('nama_kegiatan'));
        $tanggalKegiatan = trim((string) $this->request->getPost('tanggal_kegiatan'));

        if ($namaKegiatan === '' || $tanggalKegiatan === '') {
            setFlashData('error', 'Nama kegiatan dan tanggal wajib diisi!');
            return redirect()->to(back());
        }

        $this->kegiatanModel->update($kegiatan->id_kegiatan, [
            'nama_kegiatan'    => $namaKegiatan,
            'tanggal_kegiatan' => $tanggalKegiatan,
            'catatan'          => $this->request->getPost('catatan'),
        ]);

        setFlashData('success', 'Kegiatan berhasil diubah!');
        return redirect()->to('admin/kesehatan/kegiatan/' . $kegiatan->id_kegiatan);
    }

    public function kegiatan($id)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $id);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts = $this->authorizedRtIds($kegiatan);

        $catatan = $this->catatanModel->byKegiatan((int) $id);

        // Default participant list: auto-filtered lansia, plus anyone
        // manually added via the "tambah peserta lain" modal (they have a
        // kesehatan_catatan row - possibly still blank - but aren't lansia,
        // so lansiaByRtIds() alone wouldn't surface them on reload).
        $peserta   = $this->wargaModel->lansiaByRtIds($idRts);
        $lansiaIds = array_map(static fn ($w) => (int) $w->id_warga, $peserta);
        $manualIds = array_diff(array_map('intval', array_keys($catatan)), $lansiaIds);

        if (!empty($manualIds)) {
            $peserta = array_merge($peserta, $this->wargaModel->byIds(array_values($manualIds)));
            usort($peserta, static fn ($a, $b) => strcmp($a->nama_warga, $b->nama_warga));
        }

        $this->global['pageTitle'] = 'Kegiatan: ' . $kegiatan->nama_kegiatan;
        $data['kegiatan']    = $kegiatan;
        $data['peserta']     = $peserta;
        $data['catatan']     = $catatan;
        $data['semuaWarga']  = $this->wargaModel->allByRtIds($idRts);
        $data['pesertaIds']  = array_map(static fn ($p) => (int) $p->id_warga, $peserta);
        $data['multiRt']     = count($idRts) > 1;

        return $this->loadViews('admin/kegiatan_kesehatan', $this->global, $data);
    }

    /** Add a resident to the kegiatan's participant list with a blank record, so they show up for data entry. */
    public function tambahPeserta($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        $idRts   = $this->authorizedRtIds($kegiatan);

        $warga = $this->wargaModel->oneByRtIds($idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->catatanModel->upsert((int) $idKegiatan, $idWarga, (int) $warga->id_rt, [
            'id_user' => auth()->user()->id,
        ]);

        setFlashData('success', esc($warga->nama_warga) . ' ditambahkan ke kegiatan.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
    }

    public function simpanCatatan($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        $idRts   = $this->authorizedRtIds($kegiatan);

        $warga = $this->wargaModel->oneByRtIds($idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $data = [
            'tensi_sistol'   => $this->emptyToNull($this->request->getPost('tensi_sistol')),
            'tensi_diastol'  => $this->emptyToNull($this->request->getPost('tensi_diastol')),
            'berat_badan'    => $this->emptyToNull($this->request->getPost('berat_badan')),
            'tinggi_badan'   => $this->emptyToNull($this->request->getPost('tinggi_badan')),
            'lingkar_perut'  => $this->emptyToNull($this->request->getPost('lingkar_perut')),
            'gula_darah'     => $this->emptyToNull($this->request->getPost('gula_darah')),
            'gula_darah_ket' => $this->emptyToNull($this->request->getPost('gula_darah_ket')),
            'kolesterol'     => $this->emptyToNull($this->request->getPost('kolesterol')),
            'asam_urat'      => $this->emptyToNull($this->request->getPost('asam_urat')),
            'catatan'        => $this->emptyToNull($this->request->getPost('catatan')),
            'id_user'        => auth()->user()->id,
        ];

        $this->catatanModel->upsert((int) $idKegiatan, $idWarga, (int) $warga->id_rt, $data);

        setFlashData('success', 'Data kesehatan ' . $warga->nama_warga . ' tersimpan.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
    }

    public function hapusCatatan($idKegiatan, $idCatatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->catatanModel->where('id_catatan', $idCatatan)->where('id_kegiatan', $idKegiatan)->delete();

        setFlashData('success', 'Catatan berhasil dihapus.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
    }

    public function warga($idWarga)
    {
        $idRts = $this->authorizedRtIdsForScope();
        $warga = $this->wargaModel->oneByRtIds((int) $idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Riwayat Kesehatan: ' . $warga->nama_warga;
        $data['warga']   = $warga;
        $data['riwayat'] = $this->catatanModel->forWarga((int) $idWarga);

        return $this->loadViews('admin/kesehatan_warga', $this->global, $data);
    }

    /**
     * RT ids the caller may act within, given an already-authorized
     * kegiatan (RT-owned -> that one RT; RW-owned -> every RT in that RW).
     *
     * @return int[]
     */
    private function authorizedRtIds(object $kegiatan): array
    {
        if ($kegiatan->id_rw !== null) {
            return array_map(static fn ($r) => (int) $r->id_rt, $this->rtModel->byRw((int) $kegiatan->id_rw));
        }

        return [(int) $kegiatan->id_rt];
    }

    /**
     * RT ids the caller may act within, based on session scope alone
     * (no specific kegiatan row involved - used by warga()).
     *
     * @return int[]
     */
    private function authorizedRtIdsForScope(): array
    {
        $rwId = current_rw_id();
        if ($rwId !== null) {
            return array_map(static fn ($r) => (int) $r->id_rt, $this->rtModel->byRw($rwId));
        }

        return [current_rt_id()];
    }

    private function emptyToNull(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
