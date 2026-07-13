<?php

namespace App\Models;

use CodeIgniter\Model;

class WargaModel extends Model
{
    protected $table         = 'warga';
    protected $primaryKey    = 'id_warga';
    protected $allowedFields = [
        'nama_warga',
        'no_kk',
        'id_alamat',
        'alamat_lengkap',
        'nik',
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
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
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
            ->select('warga.id_rt, jenis_kelamin, tanggal_lahir, pendidikan')
            ->where('status_warga', 1)
            ->whereIn('warga.id_rt', $idRts)
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('id_warga', $id)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getRow();
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
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('nik', $nik)
            ->where('warga.id_rt', current_rt_id())
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
            ->select('id_warga, nama_warga, tanggal_lahir, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
            ->where('status_warga', 1)
            ->whereIn('warga.id_rt', $idRts)
            ->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', self::LANSIA_MIN_AGE)
            ->orderBy('nama_warga')
            ->get()->getResult();
    }

    /**
     * Every active resident across a set of RTs, for the "tambah peserta
     * lain" modal's full datatable (client-side search/filter, no
     * age restriction). Callers must already have authorized access to
     * every id.
     *
     * @param int[] $idRts
     */
    public function allByRtIds(array $idRts): array
    {
        if (empty($idRts)) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('id_warga, nama_warga, nik, tanggal_lahir, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
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
            ->select('id_warga, nama_warga, tanggal_lahir, jenis_kelamin, warga.id_rt, rt.nama nama_rt')
            ->join('rt', 'rt.id_rt = warga.id_rt')
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
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
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
}
