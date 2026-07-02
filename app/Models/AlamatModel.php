<?php

namespace App\Models;

use CodeIgniter\Model;

class AlamatModel extends Model
{
    protected $table         = 'alamat';
    protected $primaryKey    = 'id_alamat';
    protected $allowedFields = ['nomor', 'alamat', 'kode_rumah', 'qrcode'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('alamat.id_alamat, alamat.alamat, alamat.qrcode, COUNT(warga.id_warga) jumlah')
            ->join('warga', 'warga.id_alamat = alamat.id_alamat AND warga.status_warga = 1', 'left')
            ->groupBy('alamat.id_alamat')
            ->get()->getResult();
    }

    public function alamat_detail($kode)
    {
        return $this->db->table($this->table)
            ->select('nama_warga, alamat')
            ->where('qrcode', $kode)
            ->where('id_status_keluarga', 1)
            ->join('warga', 'warga.id_alamat = alamat.id_alamat')
            ->get()->getRow();
    }

    public function cek_alamat($alamat, $nomor)
    {
        return $this->db->table($this->table)
            ->where('alamat', $alamat)
            ->where('nomor', $nomor)
            ->get()->getNumRows();
    }

    public function alamat_count()
    {
        return $this->db->table($this->table)->get()->getNumRows();
    }

    public function kosong_count()
    {
        return $this->db->table($this->table)
            ->where('id_alamat NOT IN (SELECT id_alamat FROM warga WHERE status_warga = 1)', null, false)
            ->get()->getNumRows();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_alamat', $id)
            ->get()->getRow();
    }
}
