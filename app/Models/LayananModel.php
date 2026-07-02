<?php

namespace App\Models;

use CodeIgniter\Model;

class LayananModel extends Model
{
    protected $table         = 'layanan';
    protected $primaryKey    = 'id_layanan';
    protected $allowedFields = ['nama_layanan'];

    public function all()
    {
        return $this->db->table($this->table)->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_layanan', $id)
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)->get()->getNumRows();
    }
}
