<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PekerjaanModel;
use App\Models\PresensiAcaraModel;
use App\Models\PresensiKehadiranModel;
use App\Models\RtModel;
use App\Models\RwModel;
use App\Models\WargaModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Attendance ("presensi") for RT/RW events. Deliberately shaped like
 * Admin\Kesehatan - same acara-then-detail flow, same RT/RW ownership and
 * read-only rules, same e-KTP (RFID) scanning - but the per-resident data
 * entry is reduced to a single choice: hadir or tidak hadir.
 */
class Presensi extends BaseController
{
    protected $acaraModel;
    protected $kehadiranModel;
    protected $wargaModel;
    protected $rtModel;
    protected $rwModel;
    protected $pekerjaanModel;

    /**
     * Per-request memoization of RtModel::byRw() lookups, keyed by id_rw -
     * authorizedRtIds() and printScopeRtIds() both resolve "every RT under
     * this acara's RW" and are commonly called back-to-back for the same
     * acara, so this avoids issuing the same query twice.
     *
     * @var array<int, object[]>
     */
    private array $rwMembersCache = [];

    /**
     * Kelurahan shown on the printed/exported rekap header. Not a DB
     * column anywhere - this app is built specifically for RT/RW units
     * inside Kelurahan Minomartani (see CLAUDE.md), so it's a fixed label
     * rather than per-tenant data.
     */
    private const KALURAHAN = 'Minomartani';

    public function __construct()
    {
        $this->acaraModel     = new PresensiAcaraModel();
        $this->kehadiranModel = new PresensiKehadiranModel();
        $this->wargaModel     = new WargaModel();
        $this->rtModel        = new RtModel();
        $this->rwModel        = new RwModel();
        $this->pekerjaanModel = new PekerjaanModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Presensi Acara';
        $data['acaras'] = $this->acaraModel->forCurrentScope();
        return $this->loadViews('admin/presensi', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Acara';
        return $this->loadViews('admin/tambah_acara', $this->global, []);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw PageNotFoundException::forPageNotFound();
        }

        $namaAcara    = trim((string) $this->request->getPost('nama_acara'));
        $tanggalAcara = trim((string) $this->request->getPost('tanggal_acara'));

        if ($namaAcara === '' || $tanggalAcara === '') {
            setFlashData('error', 'Nama acara dan tanggal wajib diisi!');
            return redirect()->to(back());
        }

        $data = [
            'nama_acara'    => $namaAcara,
            'tanggal_acara' => $tanggalAcara,
            'tempat'        => $this->emptyToNull($this->request->getPost('tempat')),
            'catatan'       => $this->request->getPost('catatan'),
            'id_user'       => auth()->user()->id,
        ];

        $rwId = current_rw_id();
        if ($rwId !== null) {
            $data['id_rw'] = $rwId;
        } else {
            $data['id_rt'] = current_rt_id();
        }

        $idAcara = $this->acaraModel->insert($data);
        setFlashData('success', 'Acara berhasil dibuat, silakan mulai presensi.');
        return redirect()->to('admin/presensi/acara/' . $idAcara);
    }

    public function editAcara($id)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $id);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isReadOnlyForCaller($acara)) {
            setFlashData('error', 'Acara gabungan RW hanya bisa diubah oleh RW, RT hanya bisa melihat.');
            return redirect()->to('admin/presensi/acara/' . $acara->id_acara);
        }

        $this->global['pageTitle'] = 'Ubah Acara';
        $data['acara'] = $acara;
        return $this->loadViews('admin/ubah_acara', $this->global, $data);
    }

    public function updateAcara($id)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $id);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isReadOnlyForCaller($acara)) {
            setFlashData('error', 'Acara gabungan RW hanya bisa diubah oleh RW, RT hanya bisa melihat.');
            return redirect()->to('admin/presensi/acara/' . $acara->id_acara);
        }

        $namaAcara    = trim((string) $this->request->getPost('nama_acara'));
        $tanggalAcara = trim((string) $this->request->getPost('tanggal_acara'));

        if ($namaAcara === '' || $tanggalAcara === '') {
            setFlashData('error', 'Nama acara dan tanggal wajib diisi!');
            return redirect()->to(back());
        }

        $this->acaraModel->update($acara->id_acara, [
            'nama_acara'    => $namaAcara,
            'tanggal_acara' => $tanggalAcara,
            'tempat'        => $this->emptyToNull($this->request->getPost('tempat')),
            'catatan'       => $this->request->getPost('catatan'),
        ]);

        setFlashData('success', 'Acara berhasil diubah!');
        return redirect()->to('admin/presensi/acara/' . $acara->id_acara);
    }

    /**
     * The attendance screen: every resident in the caller's authorized
     * RT(s) with a hadir / tidak hadir toggle. Unlike Kesehatan there's no
     * auto-filtered subset - an acara is open to all residents, so the
     * roster IS the participant list.
     */
    public function acara($id)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $id);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts     = $this->authorizedRtIds($acara);
        $kehadiran = $this->kehadiranModel->byAcara((int) $id);

        $this->global['pageTitle'] = 'Presensi: ' . $acara->nama_acara;
        $data['acara']     = $acara;
        $data['peserta']   = $this->wargaModel->allByRtIds($idRts);
        $data['kehadiran'] = $kehadiran;
        $data['multiRt']   = count($idRts) > 1;
        $data['readOnly']  = $this->isReadOnlyForCaller($acara);

        // Only a plain RT admin viewing a joint RW acara ends up narrowed
        // (readOnly === true): $peserta above is already just their own
        // RT's slice. Fetch the RW-wide roster too so the recap can show
        // both "keseluruhan RW" and "RT ini" side by side.
        if ($data['readOnly']) {
            $data['pesertaRw'] = $this->wargaModel->allByRtIds($this->printScopeRtIds($acara));
        }

        // Every RT the caller may add a resident into, for the "tambah
        // warga baru" picker.
        $data['rtPilihan'] = $this->rtModel->whereIn('id_rt', $idRts)->orderBy('nama')->findAll();

        return $this->loadViews('admin/acara_presensi', $this->global, $data);
    }

    /**
     * Mark one resident hadir / tidak hadir. Answers JSON for the in-page
     * buttons (so a long roster doesn't reload and lose scroll position on
     * every tap) and falls back to a plain redirect for a non-AJAX post.
     */
    public function tandai($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            return $this->respondTandai(404, ['status' => 'error', 'message' => 'Acara tidak ditemukan.'], $idAcara, 'error');
        }

        if ($this->isReadOnlyForCaller($acara)) {
            return $this->respondTandai(403, ['status' => 'error', 'message' => 'Acara gabungan RW bersifat baca-saja untuk RT.'], $idAcara, 'error');
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        $status  = (string) $this->request->getPost('status');

        if (! in_array($status, PresensiKehadiranModel::STATUSES, true)) {
            return $this->respondTandai(400, ['status' => 'error', 'message' => 'Status presensi tidak dikenali.'], $idAcara, 'error');
        }

        $warga = $this->wargaModel->oneByRtIds($idWarga, $this->authorizedRtIds($acara));
        if ($warga === null) {
            return $this->respondTandai(404, ['status' => 'error', 'message' => 'Warga tidak ditemukan di acara ini.'], $idAcara, 'error');
        }

        $row = $this->kehadiranModel->upsert(
            (int) $idAcara,
            $idWarga,
            (int) $warga->id_rt,
            $status,
            (int) auth()->user()->id
        );

        $label = $status === 'hadir' ? 'hadir' : 'tidak hadir';

        return $this->respondTandai(200, [
            'status'     => 'ok',
            'idWarga'    => $idWarga,
            'idPresensi' => (int) $row->id_presensi,
            'kehadiran'  => $row->status,
            'waktu'      => $row->waktu,
            'message'    => esc($warga->nama_warga) . ' ditandai ' . $label . '.',
        ], $idAcara, 'success');
    }

    /**
     * Clear one resident's mark, putting them back to "belum dipresensi"
     * (which is a distinct state from "tidak hadir" - see the
     * presensi_kehadiran migration).
     */
    public function hapusKehadiran($idAcara, $idPresensi)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isReadOnlyForCaller($acara)) {
            setFlashData('error', 'Acara gabungan RW bersifat baca-saja untuk RT.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        // whereIn on the caller's own RT ids too: a narrowed RT admin must
        // not be able to clear another member RT's row on a joint acara by
        // guessing an id_presensi.
        $this->kehadiranModel
            ->where('id_presensi', $idPresensi)
            ->where('id_acara', $idAcara)
            ->whereIn('id_rt', $this->authorizedRtIds($acara))
            ->delete();

        setFlashData('success', 'Presensi berhasil dibatalkan.');
        return redirect()->to('admin/presensi/acara/' . $idAcara);
    }

    /**
     * Quick-add someone who isn't in `warga` at all yet (e.g. a new
     * resident who turns up at the meeting) with just a name, so they can
     * still be marked present. Everything else the schema requires but
     * this form doesn't ask for gets a placeholder - see
     * WargaModel::createMinimal(). Mirrors Kesehatan::tambahWargaBaru().
     */
    public function tambahWargaBaru($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isReadOnlyForCaller($acara)) {
            setFlashData('error', 'Acara gabungan RW bersifat baca-saja untuk RT.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $nama = trim((string) $this->request->getPost('nama_warga'));
        if ($nama === '') {
            setFlashData('error', 'Nama wajib diisi.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $usia = $this->request->getPost('usia');
        if (! ctype_digit((string) $usia)) {
            setFlashData('error', 'Perkiraan usia wajib diisi (boleh perkiraan).');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $idRts = $this->authorizedRtIds($acara);
        $idRt  = (int) $this->request->getPost('id_rt');
        if (! in_array($idRt, $idRts, true)) {
            $idRt = $idRts[0];
        }

        $jenisKelamin = $this->request->getPost('jenis_kelamin');
        $jenisKelamin = in_array($jenisKelamin, ['L', 'P'], true) ? $jenisKelamin : null;

        $tanggalLahir = (new \DateTime())->modify('-' . (int) $usia . ' years')->format('Y-m-d');

        $idWarga = $this->wargaModel->createMinimal($nama, $jenisKelamin, $tanggalLahir, $this->pekerjaanModel->defaultId(), $idRt);

        // They were added because they're standing right there, so mark
        // them hadir straight away rather than making it a second click.
        $this->kehadiranModel->upsert((int) $idAcara, $idWarga, $idRt, 'hadir', (int) auth()->user()->id);

        setFlashData('success', esc($nama) . ' ditambahkan sebagai warga baru dan ditandai hadir. Lengkapi data lengkapnya nanti lewat menu Warga.');
        return redirect()->to('admin/presensi/acara/' . $idAcara);
    }

    /**
     * AJAX lookup: RFID scanner reads a card's chip UID and posts it here
     * (GET, no CSRF - the write it performs is the idempotent "mark this
     * card's owner hadir") so tapping an e-KTP presences someone in one
     * motion. Only resolves for residents already enrolled via
     * daftarRfid() - the UID alone doesn't identify a resident until
     * linked once.
     */
    public function scanRfid($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Acara tidak ditemukan.']);
        }

        if ($this->isReadOnlyForCaller($acara)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Acara gabungan RW bersifat baca-saja untuk RT.']);
        }

        $kode = $this->normalizeRfidCode((string) $this->request->getGet('kode'));
        if ($kode === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Kode kartu kosong.']);
        }

        $warga = $this->wargaModel->oneByRfidAndRtIds($kode, $this->authorizedRtIds($acara));

        if ($warga === null) {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        $row = $this->kehadiranModel->upsert(
            (int) $idAcara,
            (int) $warga->id_warga,
            (int) $warga->id_rt,
            'hadir',
            (int) auth()->user()->id
        );

        return $this->response->noCache()->setJSON([
            'status' => 'found',
            'warga'  => [
                'idWarga' => (int) $warga->id_warga,
                'nama'    => $warga->nama_warga,
            ],
            'presensi' => [
                'idPresensi' => (int) $row->id_presensi,
                'kehadiran'  => $row->status,
                'waktu'      => $row->waktu,
            ],
        ]);
    }

    /**
     * Enrolls a scanned card UID that didn't match anyone: admin picks the
     * owning warga from a search list, and that pairing is saved so future
     * scans of the same card resolve automatically via scanRfid(). Plain
     * form POST + redirect (not AJAX), matching this app's convention.
     */
    public function daftarRfid($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isReadOnlyForCaller($acara)) {
            setFlashData('error', 'Acara gabungan RW bersifat baca-saja untuk RT.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $kode    = $this->normalizeRfidCode((string) $this->request->getPost('kode_rfid'));
        $idWarga = (int) $this->request->getPost('id_warga');

        if ($kode === '' || $idWarga <= 0) {
            setFlashData('error', 'Data kartu tidak lengkap, silakan scan ulang.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $idRts = $this->authorizedRtIds($acara);
        $warga = $this->wargaModel->oneByRtIds($idWarga, $idRts);
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $existingOwner = $this->wargaModel->oneByRfidAndRtIds($kode, $idRts);
        if ($existingOwner !== null && (int) $existingOwner->id_warga !== $idWarga) {
            setFlashData('error', 'Kartu ini sudah terdaftar untuk warga lain (' . esc($existingOwner->nama_warga) . ').');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        try {
            $this->wargaModel->update($idWarga, ['kode_rfid' => $kode]);
        } catch (DatabaseException $e) {
            // Unique constraint hit - card already linked to a resident
            // outside this scope. Generic message: don't leak cross-tenant
            // resident names.
            setFlashData('error', 'Kartu ini sudah terdaftar untuk warga lain.');
            return redirect()->to('admin/presensi/acara/' . $idAcara);
        }

        $this->kehadiranModel->upsert((int) $idAcara, $idWarga, (int) $warga->id_rt, 'hadir', (int) auth()->user()->id);

        setFlashData('success', 'Kartu berhasil didaftarkan untuk ' . esc($warga->nama_warga) . ' dan ditandai hadir.');
        return redirect()->to('admin/presensi/acara/' . $idAcara);
    }

    /**
     * Excel (HTML-table) export of the attendance sheet for this acara:
     * header info block, residents grouped by RT, hadir/tidak column.
     * Pass `?rt=<id_rt>` to export just one RT; omitted (or an RT outside
     * the caller's scope) exports every RT the caller may see ("gabungan").
     */
    public function exportExcel($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts       = $this->authorizedRtIds($acara);
        $targetRtIds = $this->scopeRtIds($idRts);

        return view('admin/export_presensi', $this->buildRekap($acara, $idRts, $targetRtIds));
    }

    /**
     * Print-friendly (browser "save as PDF") version of the same
     * attendance sheet as exportExcel() - same grouping, same `?rt=`
     * scoping.
     */
    public function exportPdf($idAcara)
    {
        $acara = $this->acaraModel->detailForCurrentScope((int) $idAcara);
        if ($acara === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $idRts       = $this->authorizedRtIds($acara);
        $targetRtIds = $this->scopeRtIds($idRts);

        return view('admin/cetak_rekap_presensi', $this->buildRekap($acara, $idRts, $targetRtIds));
    }

    /** One resident's attendance history across every acara. */
    public function warga($idWarga)
    {
        $warga = $this->wargaModel->oneByRtIds((int) $idWarga, $this->authorizedRtIdsForScope());
        if ($warga === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Riwayat Presensi: ' . $warga->nama_warga;
        $data['warga']   = $warga;
        $data['riwayat'] = $this->kehadiranModel->forWarga((int) $idWarga);

        return $this->loadViews('admin/presensi_warga', $this->global, $data);
    }

    /**
     * Shared payload for the Excel and PDF exports. Unlike Kesehatan's
     * results sheet, the attendance sheet lists EVERY resident in scope -
     * the people with no row are exactly the information the sheet is
     * meant to convey ("belum hadir") - grouped by RT and sorted by name.
     *
     * @param int[] $authorizedRtIds every RT the caller may see, for the RT picker
     * @param int[] $targetRtIds     RT(s) actually included in this export
     * @return array{
     *     acara: object, kalurahan: string, scopeLabel: string, signLabel: string,
     *     groups: array<int, array{rt: object, items: object[]}>, kehadiran: array<int, object>,
     *     totalPeserta: int, totalHadir: int, totalTidak: int, totalBelum: int,
     *     multiRt: bool, rtOptions: array<int, string>, currentRt: ?int
     * }
     */
    private function buildRekap(object $acara, array $authorizedRtIds, array $targetRtIds): array
    {
        $peserta   = $this->wargaModel->allByRtIds($targetRtIds);
        $kehadiran = $this->kehadiranModel->byAcara((int) $acara->id_acara);

        $rtRows = $this->rtModel->whereIn('id_rt', $targetRtIds)->orderBy('nama')->findAll();

        $groups = [];
        $total  = 0;
        $hadir  = 0;
        $tidak  = 0;
        foreach ($rtRows as $rt) {
            $items = array_values(array_filter($peserta, static fn ($p) => (int) $p->id_rt === (int) $rt->id_rt));
            if (empty($items)) {
                continue;
            }
            usort($items, static fn ($a, $b) => strcasecmp($a->nama_warga, $b->nama_warga));
            $groups[] = ['rt' => $rt, 'items' => $items];
            $total += count($items);

            foreach ($items as $p) {
                $row = $kehadiran[(int) $p->id_warga] ?? null;
                if ($row === null) {
                    continue;
                }
                if ($row->status === 'hadir') {
                    $hadir++;
                } else {
                    $tidak++;
                }
            }
        }

        // Every RT belongs to exactly one RW; read it off whichever RT row
        // is on hand rather than requiring acara->id_rw (RT-owned acara
        // leave that column null).
        $rw = ! empty($rtRows) ? $this->rwModel->find((int) $rtRows[0]->id_rw) : null;

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
            'acara'        => $acara,
            'kalurahan'    => self::KALURAHAN,
            'scopeLabel'   => $scopeLabel,
            'signLabel'    => $signLabel,
            'groups'       => $groups,
            'kehadiran'    => $kehadiran,
            'totalPeserta' => $total,
            'totalHadir'   => $hadir,
            'totalTidak'   => $tidak,
            'totalBelum'   => $total - $hadir - $tidak,
            'multiRt'      => count($targetRtIds) > 1,
            'rtOptions'    => $rtOptions,
            'currentRt'    => count($targetRtIds) === 1 ? (int) $targetRtIds[0] : null,
        ];
    }

    /**
     * RT id(s) an export actually covers: the single RT named by `?rt=`
     * when it's one the caller is authorized for, otherwise every
     * authorized RT ("gabungan").
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
     * JSON for an AJAX tandai() call, plain flash + redirect otherwise, so
     * the toggle still works with JavaScript disabled. The JSON carries a
     * fresh CSRF hash because Config\Security::$regenerate is on - without
     * it the second tap in a row would be rejected.
     */
    private function respondTandai(int $statusCode, array $payload, $idAcara, string $flashType)
    {
        if ($this->request->isAJAX()) {
            $payload['csrfHash'] = csrf_hash();

            return $this->response->setStatusCode($statusCode)->noCache()->setJSON($payload);
        }

        setFlashData($flashType, $payload['message'] ?? '');

        return redirect()->to('admin/presensi/acara/' . $idAcara);
    }

    /**
     * True when the acara is RW-owned but the caller is only RT-scoped
     * (plain RT admin, not an 'rw' account and not superadmin) - RT
     * participation in a joint RW event is view/print-only.
     */
    private function isReadOnlyForCaller(object $acara): bool
    {
        return $acara->id_rw !== null && current_rw_id() === null && ! auth()->user()->inGroup('superadmin');
    }

    /**
     * RT ids the caller may act within, given an already-authorized acara
     * (RT-owned -> that one RT; RW-owned -> every RT in that RW - but only
     * for a caller who is actually RW-scoped, or superadmin). A plain
     * RT-level admin opening an RW/gabungan acara is ALWAYS narrowed to
     * just their own RT. Fails closed: if their own RT is missing from
     * printScopeRtIds() (e.g. deactivated mid-session), the result is
     * "just that RT id, matching zero residents", never "every member RT".
     *
     * @return int[]
     */
    private function authorizedRtIds(object $acara): array
    {
        if (current_rw_id() !== null || auth()->user()->inGroup('superadmin')) {
            return $this->printScopeRtIds($acara);
        }

        return [current_rt_id()];
    }

    /**
     * Every RT id under the acara's scope (RW-owned -> all member RTs;
     * RT-owned -> just that RT), unnarrowed by the caller's own RT. Used
     * only for read-only purposes - here, the RW-wide recap numbers shown
     * alongside the RT-scoped ones on acara().
     *
     * @return int[]
     */
    private function printScopeRtIds(object $acara): array
    {
        if ($acara->id_rw === null) {
            return [(int) $acara->id_rt];
        }

        $idRw = (int) $acara->id_rw;
        if (! isset($this->rwMembersCache[$idRw])) {
            $this->rwMembersCache[$idRw] = $this->rtModel->byRw($idRw);
        }

        return array_map(static fn ($r) => (int) $r->id_rt, $this->rwMembersCache[$idRw]);
    }

    /**
     * RT ids the caller may act within, based on session scope alone
     * (no specific acara row involved - used by warga()).
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
