<?php

namespace App\Models;

use CodeIgniter\Model;

class KetuaModel extends Model
{
    protected $table         = 'ketua';
    protected $primaryKey    = 'id_ketua';
    protected $allowedFields = ['nama_ketua', 'mulai', 'selesai', 'foto_ketua', 'timestamp', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->where('ketua.id_rt', current_rt_id())
            ->orderBy('mulai', 'desc')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_ketua', $id)
            ->where('ketua.id_rt', current_rt_id())
            ->get()->getRow();
    }
}
