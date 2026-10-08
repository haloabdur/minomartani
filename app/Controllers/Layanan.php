<?php

namespace App\Controllers;

use App\Libraries\PinThrottle;
use App\Libraries\SuratPemohon;
use App\Models\AlamatModel;
use App\Models\SuratModel;
use App\Models\WargaModel;

/**
 * Public request form for a Surat Keterangan, in three steps:
 *   1. verifikasi - pick the address and enter its PIN (alamat.kode_rumah)
 *   2. pilih      - pick which resident of that address is applying
 *   3. form/store - prefilled, fully editable form; submitted values are
 *                   stored as a snapshot on `surat` (never written to
 *                   `warga`) and the admin compares them with the RT data.
 *
 * The verified address lives in the session (bound to the tenant and
 * time-limited); steps 2 and 3 re-check it server-side instead of
 * trusting anything the browser sends.
 */
class Layanan extends BaseController
{
    private const SESSION_KEY = 'layanan_alamat';
    private const SESSION_TTL = 1800;

    // Same message for a wrong PIN, an unknown address and a locked address.
    private const MSG_PIN_SALAH = 'Alamat atau PIN Anda salah, atau terlalu banyak percobaan. Coba lagi beberapa saat lagi.';

    protected $suratModel;
    protected $wargaModel;
    protected $alamatModel;

    public function __construct()
    {
        $this->suratModel  = new SuratModel();
        $this->wargaModel  = new WargaModel();
        $this->alamatModel = new AlamatModel();
    }

    private function url(?string $slug, string $path): string
    {
        return empty($slug) ? $path : $slug . '/' . $path;
    }

    /** Verified address for the current tenant, or null (also when expired). */
    private function alamatTerverifikasi(): ?int
    {
        $state = session()->get(self::SESSION_KEY);

        if (
            ! is_array($state)
            || (int) ($state['id_rt'] ?? 0) !== (int) current_rt_id()
            || (int) ($state['exp'] ?? 0) < time()
        ) {
            session()->remove(self::SESSION_KEY);
            return null;
        }

        return (int) $state['id_alamat'];
    }

    public function index($slug = null)
    {
        $this->resolveTenant($slug);

        return $this->load_view('layanan', [
            'langkah' => 1,
            'alamats' => $this->alamatModel->pilihanLayanan(),
            'slug'    => $slug,
        ]);
    }

    public function verifikasi($slug = null)
    {
        $this->resolveTenant($slug);

        $idAlamat = (int) $this->request->getPost('id_alamat');
        $pin      = trim((string) $this->request->getPost('pin'));
        $idRt     = (int) current_rt_id();
        $throttle = new PinThrottle();

        if ($idAlamat < 1 || $pin === '' || $throttle->isLocked($idRt, $idAlamat)) {
            setFlashData('error', self::MSG_PIN_SALAH);
            return redirect()->to($this->url($slug, 'layanan'));
        }

        $alamat = $this->alamatModel->detail($idAlamat);
        $kode   = $alamat === null ? '' : (string) $alamat->kode_rumah;

        // A blank PIN on file never matches: fail closed.
        if ($alamat === null || $kode === '' || ! hash_equals($kode, $pin)) {
            if ($alamat !== null) {
                $throttle->recordFailure($idRt, $idAlamat);
            }
            setFlashData('error', self::MSG_PIN_SALAH);
            return redirect()->to($this->url($slug, 'layanan'));
        }

        $throttle->reset($idRt, $idAlamat);
        session()->regenerate();
        session()->set(self::SESSION_KEY, [
            'id_alamat' => $idAlamat,
            'id_rt'     => $idRt,
            'exp'       => time() + self::SESSION_TTL,
        ]);

        return redirect()->to($this->url($slug, 'layanan/pilih'));
    }

    public function pilih($slug = null)
    {
        $this->resolveTenant($slug);

        $idAlamat = $this->alamatTerverifikasi();
        if ($idAlamat === null) {
            setFlashData('error', 'Sesi habis. Silakan pilih alamat dan masukkan PIN lagi.');
            return redirect()->to($this->url($slug, 'layanan'));
        }

        return $this->load_view('layanan', [
            'langkah' => 2,
            'alamat'  => $this->alamatModel->detail($idAlamat),
            'anggota' => $this->wargaModel->anggotaAlamat($idAlamat),
            'slug'    => $slug,
        ]);
    }

    /** Route target for the unprefixed /layanan/form/(:num). */
    public function formDefault($idWarga)
    {
        return $this->form(null, $idWarga);
    }

    public function form($slug = null, $idWarga = null)
    {
        $this->resolveTenant($slug);

        $idAlamat = $this->alamatTerverifikasi();
        if ($idAlamat === null) {
            setFlashData('error', 'Sesi habis. Silakan pilih alamat dan masukkan PIN lagi.');
            return redirect()->to($this->url($slug, 'layanan'));
        }

        $warga = $this->wargaModel->untukLayanan((int) $idWarga, $idAlamat);
        if ($warga === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $pending = $this->suratModel->hasMenunggu((int) $warga->id_warga);
        if ($pending) {
            setFlashData('error', 'Pengajuan ' . $warga->nama_warga . ' sebelumnya masih menunggu persetujuan RT.');
            return redirect()->to($this->url($slug, 'layanan/pilih'));
        }

        return $this->load_view('layanan', [
            'langkah' => 3,
            'warga'   => $warga,
            'alamat'  => SuratPemohon::alamatRt($warga->alamat_lengkap, $warga->alamat),
            'slug'    => $slug,
        ]);
    }

    public function store($slug = null)
    {
        $this->resolveTenant($slug);

        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $idAlamat = $this->alamatTerverifikasi();
        if ($idAlamat === null) {
            setFlashData('error', 'Sesi habis. Silakan pilih alamat dan masukkan PIN lagi.');
            return redirect()->to($this->url($slug, 'layanan'));
        }

        $idWarga = (int) $this->request->getPost('id_warga');
        $warga   = $this->wargaModel->untukLayanan($idWarga, $idAlamat);
        if ($warga === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $formUrl = $this->url($slug, 'layanan/form/' . $idWarga);

        $rules = [
            'nama_pemohon'   => 'required|max_length[255]',
            'nik_pemohon'    => 'required|max_length[50]',
            'alamat_pemohon' => 'required|max_length[255]',
            'tempat_lahir'   => 'required|max_length[50]',
            'tanggal_lahir'  => 'required|valid_date[Y-m-d]',
            'agama'          => 'required|max_length[30]',
            'no_hp'          => 'required|max_length[20]',
            'maksut'         => 'required|max_length[100]',
            'perlu'          => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            setFlashData('error', 'Data belum lengkap atau terlalu panjang. Periksa kembali isian Anda.');
            return redirect()->to($formUrl)->withInput();
        }

        $lampiran = SuratPemohon::cleanLampiran($this->request->getPost('lampiran'));
        if ($lampiran === []) {
            setFlashData('error', 'Isi minimal satu berkas lampiran.');
            return redirect()->to($formUrl)->withInput();
        }

        $noHp = SuratPemohon::normalizePhone($this->request->getPost('no_hp'));
        if (strlen($noHp) < 8) {
            setFlashData('error', 'Nomor HP tidak valid.');
            return redirect()->to($formUrl)->withInput();
        }

        if ($this->suratModel->hasMenunggu($idWarga)) {
            setFlashData('error', 'Pengajuan sebelumnya masih menunggu persetujuan RT.');
            return redirect()->to($this->url($slug, 'layanan/pilih'));
        }

        $post = $this->request->getPost();

        $this->suratModel->insert([
            'id_warga'       => $idWarga,
            'id_rt'          => current_rt_id(),
            'status_surat'   => SuratModel::STATUS_MENUNGGU,
            'maksut'         => trim($post['maksut']),
            'perlu'          => trim($post['perlu']),
            'lampiran'       => SuratPemohon::encodeLampiran($lampiran),
            'nama_pemohon'   => trim($post['nama_pemohon']),
            'nik_pemohon'    => trim($post['nik_pemohon']),
            'alamat_pemohon' => trim($post['alamat_pemohon']),
            'tempat_lahir'   => trim($post['tempat_lahir']),
            'tanggal_lahir'  => $post['tanggal_lahir'],
            'agama'          => trim($post['agama']),
            'no_hp'          => $noHp,
        ]);

        $idSurat = (int) $this->suratModel->getInsertID();

        // Done with this address for now: make the next request start
        // from the PIN step again.
        session()->remove(self::SESSION_KEY);
        setFlashData('no_pengajuan', (string) $idSurat);

        return redirect()->to($this->url($slug, 'layanan/sukses'));
    }

    public function sukses($slug = null)
    {
        $this->resolveTenant($slug);

        return $this->load_view('layanan_sukses', ['slug' => $slug]);
    }
}
