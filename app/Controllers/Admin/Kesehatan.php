<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KesehatanCatatanModel;
use App\Models\KesehatanKegiatanModel;
use App\Models\RtModel;
use App\Models\WargaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
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

        $isi = $this->request->getGet('isi');
        $data['autoOpenWarga'] = ($isi !== null && ctype_digit((string) $isi)) ? (int) $isi : null;

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

    /**
     * AJAX lookup: RFID scanner reads a card's chip UID and posts it here
     * (GET, no CSRF - read-only) so the kegiatan screen can auto-fill the
     * "Isi Data" form the instant an admin taps an e-KTP. Only resolves
     * for residents already enrolled via daftarRfid() - the UID alone
     * doesn't identify a resident until linked once.
     */
    public function scanRfid($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan.']);
        }

        $kode = $this->normalizeRfidCode((string) $this->request->getGet('kode'));
        if ($kode === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Kode kartu kosong.']);
        }

        $idRts = $this->authorizedRtIds($kegiatan);
        $warga = $this->wargaModel->oneByRfidAndRtIds($kode, $idRts);

        if ($warga === null) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        // Ensure they show up as a participant (blank record if new) without
        // touching any measurements already recorded for them this session.
        // upsert() hands back the row it just wrote/found directly, so this
        // always reflects whatever is actually in the DB for them - no
        // separate re-query (and no risk of a cached GET response papering
        // over already-recorded data).
        $existing = $this->catatanModel->upsert((int) $idKegiatan, (int) $warga->id_warga, (int) $warga->id_rt, [
            'id_user' => auth()->user()->id,
        ]);

        return $this->response->noCache()->setJSON([
            'status' => 'found',
            'warga'  => [
                'idWarga' => (int) $warga->id_warga,
                'nama'    => $warga->nama_warga,
            ],
            'catatan' => [
                'idCatatan'    => $existing->id_catatan ?? null,
                'tensiSistol'  => $existing->tensi_sistol ?? null,
                'tensiDiastol' => $existing->tensi_diastol ?? null,
                'beratBadan'   => $existing->berat_badan ?? null,
                'tinggiBadan'  => $existing->tinggi_badan ?? null,
                'lingkarPerut' => $existing->lingkar_perut ?? null,
                'gulaDarah'    => $existing->gula_darah ?? null,
                'gulaDarahKet' => $existing->gula_darah_ket ?? null,
                'kolesterol'   => $existing->kolesterol ?? null,
                'asamUrat'     => $existing->asam_urat ?? null,
                'catatan'      => $existing->catatan ?? null,
            ],
        ]);
    }

    /**
     * Enrolls a scanned card UID that didn't match anyone: admin picks the
     * owning warga from a search list, and that pairing is saved so future
     * scans of the same card resolve automatically via scanRfid(). Plain
     * form POST + redirect (not AJAX), matching this app's convention.
     */
    public function daftarRfid($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $kode    = $this->normalizeRfidCode((string) $this->request->getPost('kode_rfid'));
        $idWarga = (int) $this->request->getPost('id_warga');

        if ($kode === '' || $idWarga <= 0) {
            setFlashData('error', 'Data kartu tidak lengkap, silakan scan ulang.');
            return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
        }

        $idRts = $this->authorizedRtIds($kegiatan);
        $warga = $this->wargaModel->oneByRtIds($idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $existingOwner = $this->wargaModel->oneByRfidAndRtIds($kode, $idRts);
        if ($existingOwner !== null && (int) $existingOwner->id_warga !== $idWarga) {
            setFlashData('error', 'Kartu ini sudah terdaftar untuk warga lain (' . esc($existingOwner->nama_warga) . ').');
            return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
        }

        try {
            $this->wargaModel->update($idWarga, ['kode_rfid' => $kode]);
        } catch (DatabaseException $e) {
            // Unique constraint hit - card already linked to a resident
            // outside this scope. Generic message: don't leak cross-tenant
            // resident names.
            setFlashData('error', 'Kartu ini sudah terdaftar untuk warga lain.');
            return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
        }

        $this->catatanModel->upsert((int) $idKegiatan, $idWarga, (int) $warga->id_rt, [
            'id_user' => auth()->user()->id,
        ]);

        setFlashData('success', 'Kartu berhasil didaftarkan untuk ' . esc($warga->nama_warga) . '. Silakan isi data kesehatannya.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan . '?isi=' . $idWarga);
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

    /** Normalize a scanned RFID UID (trim + uppercase) so lookups aren't case-sensitive across reads of the same card. */
    private function normalizeRfidCode(string $value): string
    {
        return strtoupper(trim($value));
    }
}
