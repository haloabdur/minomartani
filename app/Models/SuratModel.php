<?php

namespace App\Models;

use CodeIgniter\Model;

class SuratModel extends Model
{
    protected $table         = 'surat';
    protected $primaryKey    = 'id_surat';
    protected $allowedFields = ['no_surat', 'id_warga', 'id_alamat', 'maksut', 'perlu', 'lampiran', 'status_surat'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('nama_warga, no_hp, surat.*')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('surat.*, warga.*, alamat.alamat')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('surat.id_surat', $id)
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)->get()->getNumRows();
    }
}
