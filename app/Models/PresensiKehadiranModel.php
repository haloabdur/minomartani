<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiKehadiranModel extends Model
{
    protected $table         = 'presensi_kehadiran';
    protected $primaryKey    = 'id_presensi';
    protected $allowedFields = [
        'id_acara',
        'id_warga',
        'id_rt',
        'status',
        'waktu',
        'id_user',
    ];

    /** The only two values `status` may hold - anything else is rejected by the controller. */
    public const STATUSES = ['hadir', 'tidak'];

    /** All recorded attendance for one acara, keyed by id_warga for easy lookup in the list. */
    public function byAcara(int $idAcara): array
    {
        $rows = $this->db->table($this->table)
            ->select('presensi_kehadiran.*, warga.nama_warga')
            ->join('warga', 'warga.id_warga = presensi_kehadiran.id_warga')
            ->where('id_acara', $idAcara)
            ->get()->getResult();

        $byWarga = [];
        foreach ($rows as $row) {
            $byWarga[(int) $row->id_warga] = $row;
        }

        return $byWarga;
    }

    /** One resident's attendance across every acara, newest first. */
    public function forWarga(int $idWarga): array
    {
        return $this->db->table($this->table)
            ->select('presensi_kehadiran.*, presensi_acara.nama_acara, presensi_acara.tanggal_acara, presensi_acara.tempat')
            ->join('presensi_acara', 'presensi_acara.id_acara = presensi_kehadiran.id_acara')
            ->where('id_warga', $idWarga)
            ->orderBy('presensi_acara.tanggal_acara', 'DESC')
            ->get()->getResult();
    }

    /**
     * Insert or update the one row for (id_acara, id_warga), and return
     * the resulting row fresh from the DB - so callers that need to know
     * what's actually recorded (e.g. the RFID scan endpoint, which reports
     * back whether this tap changed anything) read the row this method
     * itself just wrote instead of running a second, separate query.
     */
    public function upsert(int $idAcara, int $idWarga, int $idRt, string $status, ?int $idUser = null): object
    {
        $existing = $this->db->table($this->table)
            ->where('id_acara', $idAcara)
            ->where('id_warga', $idWarga)
            ->get()->getRow();

        $data = [
            'id_acara' => $idAcara,
            'id_warga' => $idWarga,
            'id_rt'    => $idRt,
            'status'   => $status,
            'waktu'    => date('Y-m-d H:i:s'),
            'id_user'  => $idUser,
        ];

        if ($existing === null) {
            $this->insert($data);
        } else {
            $this->update($existing->id_presensi, $data);
        }

        return $this->db->table($this->table)
            ->where('id_acara', $idAcara)
            ->where('id_warga', $idWarga)
            ->get()->getRow();
    }
}
