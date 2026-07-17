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
     * Riwayat general: RT-scoped admin sees ONLY the kegiatan their own RT
     * created - never another RT's, never the RW's - strict isolation.
     * RW-scoped admin sees everything under their RW: the RW's own
     * kegiatan PLUS every kegiatan any member RT created independently,
     * so nothing captured by an RT admin is hidden from the RW view.
     * Sertakan jumlah peserta yang sudah tercatat per kegiatan.
     */
    public function forCurrentScope(): array
    {
        // nama_rt/nama_rw: which RT organized it, or that it's an RW-wide
        // kegiatan - shown as an explicit badge in the list (not inferred
        // from nama_kegiatan, which two different RTs could easily reuse).
        $builder = $this->db->table($this->table)
            ->select('kesehatan_kegiatan.*, rt.nama nama_rt, rw.nama nama_rw, COUNT(kesehatan_catatan.id_catatan) jumlah_peserta')
            ->join('kesehatan_catatan', 'kesehatan_catatan.id_kegiatan = kesehatan_kegiatan.id_kegiatan', 'left')
            ->join('rt', 'rt.id_rt = kesehatan_kegiatan.id_rt', 'left')
            ->join('rw', 'rw.id_rw = kesehatan_kegiatan.id_rw', 'left')
            ->groupBy('kesehatan_kegiatan.id_kegiatan')
            ->orderBy('kesehatan_kegiatan.tanggal_kegiatan', 'DESC');

        $this->applyScope($builder, 'kesehatan_kegiatan.');

        return $builder->get()->getResult();
    }

    /**
     * Fetch a kegiatan, but only if it belongs to the caller's current
     * RT/RW scope. Returns null (not the row) when out of scope, so
     * callers can 404 instead of leaking cross-tenant data. Mirrors
     * forCurrentScope()'s isolation rule: RT admins only ever match their
     * own RT's kegiatan; RW admins match the RW's own kegiatan or any
     * member RT's kegiatan.
     */
    public function detailForCurrentScope(int $id): ?object
    {
        $builder = $this->db->table($this->table)->where('id_kegiatan', $id);

        $this->applyScope($builder);

        return $builder->get()->getRow();
    }

    /**
     * Applies the RT/RW isolation rule shared by forCurrentScope() and
     * detailForCurrentScope() to an in-progress query builder.
     *
     * @param string $prefix Column prefix to use (e.g. 'kesehatan_kegiatan.'
     *                       when the query joins another table), empty when
     *                       the query only touches this table.
     */
    private function applyScope($builder, string $prefix = ''): void
    {
        $rwId = current_rw_id();

        if ($rwId !== null) {
            $rtIds = array_map(static fn ($r) => (int) $r->id_rt, model(RtModel::class)->byRw($rwId));

            $builder->groupStart()->where($prefix . 'id_rw', $rwId);
            if (! empty($rtIds)) {
                $builder->orWhereIn($prefix . 'id_rt', $rtIds);
            }
            $builder->groupEnd();

            return;
        }

        $builder->where($prefix . 'id_rt', current_rt_id());
    }
}
