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

    /** Name of the current Ketua RT (newest `mulai`) of the active tenant, or null. */
    public function namaKetuaSekarang(): ?string
    {
        $row = $this->db->table($this->table)
            ->select('nama_ketua')
            ->where('ketua.id_rt', current_rt_id())
            ->orderBy('mulai', 'desc')
            ->orderBy('id_ketua', 'desc')
            ->get()->getRow();

        return $row === null ? null : $row->nama_ketua;
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_ketua', $id)
            ->where('ketua.id_rt', current_rt_id())
            ->get()->getRow();
    }
}
