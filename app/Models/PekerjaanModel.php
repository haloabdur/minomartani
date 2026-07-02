<?php

namespace App\Models;

use CodeIgniter\Model;

class PekerjaanModel extends Model
{
    protected $table         = 'pekerjaan';
    protected $primaryKey    = 'id_pekerjaan';
    protected $allowedFields = ['nama_pekerjaan'];

    public function all()
    {
        return $this->db->table($this->table)->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_pekerjaan', $id)
            ->get()->getRow();
    }
}
