<?php

namespace App\Models;

use CodeIgniter\Model;

class KesehatanKegiatanModel extends Model
{
    protected $table         = 'kesehatan_kegiatan';
    protected $primaryKey    = 'id_kegiatan';
    protected $allowedFields = [
        'nama_kegiatan',
        'tanggal_kegiatan',
        'kategori',
        'id_rt',
        'id_rw',
        'catatan',
        'id_user',
    ];

    /**
     * Riwayat general: kegiatan RW-nya sendiri kalau RW-scoped, atau
     * kegiatan RT-nya sendiri kalau RT-scoped. Sertakan jumlah peserta
     * yang sudah tercatat per kegiatan.
     */
    public function forCurrentScope(): array
    {
        $builder = $this->db->table($this->table)
            ->select('kesehatan_kegiatan.*, COUNT(kesehatan_catatan.id_catatan) jumlah_peserta')
            ->join('kesehatan_catatan', 'kesehatan_catatan.id_kegiatan = kesehatan_kegiatan.id_kegiatan', 'left')
            ->groupBy('kesehatan_kegiatan.id_kegiatan')
            ->orderBy('kesehatan_kegiatan.tanggal_kegiatan', 'DESC');

        $rwId = current_rw_id();
        if ($rwId !== null) {
            $builder->where('kesehatan_kegiatan.id_rw', $rwId);
        } else {
            $builder->where('kesehatan_kegiatan.id_rt', current_rt_id());
        }

        return $builder->get()->getResult();
    }

    /**
     * Fetch a kegiatan, but only if it belongs to the caller's current
     * RT/RW scope. Returns null (not the row) when out of scope, so
     * callers can 404 instead of leaking cross-tenant data.
     */
    public function detailForCurrentScope(int $id): ?object
    {
        $builder = $this->db->table($this->table)->where('id_kegiatan', $id);

        $rwId = current_rw_id();
        if ($rwId !== null) {
            $builder->where('id_rw', $rwId);
        } else {
            $builder->where('id_rt', current_rt_id());
        }

        return $builder->get()->getRow();
    }
}
