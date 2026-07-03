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

    public function export($type = null, $value = null)
    {
        $builder = $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->where('status_warga', 1)
            ->where('warga.id_rt', current_rt_id());

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
