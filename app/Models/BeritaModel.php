<?php

namespace App\Models;

use CodeIgniter\Model;

class BeritaModel extends Model
{
    protected $table         = 'berita';
    protected $primaryKey    = 'id_berita';
    protected $allowedFields = ['judul', 'slug', 'deskripsi', 'lampiran', 'foto', 'kategori', 'is_status', 'created_by', 'timestamp'];

    public function all()
    {
        return $this->db->table($this->table)
            ->orderBy('timestamp', 'desc')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_berita', $id)
            ->get()->getRow();
    }

    public function detail_berita($slug)
    {
        return $this->db->table($this->table)
            ->where('slug', $slug)
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)->get()->getNumRows();
    }
}
