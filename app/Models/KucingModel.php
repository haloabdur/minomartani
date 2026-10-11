<?php

namespace App\Models;

use CodeIgniter\Model;

class KucingModel extends Model
{
    protected $table         = 'kucing';
    protected $primaryKey    = 'id_kucing';
    protected $allowedFields = ['id_rt', 'id_warga', 'nama', 'foto', 'warna_ciri', 'jenis_kelamin', 'steril', 'vaksin', 'created_at', 'updated_at'];

    /** Admin list: every cat in the current RT with its owner's name. */
    public function all()
    {
        return $this->db->table($this->table)
            ->select('kucing.*, warga.nama_warga, alamat.alamat')
            ->join('warga', 'warga.id_warga = kucing.id_warga', 'left')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('kucing.id_rt', current_rt_id())
            ->orderBy('kucing.nama')
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_kucing', $id)
            ->where('kucing.id_rt', current_rt_id())
            ->get()->getRow();
    }

    /**
     * Public listing: deliberately exposes no owner name, only the street
     * part of the owner's alamat ("jalan/nomor" -> "jalan").
     */
    public function publik(?string $cari = null)
    {
        $builder = $this->db->table($this->table)
            ->select("kucing.id_kucing, kucing.nama, kucing.foto, kucing.warna_ciri, kucing.jenis_kelamin, SUBSTRING_INDEX(alamat.alamat, '/', 1) AS jalan", false)
            ->join('warga', 'warga.id_warga = kucing.id_warga', 'left')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('kucing.id_rt', current_rt_id());

        if ($cari !== null && $cari !== '') {
            $builder->like('kucing.nama', $cari);
        }

        return $builder->orderBy('kucing.nama')->get()->getResult();
    }

    /** Owner picker: living warga of the current RT only. */
    public function pemilikOptions()
    {
        return $this->db->table('warga')
            ->select('warga.id_warga, warga.nama_warga, alamat.alamat')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('warga.id_rt', current_rt_id())
            ->where('warga.status_warga', 1)
            ->orderBy('warga.nama_warga')
            ->get()->getResult();
    }

    /** Guards against posting an id_warga that belongs to another RT. */
    public function pemilikValid(int $idWarga): bool
    {
        return $this->db->table('warga')
            ->where('id_warga', $idWarga)
            ->where('id_rt', current_rt_id())
            ->countAllResults() > 0;
    }

    public function hapus($id)
    {
        return $this->db->table($this->table)
            ->where('id_kucing', $id)
            ->where('id_rt', current_rt_id())
            ->delete();
    }

    /** Total cats of the current RT; 0 keeps the public page hidden (404). */
    public function jumlah(): int
    {
        return $this->db->table($this->table)
            ->where('id_rt', current_rt_id())
            ->countAllResults();
    }

    public function countByWarga(int $idWarga): int
    {
        return $this->db->table($this->table)
            ->where('id_warga', $idWarga)
            ->where('id_rt', current_rt_id())
            ->countAllResults();
    }
}
