<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_log';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_rt', 'user_id', 'user_name', 'module', 'record_id',
        'record_label', 'action', 'changes', 'ip_address',
    ];

    /** Modul yang tercatat, untuk dropdown filter. */
    public const MODULES = [
        'warga' => 'Warga',
    ];

    public const MODULE_ICONS = [
        'warga' => 'fas fa-users',
    ];

    public const ACTIONS = [
        'create' => ['Tambah', 'success', 'fas fa-plus-circle'],
        'update' => ['Ubah', 'info', 'fas fa-pencil-alt'],
        'delete' => ['Hapus', 'danger', 'fas fa-trash-alt'],
    ];

    /**
     * Log tenant aktif, terbaru dulu. Filter: q, action, module, record_id,
     * user_name, dari, sampai (Y-m-d). Dipanggil lalu ->paginate().
     */
    public function forCurrentRt(array $filters): self
    {
        $this->where('activity_log.id_rt', current_rt_id())
            ->orderBy('activity_log.id', 'DESC');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $this->groupStart()
                ->like('activity_log.record_label', $q)
                ->orLike('activity_log.user_name', $q)
                ->groupEnd();
        }
        if (! empty($filters['action'])) {
            $this->where('activity_log.action', $filters['action']);
        }
        if (! empty($filters['module'])) {
            $this->where('activity_log.module', $filters['module']);
        }
        if (! empty($filters['record_id'])) {
            $this->where('activity_log.record_id', (int) $filters['record_id']);
        }
        if (! empty($filters['user_name'])) {
            $this->where('activity_log.user_name', $filters['user_name']);
        }
        if (! empty($filters['dari'])) {
            $this->where('activity_log.created_at >=', $filters['dari'] . ' 00:00:00');
        }
        if (! empty($filters['sampai'])) {
            $this->where('activity_log.created_at <=', $filters['sampai'] . ' 23:59:59');
        }

        return $this;
    }

    /**
     * Hitung ringkasan statistik aktivitas untuk tenant aktif.
     */
    public function getStats(): array
    {
        $idRt  = current_rt_id();
        $today = date('Y-m-d');

        $row = $this->db->table($this->table)
            ->select("
                COUNT(*) as total,
                SUM(CASE WHEN action = 'create' THEN 1 ELSE 0 END) as total_create,
                SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as total_update,
                SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as total_delete,
                SUM(CASE WHEN created_at >= '{$today} 00:00:00' THEN 1 ELSE 0 END) as total_today
            ")
            ->where('id_rt', $idRt)
            ->get()->getRowArray();

        return [
            'total'        => (int) ($row['total'] ?? 0),
            'total_create' => (int) ($row['total_create'] ?? 0),
            'total_update' => (int) ($row['total_update'] ?? 0),
            'total_delete' => (int) ($row['total_delete'] ?? 0),
            'total_today'  => (int) ($row['total_today'] ?? 0),
        ];
    }

    /** @return string[] nama user yang pernah tercatat di tenant aktif */
    public function userNames(): array
    {
        $rows = $this->db->table($this->table)
            ->select('user_name')->distinct()
            ->where('id_rt', current_rt_id())
            ->orderBy('user_name')
            ->get()->getResultArray();

        return array_column($rows, 'user_name');
    }
}
