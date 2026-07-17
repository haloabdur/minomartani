<?php

namespace App\Models;

use CodeIgniter\Model;

class SuratModel extends Model
{
    protected $table         = 'surat';
    protected $primaryKey    = 'id_surat';
    protected $allowedFields = ['id_warga', 'maksut', 'perlu', 'lampiran', 'status_surat', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('nama_warga, no_hp, surat.*')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->where('surat.id_rt', current_rt_id())
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->select('surat.*, warga.*, alamat.alamat')
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat')
            ->where('surat.id_surat', $id)
            ->where('surat.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('surat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
