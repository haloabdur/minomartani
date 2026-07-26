<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KesehatanCatatanModel;
use App\Models\KesehatanKegiatanModel;
use App\Models\PekerjaanModel;
use App\Models\RtModel;
use App\Models\RwModel;
use App\Models\WargaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;

class Kesehatan extends BaseController
{
    protected $kegiatanModel;
    protected $catatanModel;
    protected $wargaModel;
    protected $rtModel;
    protected $rwModel;
    protected $pekerjaanModel;

    /**
     * Kelurahan shown on the printed/exported rekap header. Not a DB
     * column anywhere (schema has no kelurahan table) - this app is
     * built specifically for RT/RW units inside Kelurahan Minomartani
     * (see CLAUDE.md), so it's a fixed label rather than per-tenant data.
     */
    private const KALURAHAN = 'Minomartani';

    public function __construct()
    {
        $this->kegiatanModel  = new KesehatanKegiatanModel();
        $this->catatanModel   = new KesehatanCatatanModel();
        $this->wargaModel     = new WargaModel();
        $this->rtModel        = new RtModel();
        $this->rwModel        = new RwModel();
        $this->pekerjaanModel = new PekerjaanModel();
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

        $peserta = $this->pesertaForKegiatan($idRts, $catatan);

        $this->global['pageTitle'] = 'Kegiatan: ' . $kegiatan->nama_kegiatan;
        $data['kegiatan']    = $kegiatan;
        $data['peserta']     = $peserta;
        $data['catatan']     = $catatan;
        $data['semuaWarga']  = $this->wargaModel->allByRtIds($idRts);
        $data['pesertaIds']  = array_map(static fn ($p) => (int) $p->id_warga, $peserta);
        $data['multiRt']     = count($idRts) > 1;
        // Every RT the caller may add a resident into, for the "tambah
        // warga baru" picker. Not derived from $peserta (like $rtOptions
        // below is, for the filter dropdown) because an RT with zero
        // lansia participants today would otherwise be impossible to
        // pick when adding its first one.
        $data['rtPilihan']   = $this->rtModel->whereIn('id_rt', $idRts)->orderBy('nama')->findAll();

        $isi = $this->request->getGet('isi');
        $data['autoOpenWarga'] = ($isi !== null && ctype_digit((string) $isi)) ? (int) $isi : null;

        return $this->loadViews('admin/kegiatan_kesehatan', $this->global, $data);
    }

    /**
     * Excel (HTML-table) export of the posyandu/posbindu lansia rekap for
     * this kegiatan: header info block, participants grouped by RT, and
     * the BB/TB/LP/TD/GDS/AU/KOL result columns - the same print-ready
     * layout as exportPdf(). Pass `?rt=<id_rt>` to export just one RT;
     * omitted (or an RT outside the caller's scope) exports every RT the
     * caller may see ("gabungan").
     */
    public function exportExcel($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts       = $this->authorizedRtIds($kegiatan);
        $targetRtIds = $this->scopeRtIds($idRts);
        $catatan     = $this->catatanModel->byKegiatan((int) $idKegiatan);

        return view('admin/export_kesehatan', $this->buildRekap($kegiatan, $idRts, $targetRtIds, $catatan));
    }

    /**
     * Print-friendly (browser "save as PDF") version of the same rekap as
     * exportExcel() - same grouping, same `?rt=` scoping.
     */
    public function exportPdf($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts       = $this->authorizedRtIds($kegiatan);
        $targetRtIds = $this->scopeRtIds($idRts);
        $catatan     = $this->catatanModel->byKegiatan((int) $idKegiatan);

        return view('admin/cetak_rekap_kesehatan', $this->buildRekap($kegiatan, $idRts, $targetRtIds, $catatan));
    }

    /**
     * RT id(s) an export actually covers: the single RT named by `?rt=`
     * when it's one the caller is authorized for, otherwise every
     * authorized RT ("gabungan"). Shared by exportExcel() and exportPdf()
     * so a bookmarked/shared export URL behaves identically in both
     * formats.
     *
     * @param int[] $authorizedRtIds
     * @return int[]
     */
    private function scopeRtIds(array $authorizedRtIds): array
    {
        $rtParam = $this->request->getGet('rt');
        if ($rtParam !== null && ctype_digit((string) $rtParam)) {
            $idRt = (int) $rtParam;
            if (in_array($idRt, $authorizedRtIds, true)) {
                return [$idRt];
            }
        }

        return $authorizedRtIds;
    }

    /**
     * Shared payload for the Excel and PDF rekap exports: participants who
     * actually have a kesehatan_catatan row for this kegiatan (i.e. were
     * measured or at least added - not the full lansia roster, unlike the
     * on-screen data-entry table), grouped by RT and sorted by name within
     * each group.
     *
     * @param int[]               $authorizedRtIds every RT the caller may see, for the RT picker
     * @param int[]               $targetRtIds     RT(s) actually included in this export
     * @param array<int, object>  $catatan         keyed by id_warga, from KesehatanCatatanModel::byKegiatan()
     * @return array{
     *     kegiatan: object, kalurahan: string, scopeLabel: string, signLabel: string,
     *     groups: array<int, array{rt: object, items: object[]}>, totalPeserta: int,
     *     multiRt: bool, rtOptions: array<int, string>, currentRt: ?int,
     *     auDiperiksa: bool, kolDiperiksa: bool
     * }
     */
    private function buildRekap(object $kegiatan, array $authorizedRtIds, array $targetRtIds, array $catatan): array
    {
        // Every candidate (auto lansia + manually added) across the target
        // RTs, narrowed to those actually recorded for *this* kegiatan -
        // the recap is a results sheet, not the full resident roster.
        $peserta = array_filter(
            $this->pesertaForKegiatan($targetRtIds, $catatan),
            static fn ($p) => isset($catatan[(int) $p->id_warga])
        );

        $rtRows = $this->rtModel->whereIn('id_rt', $targetRtIds)->orderBy('nama')->findAll();

        $groups = [];
        $total  = 0;
        foreach ($rtRows as $rt) {
            $items = array_values(array_filter($peserta, static fn ($p) => (int) $p->id_rt === (int) $rt->id_rt));
            if (empty($items)) {
                continue;
            }
            usort($items, static fn ($a, $b) => strcasecmp($a->nama_warga, $b->nama_warga));
            $groups[] = ['rt' => $rt, 'items' => $items];
            $total += count($items);
        }

        // Every RT belongs to exactly one RW; read it off whichever RT row
        // is on hand rather than requiring kegiatan->id_rw (RT-owned
        // kegiatan leave that column null).
        $rw = !empty($rtRows) ? $this->rwModel->find((int) $rtRows[0]->id_rw) : null;

        $rtOptions = [];
        foreach ($this->rtModel->whereIn('id_rt', $authorizedRtIds)->orderBy('nama')->findAll() as $rt) {
            $rtOptions[(int) $rt->id_rt] = $rt->nama;
        }

        if (count($targetRtIds) === 1) {
            $namaRt     = $rtOptions[(int) $targetRtIds[0]] ?? '-';
            $scopeLabel = ($rw->nama ?? '-') . ' / ' . $namaRt;
            $signLabel  = 'Ketua ' . $namaRt . ',';
        } else {
            $scopeLabel = ($rw->nama ?? '-') . ' (Gabungan ' . implode(', ', array_map(static fn ($rt) => $rt->nama, $rtRows)) . ')';
            $signLabel  = 'Ketua ' . ($rw->nama ?? 'RW') . ',';
        }

        return [
            'kegiatan'     => $kegiatan,
            'kalurahan'    => self::KALURAHAN,
            'scopeLabel'   => $scopeLabel,
            'signLabel'    => $signLabel,
            'groups'       => $groups,
            'catatan'      => $catatan,
            'totalPeserta' => $total,
            'multiRt'      => count($targetRtIds) > 1,
            'rtOptions'    => $rtOptions,
            'currentRt'    => count($targetRtIds) === 1 ? (int) $targetRtIds[0] : null,
            'auDiperiksa'  => $this->anyFieldFilled($peserta, $catatan, 'asam_urat'),
            'kolDiperiksa' => $this->anyFieldFilled($peserta, $catatan, 'kolesterol'),
        ];
    }

    /** Whether any participant in $peserta has a non-null $field recorded, so the rekap can note "tidak diperiksa" instead of a wall of dashes when a measurement wasn't taken this session. */
    private function anyFieldFilled(array $peserta, array $catatan, string $field): bool
    {
        foreach ($peserta as $p) {
            $row = $catatan[(int) $p->id_warga] ?? null;
            if ($row !== null && $row->{$field} !== null && $row->{$field} !== '') {
                return true;
            }
        }

        return false;
    }

    /** Simple print-friendly (browser "save as PDF") record sheet for one participant. */
    public function cetakPdf($idKegiatan, $idWarga)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts = $this->authorizedRtIds($kegiatan);
        $warga = $this->wargaModel->oneByRtIds((int) $idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $catatan = $this->catatanModel->byKegiatan((int) $idKegiatan);
        $rt      = $this->rtModel->find((int) $warga->id_rt);

        $data['kegiatan'] = $kegiatan;
        $data['warga']    = $warga;
        $data['namaRt']   = $rt->nama ?? '-';
        $data['catatan']  = $catatan[(int) $idWarga] ?? null;
        $data['usia']     = (new \DateTime($warga->tanggal_lahir))->diff(new \DateTime())->y;

        return view('admin/cetak_kesehatan', $data);
    }

    /**
     * Participant list for a kegiatan: auto-filtered lansia, plus anyone
     * manually added via the "tambah peserta lain" modal. Shared by
     * kegiatan() and exportExcel() so the export always matches what's
     * shown on screen.
     *
     * @param int[]              $idRts
     * @param array<int, object> $catatan keyed by id_warga, from KesehatanCatatanModel::byKegiatan()
     * @return object[]
     */
    private function pesertaForKegiatan(array $idRts, array $catatan): array
    {
        $peserta   = $this->wargaModel->lansiaByRtIds($idRts);
        $lansiaIds = array_map(static fn ($w) => (int) $w->id_warga, $peserta);
        $manualIds = array_diff(array_map('intval', array_keys($catatan)), $lansiaIds);

        if (!empty($manualIds)) {
            $peserta = array_merge($peserta, $this->wargaModel->byIds(array_values($manualIds)));
            usort($peserta, static fn ($a, $b) => strcmp($a->nama_warga, $b->nama_warga));
        }

        return $peserta;
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

    /**
     * Quick-add a resident who isn't in `warga` at all yet (e.g. found
     * during posyandu but missing from the DB) with just a name, so
     * they can still get a kesehatan_catatan row for this kegiatan.
     * Everything else the schema requires but this form doesn't ask for
     * (nik, no_kk, tempat_lahir) gets a placeholder - see
     * WargaModel::createMinimal(). The admin can fill in full bio data
     * later via the regular "Ubah Warga" screen.
     */
    public function tambahWargaBaru($idKegiatan)
    {
        $kegiatan = $this->kegiatanModel->detailForCurrentScope((int) $idKegiatan);
        if ($kegiatan === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $nama = trim((string) $this->request->getPost('nama_warga'));
        if ($nama === '') {
            setFlashData('error', 'Nama wajib diisi.');
            return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
        }

        $usia = $this->request->getPost('usia');
        if (!ctype_digit((string) $usia)) {
            setFlashData('error', 'Perkiraan usia wajib diisi (boleh perkiraan).');
            return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan);
        }

        $idRts = $this->authorizedRtIds($kegiatan);
        $idRt  = (int) $this->request->getPost('id_rt');
        if (!in_array($idRt, $idRts, true)) {
            $idRt = $idRts[0];
        }

        $jenisKelamin = $this->request->getPost('jenis_kelamin');
        $jenisKelamin = in_array($jenisKelamin, ['L', 'P'], true) ? $jenisKelamin : null;

        $tanggalLahir = (new \DateTime())->modify('-' . (int) $usia . ' years')->format('Y-m-d');

        $idWarga = $this->wargaModel->createMinimal($nama, $jenisKelamin, $tanggalLahir, $this->pekerjaanModel->defaultId(), $idRt);

        $this->catatanModel->upsert((int) $idKegiatan, $idWarga, $idRt, [
            'id_user' => auth()->user()->id,
        ]);

        setFlashData('success', esc($nama) . ' ditambahkan sebagai warga baru. Lengkapi data lengkapnya nanti lewat menu Warga.');
        return redirect()->to('admin/kesehatan/kegiatan/' . $idKegiatan . '?isi=' . $idWarga);
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
     * kegiatan (RT-owned -> that one RT; RW-owned -> every RT in that RW -
     * but only for a caller who is actually RW-scoped). An RT-level admin
     * opening an RW/Gabungan kegiatan is narrowed to just their own RT:
     * they can see and record data for their own residents alongside the
     * joint event, but never another member RT's, matching the isolation
     * rule detailForCurrentScope() already applies to the kegiatan itself.
     *
     * @return int[]
     */
    private function authorizedRtIds(object $kegiatan): array
    {
        $fullScope = $kegiatan->id_rw !== null
            ? array_map(static fn ($r) => (int) $r->id_rt, $this->rtModel->byRw((int) $kegiatan->id_rw))
            : [(int) $kegiatan->id_rt];

        if (current_rw_id() !== null) {
            return $fullScope;
        }

        $ownRt = current_rt_id();
        return in_array($ownRt, $fullScope, true) ? [$ownRt] : $fullScope;
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
