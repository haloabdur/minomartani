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
        'sumber_air'
    ];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
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
            ->get()->getRow();
    }

    public function kk_count()
    {
        return $this->db->table($this->table)
            ->select('DISTINCT(warga.`no_kk`)')
            ->where('status_warga', 1)
            ->get()->getNumRows();
    }

    public function laki_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'L')
            ->where('status_warga', 1)
            ->get()->getNumRows();
    }

    public function perempuan_count()
    {
        return $this->db->table($this->table)
            ->where('jenis_kelamin', 'P')
            ->where('status_warga', 1)
            ->get()->getNumRows();
    }

    public function nik($nik)
    {
        return $this->db->table($this->table)
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('nik', $nik)
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

    public function export()
    {
        return $this->db->table($this->table)
            ->select('*, status_keluarga.status status_keluarga, status_penduduk.status status_penduduk, status_penduduk.label label_penduduk')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->join('status_keluarga', 'status_keluarga.id_status_keluarga = warga.id_status_keluarga')
            ->join('status_penduduk', 'status_penduduk.id_status_penduduk = warga.id_status_penduduk')
            ->orderBy('alamat.id_alamat, warga.no_kk, warga.id_status_keluarga')
            ->where('status_warga', 1)
            ->get()->getResult();
    }

    public function count()
    {
        return $this->db->table($this->table)->get()->getNumRows();
    }
}
