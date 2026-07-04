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
                    $builder->where('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >=', 60);
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

        return $builder->get()->getResult();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('warga.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
