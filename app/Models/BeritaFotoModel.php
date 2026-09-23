<?php

namespace App\Models;

use CodeIgniter\Model;

class BeritaFotoModel extends Model
{
    protected $table         = 'berita_foto';
    protected $primaryKey    = 'id_berita_foto';
    protected $allowedFields = ['id_berita', 'foto', 'urutan', 'is_cover', 'id_rt'];

    public function forBerita(int $idBerita): array
    {
        return $this->db->table($this->table)
            ->where('id_berita', $idBerita)
            ->where('berita_foto.id_rt', current_rt_id())
            ->orderBy('urutan', 'asc')
            ->get()->getResult();
    }

    public function countForBerita(int $idBerita): int
    {
        return $this->db->table($this->table)
            ->where('id_berita', $idBerita)
            ->where('berita_foto.id_rt', current_rt_id())
            ->countAllResults();
    }

    public function deleteForBerita(int $idBerita, int $id): void
    {
        $this->db->table($this->table)
            ->where('id_berita_foto', $id)
            ->where('id_berita', $idBerita)
            ->where('id_rt', current_rt_id())
            ->delete();
    }

    /**
     * Clears is_cover on every photo of the berita, then sets it on $id.
     * No-op on the set step if $id doesn't belong to $idBerita/the tenant.
     */
    public function setCover(int $idBerita, int $id): void
    {
        $this->db->table($this->table)
            ->where('id_berita', $idBerita)
            ->where('id_rt', current_rt_id())
            ->update(['is_cover' => 0]);

        $this->db->table($this->table)
            ->where('id_berita_foto', $id)
            ->where('id_berita', $idBerita)
            ->where('id_rt', current_rt_id())
            ->update(['is_cover' => 1]);
    }
}
