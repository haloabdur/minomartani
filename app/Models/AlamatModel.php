<?php

namespace App\Models;

use CodeIgniter\Model;

class AlamatModel extends Model
{
    protected $table         = 'alamat';
    protected $primaryKey    = 'id_alamat';
    protected $allowedFields = ['nomor', 'alamat', 'kode_rumah', 'qrcode', 'id_rt'];

    public function all()
    {
        return $this->db->table($this->table)
            ->select('alamat.id_alamat, alamat.alamat, alamat.qrcode, alamat.kode_rumah, COUNT(warga.id_warga) jumlah')
            ->join('warga', 'warga.id_alamat = alamat.id_alamat AND warga.status_warga = 1', 'left')
            ->where('alamat.id_rt', current_rt_id())
            ->groupBy('alamat.id_alamat')
            ->get()->getResult();
    }

    public function alamat_detail($kode)
    {
        return $this->db->table($this->table)
            ->select('nama_warga, alamat')
            ->where('qrcode', $kode)
            ->where('id_status_keluarga', 1)
            ->where('alamat.id_rt', current_rt_id())
            ->join('warga', 'warga.id_alamat = alamat.id_alamat')
            ->get()->getRow();
    }

    public function cek_alamat($jalan, $nomor, $idRt = null)
    {
        $idRt = $idRt ?? current_rt_id();
        return $this->db->table($this->table)
            ->where('alamat', $jalan . '/' . $nomor)
            ->where('alamat.id_rt', $idRt)
            ->get()->getNumRows();
    }

    public function alamat_count()
    {
        return $this->db->table($this->table)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function kosong_count()
    {
        return $this->db->table($this->table)
            ->where('id_alamat NOT IN (SELECT id_alamat FROM warga WHERE status_warga = 1 AND id_alamat IS NOT NULL)', null, false)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getNumRows();
    }

    public function detail($id)
    {
        return $this->db->table($this->table)
            ->where('id_alamat', $id)
            ->where('alamat.id_rt', current_rt_id())
            ->get()->getRow();
    }

    /**
     * Owning tenant of a QR code, ignoring the current tenant scope.
     * Used only to 301-redirect legacy unprefixed /detail/{kode} links
     * (printed before multi-tenancy) to their correct slug-prefixed URL.
     */
    public function findRtByQrcode(string $kode): ?int
    {
        $row = $this->db->table($this->table)
            ->select('id_rt')
            ->where('qrcode', $kode)
            ->get()->getRow();

        return $row === null ? null : (int) $row->id_rt;
    }
}
