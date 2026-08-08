<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiAcaraModel extends Model
{
    protected $table         = 'presensi_acara';
    protected $primaryKey    = 'id_acara';
    protected $allowedFields = [
        'nama_acara',
        'tanggal_acara',
        'tempat',
        'id_rt',
        'id_rw',
        'catatan',
        'id_user',
    ];

    /**
     * Riwayat acara: RT-scoped admin sees acara their own RT created PLUS
     * their RW's own gabungan acara (so a joint RW event isn't invisible
     * to member RTs) - but never another RT's independently created
     * acara. RW-scoped admin sees everything under their RW: the RW's own
     * acara PLUS every acara any member RT created independently.
     * Mirrors KesehatanKegiatanModel::forCurrentScope().
     */
    public function forCurrentScope(): array
    {
        // nama_rt/nama_rw: which RT organized it, or that it's an RW-wide
        // acara - shown as an explicit badge in the list (not inferred
        // from nama_acara, which two different RTs could easily reuse).
        $builder = $this->db->table($this->table)
            ->select('presensi_acara.*, rt.nama nama_rt, rw.nama nama_rw,'
                . " SUM(CASE WHEN presensi_kehadiran.status = 'hadir' THEN 1 ELSE 0 END) jumlah_hadir,"
                . " SUM(CASE WHEN presensi_kehadiran.status = 'tidak' THEN 1 ELSE 0 END) jumlah_tidak")
            ->join('presensi_kehadiran', 'presensi_kehadiran.id_acara = presensi_acara.id_acara', 'left')
            ->join('rt', 'rt.id_rt = presensi_acara.id_rt', 'left')
            ->join('rw', 'rw.id_rw = presensi_acara.id_rw', 'left')
            ->groupBy('presensi_acara.id_acara')
            ->orderBy('presensi_acara.tanggal_acara', 'DESC');

        $this->applyScope($builder, 'presensi_acara.');

        return $builder->get()->getResult();
    }

    /**
     * Fetch an acara, but only if it belongs to the caller's current
     * RT/RW scope. Returns null (not the row) when out of scope, so
     * callers can 404 instead of leaking cross-tenant data.
     */
    public function detailForCurrentScope(int $id): ?object
    {
        $builder = $this->db->table($this->table)->where('id_acara', $id);

        $this->applyScope($builder);

        return $builder->get()->getRow();
    }

    /**
     * Applies the RT/RW isolation rule shared by forCurrentScope() and
     * detailForCurrentScope() to an in-progress query builder.
     *
     * @param string $prefix Column prefix to use (e.g. 'presensi_acara.'
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

        $rtId = current_rt_id();
        $rt   = model(RtModel::class)->find($rtId);

        // Own RT's acara, plus the RW's own gabungan acara (own RT's acara
        // may have id_rw null, so this can't collapse into a single
        // orWhereIn - id_rt and id_rw are matched against different values).
        $builder->groupStart()->where($prefix . 'id_rt', $rtId);
        if ($rt !== null && $rt->id_rw !== null) {
            $builder->orWhere($prefix . 'id_rw', (int) $rt->id_rw);
        }
        $builder->groupEnd();
    }
}
