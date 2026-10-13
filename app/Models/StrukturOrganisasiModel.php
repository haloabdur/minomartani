<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Struktur organisasi dua level. level 'rt' dimiliki satu RT (id_rt), level
 * 'rw' dimiliki satu RW (id_rw) dan dibagi bersama semua RT di RW itu.
 * Tiap query harus lewat scope level + pemilik; Model::update()/delete()
 * hanya memfilter PK, jadi controller wajib memanggil detail() lebih dulu.
 */
class StrukturOrganisasiModel extends Model
{
    public const LEVELS = ['rt', 'rw'];

    protected $table         = 'struktur_organisasi';
    protected $primaryKey    = 'id_struktur';
    protected $allowedFields = ['level', 'id_rt', 'id_rw', 'id_warga', 'jabatan', 'id_atasan', 'urutan', 'created_at', 'updated_at'];

    /** RW induk dari RT aktif (admin RT tidak punya current_rw_id()). */
    public function rwId(): ?int
    {
        $rt = current_rt();

        return ($rt !== null && $rt->id_rw !== null) ? (int) $rt->id_rw : null;
    }

    /**
     * RT yang boleh dijangkau untuk $level: RT aktif saja (level rt) atau
     * semua RT aktif dalam RW induk (level rw).
     *
     * @return int[]
     */
    public function rtIds(string $level): array
    {
        if ($level === 'rt') {
            $id = current_rt_id();

            return $id === null ? [] : [$id];
        }

        $idRw = $this->rwId();
        if ($idRw === null) {
            return [];
        }

        return array_map(static fn ($rt) => (int) $rt->id_rt, model(RtModel::class)->byRw($idRw));
    }

    /** Menerapkan filter level + pemilik; false bila pemilik tidak ada (RT tanpa RW). */
    private function scope($builder, string $level, string $alias = 'struktur_organisasi'): bool
    {
        $builder->where($alias . '.level', $level);

        if ($level === 'rt') {
            $builder->where($alias . '.id_rt', current_rt_id());

            return true;
        }

        $idRw = $this->rwId();
        if ($idRw === null) {
            return false;
        }

        $builder->where($alias . '.id_rw', $idRw);

        return true;
    }

    /** Semua jabatan pada $level, lengkap dengan nama warga dan atasan. */
    public function daftar(string $level): array
    {
        $builder = $this->db->table($this->table)
            ->select('struktur_organisasi.*, warga.nama_warga, rt.nama AS nama_rt, atasan.jabatan AS jabatan_atasan')
            ->join('warga', 'warga.id_warga = struktur_organisasi.id_warga', 'left')
            ->join('rt', 'rt.id_rt = warga.id_rt', 'left')
            ->join('struktur_organisasi atasan', 'atasan.id_struktur = struktur_organisasi.id_atasan', 'left');

        if (! $this->scope($builder, $level)) {
            return [];
        }

        return $builder->orderBy('struktur_organisasi.urutan')
            ->orderBy('struktur_organisasi.id_struktur')
            ->get()->getResult();
    }

    /** Satu jabatan, hanya bila milik RT aktif atau RW induknya. */
    public function detail($id): ?object
    {
        $row = $this->db->table($this->table)
            ->where('id_struktur', (int) $id)
            ->get()->getRow();

        if ($row === null) {
            return null;
        }

        if ($row->level === 'rt') {
            return (int) $row->id_rt === (int) current_rt_id() ? $row : null;
        }

        $idRw = $this->rwId();

        return ($idRw !== null && (int) $row->id_rw === $idRw) ? $row : null;
    }

    /** Pilihan warga untuk $level (RT: warga RT aktif; RW: warga semua RT di RW). */
    public function wargaOptions(string $level): array
    {
        return model(WargaModel::class)->allByRtIds($this->rtIds($level));
    }

    /** Menolak id_warga yang bukan milik cakupan $level. */
    public function wargaValid(string $level, int $idWarga): bool
    {
        return model(WargaModel::class)->oneByRtIds($idWarga, $this->rtIds($level)) !== null;
    }

    /**
     * Pilihan atasan: jabatan sepelevel, tanpa dirinya sendiri dan
     * turunannya (supaya tidak membentuk siklus).
     */
    public function atasanOptions(string $level, ?int $selfId = null): array
    {
        $rows = $this->daftar($level);
        if ($selfId === null) {
            return $rows;
        }

        $exclude = array_flip($this->descendantIds($rows, $selfId));
        $exclude[$selfId] = true;

        return array_values(array_filter($rows, static fn ($r) => ! isset($exclude[(int) $r->id_struktur])));
    }

    /**
     * @param object[] $rows
     * @return int[]
     */
    private function descendantIds(array $rows, int $rootId): array
    {
        $children = [];
        foreach ($rows as $r) {
            if ($r->id_atasan !== null) {
                $children[(int) $r->id_atasan][] = (int) $r->id_struktur;
            }
        }

        $result = [];
        $queue  = [$rootId];
        while ($queue) {
            $id = array_shift($queue);
            foreach ($children[$id] ?? [] as $child) {
                if (! in_array($child, $result, true)) {
                    $result[] = $child;
                    $queue[]  = $child;
                }
            }
        }

        return $result;
    }

    /** Atasan boleh kosong; bila diisi harus sepelevel, satu pemilik, bukan diri sendiri/turunannya. */
    public function atasanValid(string $level, ?int $idAtasan, ?int $selfId = null): bool
    {
        if ($idAtasan === null) {
            return true;
        }

        foreach ($this->atasanOptions($level, $selfId) as $row) {
            if ((int) $row->id_struktur === $idAtasan) {
                return true;
            }
        }

        return false;
    }

    /** Hapus satu jabatan; bawahannya naik ke atasan jabatan itu. */
    public function hapus(object $row): void
    {
        $this->db->transStart();
        $this->db->table($this->table)
            ->where('id_atasan', $row->id_struktur)
            ->update(['id_atasan' => $row->id_atasan]);
        $this->db->table($this->table)
            ->where('id_struktur', $row->id_struktur)
            ->delete();
        $this->db->transComplete();
    }

    /**
     * Data pohon gabungan untuk renderer: pengurus RW di atas, akar
     * pengurus RT digantung di bawah akar RW pertama.
     *
     * @return array{nodes: list<array>, edges: list<array>, has_data: bool}
     */
    public function tree(): array
    {
        $rw = $this->daftar('rw');
        $rt = $this->daftar('rt');
        $all = array_merge($rw, $rt);

        $byWarga = [];
        foreach ($all as $r) {
            $byWarga[(int) $r->id_warga][] = $r;
        }

        $nodes = [];
        foreach ($all as $r) {
            $rangkap = [];
            foreach ($byWarga[(int) $r->id_warga] as $other) {
                if ((int) $other->id_struktur !== (int) $r->id_struktur) {
                    $rangkap[] = $other->jabatan . ' ' . strtoupper($other->level);
                }
            }

            $nodes[] = [
                'id'      => 's' . $r->id_struktur,
                'nama'    => (string) $r->nama_warga,
                'jabatan' => (string) $r->jabatan,
                'level'   => $r->level,
                'nama_rt' => $r->level === 'rw' ? (string) $r->nama_rt : '',
                'rangkap' => $rangkap,
            ];
        }

        $edges = [];
        $rwRoot = null;
        foreach ($rw as $r) {
            if ($r->id_atasan === null && $rwRoot === null) {
                $rwRoot = 's' . $r->id_struktur;
            }
        }

        foreach ($all as $r) {
            if ($r->id_atasan !== null) {
                $edges[] = ['from' => 's' . $r->id_atasan, 'to' => 's' . $r->id_struktur, 'link' => false];
            } elseif ($r->level === 'rt' && $rwRoot !== null) {
                $edges[] = ['from' => $rwRoot, 'to' => 's' . $r->id_struktur, 'link' => true];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges, 'has_data' => $nodes !== []];
    }
}
