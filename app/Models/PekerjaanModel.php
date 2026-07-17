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

    /**
     * id_pekerjaan is NOT NULL on warga but the "Tambah/Ubah Warga" form
     * no longer requires picking one (matches import data that doesn't
     * carry pekerjaan). Find-or-create a generic placeholder row so
     * inserts always have a valid FK value to fall back to.
     */
    public function defaultId(): int
    {
        $row = $this->db->table($this->table)->where('nama_pekerjaan', 'Belum Diisi')->get()->getRow();

        if ($row !== null) {
            return (int) $row->id_pekerjaan;
        }

        $this->insert(['nama_pekerjaan' => 'Belum Diisi']);

        return (int) $this->insertID();
    }
}
