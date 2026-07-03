<?php

namespace App\Models;

use CodeIgniter\Model;

class InventarisModel extends Model
{
    protected $table         = 'inventaris';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nama_barang', 'stok', 'foto', 'created_at', 'updated_at', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->where('id_rt', current_rt_id())
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('id_rt', current_rt_id())
            ->get()->getRow();
    }

    public function hapus($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->where('id_rt', current_rt_id())
            ->delete();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('id_rt', current_rt_id())
            ->get()->getNumRows();
    }
}
