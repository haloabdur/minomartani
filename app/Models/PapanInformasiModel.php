<?php

namespace App\Models;

use CodeIgniter\Model;

class PapanInformasiModel extends Model
{
    protected $table         = 'papan_informasi';
    protected $primaryKey    = 'id_papan';
    protected $allowedFields = ['judul', 'isi', 'lampiran', 'is_status', 'created_by', 'timestamp', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->where('papan_informasi.id_rt', current_rt_id())
            ->orderBy('timestamp', 'desc')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_papan', $id)
            ->where('papan_informasi.id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function published()
    {
        return $this->db->table($this->table)
            ->where('papan_informasi.id_rt', current_rt_id())
            ->where('is_status', 1)
            ->orderBy('timestamp', 'desc')
            ->get()->getResult();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('papan_informasi.id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
