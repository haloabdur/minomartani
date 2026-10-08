<?php

namespace App\Models;

use CodeIgniter\Model;

class SuratModel extends Model
{
    public const STATUS_MENUNGGU  = 0;
    public const STATUS_DISETUJUI = 1;
    public const STATUS_DITOLAK   = 2;

    protected $table         = 'surat';
    protected $primaryKey    = 'id_surat';
    protected $allowedFields = [
        'id_warga', 'maksut', 'perlu', 'lampiran', 'status_surat', 'id_rt',
        'nama_pemohon', 'nik_pemohon', 'alamat_pemohon', 'tempat_lahir',
        'tanggal_lahir', 'agama', 'no_hp', 'alasan_tolak',
    ];

    /**
     * surat.* is the applicant's submitted snapshot; the live `warga`
     * record is aliased rt_* because both tables have columns named
     * tempat_lahir / agama / no_hp / tanggal_lahir (see
     * App\Libraries\SuratPemohon::FIELDS for the pairing).
     */
    private const SELECT = 'surat.*,
        warga.nama_warga rt_nama, warga.nik rt_nik, warga.tempat_lahir rt_tempat_lahir,
        warga.tanggal_lahir rt_tanggal_lahir, warga.agama rt_agama, warga.no_hp rt_no_hp,
        warga.alamat_lengkap rt_alamat_lengkap, alamat.alamat rt_alamat';

    private function baseQuery()
    {
        return $this->db->table($this->table)
            ->select(self::SELECT, false)
            ->join('warga', 'warga.id_warga = surat.id_warga')
            ->join('alamat', 'alamat.id_alamat = warga.id_alamat', 'left')
            ->where('surat.id_rt', current_rt_id());
    }

    /** Pending first, then newest first. */
    public function all()
    {
        return $this->baseQuery()
            ->orderBy('surat.status_surat = 0 DESC, surat.created_at DESC', '', false)
            ->get()->getResult();
    }

    public function detail($id)
    {
        return $this->baseQuery()
            ->where('surat.id_surat', $id)
            ->get()->getRow();
    }

    public function count()
    {
        return $this->db->table($this->table)
            ->where('surat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function countMenunggu(): int
    {
        return $this->db->table($this->table)
            ->where('surat.id_rt', current_rt_id())
            ->where('surat.status_surat', self::STATUS_MENUNGGU)
            ->countAllResults();
    }

    /**
     * Whether this resident already has a request still awaiting the RT's
     * decision - the public form allows only one at a time.
     */
    public function hasMenunggu(int $idWarga): bool
    {
        return $this->db->table($this->table)
            ->where('surat.id_rt', current_rt_id())
            ->where('surat.id_warga', $idWarga)
            ->where('surat.status_surat', self::STATUS_MENUNGGU)
            ->countAllResults() > 0;
    }

    /**
     * Status transition from "menunggu" only. The WHERE on the current
     * status makes approve/reject race-safe and idempotent: a request
     * that was already decided is left alone. Returns whether a row
     * actually changed.
     */
    public function putuskan(int $id, int $status, ?string $alasanTolak = null): bool
    {
        $this->db->table($this->table)
            ->where('id_surat', $id)
            ->where('id_rt', current_rt_id())
            ->where('status_surat', self::STATUS_MENUNGGU)
            ->update([
                'status_surat' => $status,
                'alasan_tolak' => $status === self::STATUS_DITOLAK ? $alasanTolak : null,
            ]);

        return $this->db->affectedRows() > 0;
    }
}
