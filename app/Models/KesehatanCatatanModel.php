<?php

namespace App\Models;

use CodeIgniter\Model;

class KesehatanCatatanModel extends Model
{
    protected $table         = 'kesehatan_catatan';
    protected $primaryKey    = 'id_catatan';
    protected $allowedFields = [
        'id_kegiatan',
        'id_warga',
        'id_rt',
        'tensi_sistol',
        'tensi_diastol',
        'berat_badan',
        'tinggi_badan',
        'lingkar_perut',
        'gula_darah',
        'gula_darah_ket',
        'kolesterol',
        'asam_urat',
        'catatan',
        'id_user',
    ];

    /** All recorded entries for one kegiatan, keyed by id_warga for easy lookup in the input form. */
    public function byKegiatan(int $idKegiatan): array
    {
        $rows = $this->db->table($this->table)
            ->select('kesehatan_catatan.*, warga.nama_warga')
            ->join('warga', 'warga.id_warga = kesehatan_catatan.id_warga')
            ->where('id_kegiatan', $idKegiatan)
            ->get()->getResult();

        $byWarga = [];
        foreach ($rows as $row) {
            $byWarga[(int) $row->id_warga] = $row;
        }

        return $byWarga;
    }

    /** One resident's history across every kegiatan they've attended, oldest first (chart order). */
    public function forWarga(int $idWarga): array
    {
        return $this->db->table($this->table)
            ->select('kesehatan_catatan.*, kesehatan_kegiatan.nama_kegiatan, kesehatan_kegiatan.tanggal_kegiatan')
            ->join('kesehatan_kegiatan', 'kesehatan_kegiatan.id_kegiatan = kesehatan_catatan.id_kegiatan')
            ->where('id_warga', $idWarga)
            ->orderBy('kesehatan_kegiatan.tanggal_kegiatan', 'ASC')
            ->get()->getResult();
    }

    /**
     * Insert or update the one row for (id_kegiatan, id_warga), and return
     * the resulting row fresh from the DB - so callers that need to know
     * what's actually recorded (e.g. the RFID scan endpoint, which must
     * hand back any pre-existing measurements rather than blank ones)
     * read the row this method itself just wrote, instead of running a
     * second, separate query that could drift out of sync.
     */
    public function upsert(int $idKegiatan, int $idWarga, int $idRt, array $data): object
    {
        $existing = $this->db->table($this->table)
            ->where('id_kegiatan', $idKegiatan)
            ->where('id_warga', $idWarga)
            ->get()->getRow();

        $data['id_kegiatan'] = $idKegiatan;
        $data['id_warga']    = $idWarga;
        $data['id_rt']       = $idRt;

        if ($existing === null) {
            $this->insert($data);
        } else {
            $this->update($existing->id_catatan, $data);
        }

        return $this->db->table($this->table)
            ->where('id_kegiatan', $idKegiatan)
            ->where('id_warga', $idWarga)
            ->get()->getRow();
    }
}
