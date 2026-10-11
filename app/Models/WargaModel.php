<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;

class WargaModel extends Model
{
    use Auditable;

    protected string $auditModule        = 'warga';
    protected string $auditLabelField    = 'nama_warga';
    protected array  $auditIgnoredFields = ['id_alamat', 'id_rt'];

    protected $table         = 'warga';
    protected $primaryKey    = 'id_warga';
    protected $allowedFields = [
        'nama_warga',
        'no_kk',
        'id_alamat',
        'alamat_lengkap',
        'nik',
        'kode_rfid',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'gol_darah',
        'agama',
        'pendidikan',
        'id_pekerjaan',
        'status_kawin',
        'tanggal_kawin',
        'id_status_keluarga',
        'ayah',
        'ibu',
        'no_hp',
        'email',
        'status_warga',
        'id_status_penduduk',
        'is_hidup',
        'sumber_air',
        'id_rt'
    ];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk', 'left')
            ->where('warga.id_rt', current_rt_id())
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->get()->getResult();
    }

    /**
     * Warga rows across a set of RTs, for cross-tenant recap aggregation
     * (RW recap screen). Callers must already have authorized access to
     * every id in $idRts - this bypasses the single-tenant id_rt filter.
     *
     * @param int[] $idRts
     */
    public function byRtIds(array $idRts): array
    {
        if (empty($idRts)) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('warga.id_rt, warga.id_warga, warga.nama_warga, warga.no_kk, warga.jenis_kelamin, warga.tanggal_lahir, warga.pendidikan, warga.gol_darah, warga.agama, warga.status_kawin, warga.id_status_keluarga, warga.id_status_penduduk, warga.sumber_air, warga.id_pekerjaan, pekerjaan.nama_pekerjaan, status_penduduk.status as status_penduduk, status_keluarga.status as status_keluarga')
            ->join('pekerjaan', 'pekerjaan.id_pekerjaan = warga.id_pekerjaan', 'left')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk', 'left')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->where('status_warga', 1)
            ->whereIn('warga.id_rt', $idRts)
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('id_warga', $id)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getRow();
    }

    /**
     * Silsilah pohon keluarga (multi-generasi terhubung):
     * - Seluruh anggota satu No. KK
     * - Orang tua (Ayah/Ibu) dari warga baik via numeric id_warga maupun teks manual
     * - Anak kandung lintas KK (yang mencatat warga ini sebagai ayah/ibu)
     *
     * @param int|string $id
     * @return array
     */
    public function getFamilyTree($id): array
    {
        $rtId = current_rt_id();

        $query = $this->db->table($this->table)
            ->select('warga.*, status_keluarga.status as status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->where('warga.id_warga', $id);

        if ($rtId !== null) {
            $query->where('warga.id_rt', $rtId);
        }

        $current = $query->get()->getRow();

        if (!$current) {
            return [
                'current_id' => (int) $id,
                'nodes'      => [],
                'edges'      => [],
                'has_data'   => false,
            ];
        }

        $residents = [];
        $residents[(int) $current->id_warga] = $current;

        // 1. Seluruh warga dalam No. KK yang sama
        if (!empty($current->no_kk) && trim($current->no_kk) !== '-' && trim($current->no_kk) !== '0') {
            $kkQuery = $this->db->table($this->table)
                ->select('warga.*, status_keluarga.status as status_keluarga')
                ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
                ->where('warga.no_kk', $current->no_kk);

            if ($rtId !== null) {
                $kkQuery->where('warga.id_rt', $rtId);
            }

            $kkRows = $kkQuery->orderBy('warga.id_status_keluarga ASC, warga.tanggal_lahir ASC')->get()->getResult();
            foreach ($kkRows as $r) {
                $residents[(int) $r->id_warga] = $r;
            }
        }

        // 2. Orang tua dari warga yang sedang dilihat (jika numeric ID warga di RT yang sama)
        foreach (['ayah', 'ibu'] as $pCol) {
            $pVal = $current->{$pCol} ?? null;
            if (!empty($pVal) && ctype_digit((string) $pVal)) {
                $pId = (int) $pVal;
                if (!isset($residents[$pId])) {
                    $pQuery = $this->db->table($this->table)
                        ->select('warga.*, status_keluarga.status as status_keluarga')
                        ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
                        ->where('warga.id_warga', $pId);
                    if ($rtId !== null) {
                        $pQuery->where('warga.id_rt', $rtId);
                    }
                    $pRow = $pQuery->get()->getRow();
                    if ($pRow) {
                        $residents[$pId] = $pRow;
                    }
                }
            }
        }

        // 3. Anak kandung lintas KK (warga lain yang mencatat warga ini sebagai ayah atau ibu)
        $childQuery = $this->db->table($this->table)
            ->select('warga.*, status_keluarga.status as status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->groupStart()
                ->where('warga.ayah', (string) $id)
                ->orWhere('warga.ibu', (string) $id)
            ->groupEnd();

        if ($rtId !== null) {
            $childQuery->where('warga.id_rt', $rtId);
        }

        $childRows = $childQuery->get()->getResult();
        foreach ($childRows as $cr) {
            $residents[(int) $cr->id_warga] = $cr;
        }

        $nodes = [];
        $edges = [];

        // Penanganan Orang Tua Manual / Teks (Luar RT)
        if (!empty($current->ayah) && !ctype_digit((string) $current->ayah) && trim($current->ayah) !== '-') {
            $mAyahId = 'manual-ayah-' . $current->id_warga;
            $nodes[$mAyahId] = [
                'id'              => $mAyahId,
                'id_warga'        => null,
                'nama'            => trim($current->ayah),
                'nik'             => '-',
                'no_kk'           => '-',
                'jenis_kelamin'   => 'L',
                'status_keluarga' => 'Ayah (Luar RT)',
                'is_hidup'        => 1,
                'is_current'      => false,
                'is_manual'       => true,
                'url'             => null,
            ];
            $edges[] = ['from' => $mAyahId, 'to' => 'w-' . $current->id_warga, 'type' => 'parent'];
        }

        if (!empty($current->ibu) && !ctype_digit((string) $current->ibu) && trim($current->ibu) !== '-') {
            $mIbuId = 'manual-ibu-' . $current->id_warga;
            $nodes[$mIbuId] = [
                'id'              => $mIbuId,
                'id_warga'        => null,
                'nama'            => trim($current->ibu),
                'nik'             => '-',
                'no_kk'           => '-',
                'jenis_kelamin'   => 'P',
                'status_keluarga' => 'Ibu (Luar RT)',
                'is_hidup'        => 1,
                'is_current'      => false,
                'is_manual'       => true,
                'url'             => null,
            ];
            $edges[] = ['from' => $mIbuId, 'to' => 'w-' . $current->id_warga, 'type' => 'parent'];
        }

        if (isset($nodes['manual-ayah-' . $current->id_warga]) && isset($nodes['manual-ibu-' . $current->id_warga])) {
            $edges[] = [
                'from' => 'manual-ayah-' . $current->id_warga,
                'to'   => 'manual-ibu-' . $current->id_warga,
                'type' => 'spouse',
            ];
        }

        // Tambahkan seluruh warga terdaftar sebagai node
        foreach ($residents as $rId => $res) {
            $key = 'w-' . $rId;
            $nodes[$key] = [
                'id'              => $key,
                'id_warga'        => $rId,
                'nama'            => $res->nama_warga,
                'nik'             => $res->nik,
                'no_kk'           => $res->no_kk,
                'jenis_kelamin'   => strtoupper(trim($res->jenis_kelamin ?? 'L')),
                'status_keluarga' => $res->status_keluarga ?: 'Warga',
                'is_hidup'        => (int) ($res->is_hidup ?? 1),
                'is_current'      => ($rId === (int) $id),
                'is_manual'       => false,
                'url'             => base_url('admin/warga/view/' . $rId),
            ];
        }

        // 4. Hubungan orang tua - anak via kolom ayah / ibu
        foreach ($residents as $rId => $res) {
            $cKey = 'w-' . $rId;
            if (!empty($res->ayah) && ctype_digit((string) $res->ayah)) {
                $pKey = 'w-' . ((int) $res->ayah);
                if (isset($nodes[$pKey])) {
                    $edges[] = ['from' => $pKey, 'to' => $cKey, 'type' => 'parent'];
                }
            }
            if (!empty($res->ibu) && ctype_digit((string) $res->ibu)) {
                $mKey = 'w-' . ((int) $res->ibu);
                if (isset($nodes[$mKey])) {
                    $edges[] = ['from' => $mKey, 'to' => $cKey, 'type' => 'parent'];
                }
            }
        }

        // 5. Hubungan dalam 1 KK (Kepala Keluarga <-> Pasangan & Anak-anak)
        $kkGroups = [];
        foreach ($residents as $rId => $res) {
            if (!empty($res->no_kk) && trim($res->no_kk) !== '-' && trim($res->no_kk) !== '0') {
                $kkGroups[$res->no_kk][] = $res;
            }
        }

        foreach ($kkGroups as $noKk => $members) {
            $kkHead = null;
            $kkSpouse = null;
            $kkChildren = [];

            foreach ($members as $m) {
                $st = strtolower($m->status_keluarga ?? '');
                if ((int) ($m->id_status_keluarga ?? 0) === 1 || str_contains($st, 'kepala keluarga')) {
                    $kkHead = $m;
                } elseif (str_contains($st, 'istri') || str_contains($st, 'suami')) {
                    $kkSpouse = $m;
                } elseif (str_contains($st, 'anak')) {
                    $kkChildren[] = $m;
                }
            }

            if ($kkHead && $kkSpouse) {
                $edges[] = [
                    'from' => 'w-' . $kkHead->id_warga,
                    'to'   => 'w-' . $kkSpouse->id_warga,
                    'type' => 'spouse',
                ];
            }

            // Jika ada anak yang belum terhubung relasi orang tua ke KK Head / Spouse
            foreach ($kkChildren as $child) {
                $cKey = 'w-' . $child->id_warga;
                if ($kkHead) {
                    $hKey = 'w-' . $kkHead->id_warga;
                    $hasHeadEdge = false;
                    foreach ($edges as $e) {
                        if ($e['from'] === $hKey && $e['to'] === $cKey) {
                            $hasHeadEdge = true;
                            break;
                        }
                    }
                    if (!$hasHeadEdge) {
                        $edges[] = ['from' => $hKey, 'to' => $cKey, 'type' => 'parent'];
                    }
                }
                if ($kkSpouse) {
                    $sKey = 'w-' . $kkSpouse->id_warga;
                    $hasSpouseEdge = false;
                    foreach ($edges as $e) {
                        if ($e['from'] === $sKey && $e['to'] === $cKey) {
                            $hasSpouseEdge = true;
                            break;
                        }
                    }
                    if (!$hasSpouseEdge) {
                        $edges[] = ['from' => $sKey, 'to' => $cKey, 'type' => 'parent'];
                    }
                }
            }
        }

        // Deduplikasi edges
        $uniqueEdges = [];
        $seen = [];
        foreach ($edges as $edge) {
            $hash = $edge['from'] . '->' . $edge['to'] . ':' . $edge['type'];
            if (!isset($seen[$hash])) {
                $seen[$hash] = true;
                $uniqueEdges[] = $edge;
            }
        }

        return [
            'current_id' => (int) $id,
            'nodes'      => array_values($nodes),
            'edges'      => $uniqueEdges,
            'has_data'   => !empty($nodes),
        ];
    }

    public function kk_count()
    {
        return $this->db->table($this->table)
            ->select('DISTINCT(warga.`no_kk`)')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function laki_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'L')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function perempuan_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'P')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function nik($nik)
    {
        return $this->db->table($this->table)
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('nik', $nik)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getRow();
    }

    /**
     * Living, active residents of one address, for the public Layanan
     * "who is applying" step. Tenant-scoped like every other query here.
     */
    public function anggotaAlamat(int $idAlamat)
    {
        return $this->db->table($this->table)
            ->select('warga.id_warga, warga.nama_warga, status_keluarga.status status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->where('warga.id_alamat', $idAlamat)
            ->where('warga.id_rt', current_rt_id())
            ->where('warga.status_warga', 1)
            ->where('warga.is_hidup', 1)
            ->orderBy('warga.id_status_keluarga, warga.nama_warga')
            ->get()->getResult();
    }

    /**
     * One resident of the given address (null if they live elsewhere,
     * are inactive, deceased or belong to another RT), with the block
     * address joined in, to prefill the Layanan form.
     */
    public function untukLayanan(int $idWarga, int $idAlamat)
    {
        return $this->db->table($this->table)
            ->select('warga.*, alamat.alamat')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('warga.id_warga', $idWarga)
            ->where('warga.id_alamat', $idAlamat)
            ->where('warga.id_rt', current_rt_id())
            ->where('warga.status_warga', 1)
            ->where('warga.is_hidup', 1)
            ->get()->getRow();
    }

    public function get_status_keluarga()
    {
        return $this->db->table('status_keluarga')->get()->getResult();
    }

    public function get_status_penduduk()
    {
        return $this->db->table('status_penduduk')->get()->getResult();
    }

    /** Age threshold (years) used to classify a resident as lansia. */
    public const LANSIA_MIN_AGE = 60;

    /**
     * Lansia (age >= LANSIA_MIN_AGE) across a set of RTs, for the
     * kesehatan kegiatan participant picker's default (auto-filtered)
     * list. Callers must already have authorized access to every id.
     *
     * @param int[] $idRts
     */
    public function lansiaByRtIds(array $idRts): array
    {
        if (empty($idRts)) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('id_warga, nama_warga, nik, tanggal_lahir, alamat.alamat, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('status_warga', 1)
            ->whereIn('warga.id_rt', $idRts)
            ->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', self::LANSIA_MIN_AGE)
            ->orderBy('nama_warga')
            ->get()->getResult();
    }

    /**
     * Every active resident across a set of RTs, for the Kesehatan
     * "tambah peserta lain" modal's full datatable (client-side
     * search/filter, no age restriction) and for the Presensi attendance
     * list, where every resident is a candidate rather than a filtered
     * subset. Callers must already have authorized access to every id.
     *
     * @param int[] $idRts
     */
    public function allByRtIds(array $idRts): array
    {
        if (empty($idRts)) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('id_warga, nama_warga, nik, tanggal_lahir, alamat.alamat, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('status_warga', 1)
            ->whereIn('warga.id_rt', $idRts)
            ->orderBy('nama_warga')
            ->get()->getResult();
    }

    /**
     * Specific residents by id, e.g. participants manually added to a
     * kegiatan who fall outside the lansia auto-filter. Callers must
     * already have authorized access to every id.
     *
     * @param int[] $ids
     */
    public function byIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('id_warga, nama_warga, nik, tanggal_lahir, alamat.alamat, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->whereIn('id_warga', $ids)
            ->orderBy('nama_warga')
            ->get()->getResult();
    }

    /**
     * A single resident by id, scoped to a set of authorized RTs.
     * Returns an object (unlike the base Model's array return type) to
     * match every other custom query method on this model.
     *
     * @param int[] $idRts
     */
    public function oneByRtIds(int $idWarga, array $idRts): ?object
    {
        if (empty($idRts)) {
            return null;
        }

        return $this->db->table($this->table)
            ->where('id_warga', $idWarga)
            ->whereIn('warga.id_rt', $idRts)
            ->get()->getRow();
    }

    public const EXPORT_COLUMNS = [
        'nama_warga'      => 'Nama Lengkap',
        'nik'             => 'NIK',
        'no_kk'           => 'No. KK',
        'jenis_kelamin'   => 'Jenis Kelamin',
        'tempat_lahir'    => 'Tempat Lahir',
        'tanggal_lahir'   => 'Tanggal Lahir',
        'gol_darah'       => 'Golongan Darah',
        'agama'           => 'Agama',
        'pendidikan'      => 'Pendidikan',
        'nama_pekerjaan'  => 'Pekerjaan',
        'status_kawin'    => 'Status Kawin',
        'tanggal_kawin'   => 'Tanggal Kawin',
        'status_keluarga' => 'Status dlm Keluarga',
        'ayah'            => 'Nama Ayah',
        'ibu'             => 'Nama Ibu',
        'no_hp'           => 'No. HP',
        'email'           => 'Email',
        'status_penduduk' => 'Status Penduduk',
        'sumber_air'      => 'Sumber Air',
        'alamat'          => 'Alamat (Blok)',
        'alamat_lengkap'  => 'Alamat Lengkap',
        'is_hidup'        => 'Status Hidup',
    ];

    /**
     * Columns exported as Excel text so long all-digit strings (NIK,
     * no. KK, phone) aren't mangled into scientific notation / stripped
     * leading zeros when the .xls is opened.
     */
    public const EXPORT_TEXT_COLUMNS = ['nik', 'no_kk', 'no_hp'];

    /**
     * Parse the `columns` GET param (comma-separated keys) into a
     * validated, non-empty list of EXPORT_COLUMNS keys. Falls back to
     * all columns when the param is missing, empty, or contains no
     * recognized key.
     */
    public static function resolveExportColumns(?string $columnsParam): array
    {
        if ($columnsParam === null || $columnsParam === '') {
            return array_keys(self::EXPORT_COLUMNS);
        }

        $columns = array_values(array_intersect(explode(',', $columnsParam), array_keys(self::EXPORT_COLUMNS)));

        return empty($columns) ? array_keys(self::EXPORT_COLUMNS) : $columns;
    }

    public function export($type = null, $value = null, bool $includeDeceased = false)
    {
        $builder = $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk, pekerjaan.nama_pekerjaan nama_pekerjaan')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga', 'left')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk', 'left')
            ->join('pekerjaan', 'pekerjaan.id_pekerjaan = warga.id_pekerjaan', 'left')
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id());

        if (!$includeDeceased) {
            $builder->where('warga.is_hidup', 1);
        }

        if (!empty($type) && !empty($value)) {
            if ($type === 'gender') {
                $builder->where('jenis_kelamin', $value);
            } else if ($type === 'age-group') {
                if ($value === 'balita') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 5);
                } else if ($value === 'anak') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 6);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 11);
                } else if ($value === 'remaja') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 12);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 25);
                } else if ($value === 'dewasa') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 26);
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <=', 59);
                } else if ($value === 'lansia') {
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', self::LANSIA_MIN_AGE);
                }
            } else if ($type === 'education') {
                if ($value === 'BELUM_SEKOLAH') {
                    $builder->groupStart()
                        ->where('warga.pendidikan', '-')
                        ->orWhere('warga.pendidikan', '')
                        ->orWhere('warga.pendidikan', null)
                        ->groupEnd();
                } else if ($value === 'SD') {
                    $builder->where('warga.pendidikan', 'SD');
                } else if ($value === 'SMP') {
                    $builder->where('warga.pendidikan', 'SMP');
                } else if ($value === 'SMA') {
                    $builder->groupStart()
                        ->where('warga.pendidikan', 'SMA')
                        ->orWhere('warga.pendidikan', 'SMK')
                        ->groupEnd();
                } else if ($value === 'KULIAH') {
                    $builder->whereIn('warga.pendidikan', ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']);
                }
            }
        }

        $results = $builder->get()->getResult();

        $this->resolveParentNames($results);

        return $results;
    }

    /**
     * The ayah/ibu columns may hold either a literal name or a numeric
     * id_warga reference to another resident (legacy CI3 data). Replace
     * numeric references with that resident's name when it exists in the
     * same tenant; leave literal names (and unresolved numbers) as-is.
     *
     * @param object[] $results
     */
    private function resolveParentNames(array $results): void
    {
        $map = [];
        foreach (
            $this->db->table($this->table)
                ->select('id_warga, nama_warga')
                ->where('warga.id_rt', current_rt_id())
                ->get()->getResult() as $row
        ) {
            $map[(string) $row->id_warga] = $row->nama_warga;
        }

        foreach ($results as $row) {
            foreach (['ayah', 'ibu'] as $field) {
                $val = $row->{$field} ?? null;
                if ($val !== null && ctype_digit((string) $val) && isset($map[(string) $val])) {
                    $row->{$field} = $map[(string) $val];
                }
            }
        }
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    /**
     * Resident matched by their enrolled RFID card UID (`kode_rfid`),
     * scoped to authorized RTs, for the Kesehatan e-KTP scan feature.
     * Card UIDs are opaque chip identifiers rather than the printed NIK
     * (the NIK on an e-KTP chip is encrypted and only readable through a
     * certified Dukcapil SDK this app doesn't have), so a scan only
     * resolves to a warga once that resident has been enrolled - see
     * Admin\Kesehatan::daftarRfid(). Callers must already have authorized
     * access to every id in $idRts.
     *
     * @param int[] $idRts
     */
    public function oneByRfidAndRtIds(string $kodeRfid, array $idRts): ?object
    {
        if (empty($idRts) || $kodeRfid === '') {
            return null;
        }

        return $this->db->table($this->table)
            ->where('kode_rfid', $kodeRfid)
            ->whereIn('warga.id_rt', $idRts)
            ->get()->getRow();
    }

    /**
     * Insert a resident from just a name (+ optional gender/approx.
     * birth year), for the Kesehatan "tambah warga baru" flow - someone
     * shows up to a posyandu/checkup with no existing warga row and no
     * full bio data on hand, but still needs a record to attach that
     * session's measurements to. NOT NULL columns with no real value
     * (no_kk, tempat_lahir) get the '-' placeholder already used
     * elsewhere in this app (Admin\Warga::store(), the RT26-30 bulk
     * import) for the same "unknown" case. nik is NOT NULL and UNIQUE,
     * so '-' can't be reused for every quick-add - a random placeholder
     * is generated instead, with a few retries on the (astronomically
     * unlikely) chance of a collision.
     */
    public function createMinimal(string $namaWarga, ?string $jenisKelamin, string $tanggalLahir, int $idPekerjaan, int $idRt): int
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $this->insert([
                    'nama_warga'    => $namaWarga,
                    'no_kk'         => '-',
                    'nik'           => 'TMP-' . bin2hex(random_bytes(6)),
                    'jenis_kelamin' => $jenisKelamin ?: null,
                    'tempat_lahir'  => '-',
                    'tanggal_lahir' => $tanggalLahir,
                    'id_pekerjaan'  => $idPekerjaan,
                    'id_rt'         => $idRt,
                ]);

                return (int) $this->insertID();
            } catch (DatabaseException $e) {
                if ($attempt === 5) {
                    throw $e;
                }
            }
        }
    }
}
