<?php

namespace App\Models;

use CodeIgniter\Model;

class RtModel extends Model
{
    protected $table         = 'rt';
    protected $primaryKey    = 'id_rt';
    protected $returnType    = 'object';
    protected $allowedFields = ['id_rw', 'nama', 'slug', 'subdomain', 'is_aktif'];

    public function bySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->where('is_aktif', 1)->first();
    }

    public function bySubdomain(string $subdomain): ?object
    {
        return $this->where('subdomain', $subdomain)->where('is_aktif', 1)->first();
    }

    /** @return list<object> */
    public function aktif(): array
    {
        return $this->where('is_aktif', 1)->orderBy('nama')->findAll();
    }

    /** @return list<object> */
    public function byRw(int $idRw): array
    {
        return $this->where('id_rw', $idRw)->where('is_aktif', 1)->orderBy('nama')->findAll();
    }

    /**
     * Per-RT aggregate counts for the RW recap screen. Pass null to
     * include every RT (superadmin view).
     *
     * @return list<object>
     */
    public function rekap(?int $idRw): array
    {
        $builder = $this->db->table('rt')
            ->select("rt.id_rt, rt.nama, rt.slug,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.status_warga = 1) jml_warga,
                (SELECT COUNT(DISTINCT w.no_kk) FROM warga w WHERE w.id_rt = rt.id_rt AND w.status_warga = 1) jml_kk,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.jenis_kelamin = 'L' AND w.status_warga = 1) jml_l,
                (SELECT COUNT(*) FROM warga w WHERE w.id_rt = rt.id_rt AND w.jenis_kelamin = 'P' AND w.status_warga = 1) jml_p,
                (SELECT COUNT(*) FROM surat s WHERE s.id_rt = rt.id_rt) jml_surat", false)
            ->where('rt.is_aktif', 1)
            ->orderBy('rt.nama');

        if ($idRw !== null) {
            $builder->where('rt.id_rw', $idRw);
        }

        return $builder->get()->getResult();
    }
}
