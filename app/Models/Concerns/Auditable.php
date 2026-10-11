<?php

namespace App\Models\Concerns;

use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Mencatat create/update/delete model ke tabel `activity_log`.
 *
 * Pakai di model dengan:
 *
 *     use Auditable;
 *     protected string $auditModule     = 'warga';
 *     protected string $auditLabelField = 'nama_warga';
 *
 * Trait ini mendefinisikan initialize() untuk mendaftarkan callback, jadi
 * model yang memakainya tidak boleh punya initialize() sendiri (atau harus
 * memanggil registerAuditCallbacks()). Hanya jalur lewat Model::insert/
 * update/delete yang tercatat; query builder langsung tidak lewat callback.
 * Kegagalan menulis log tidak membatalkan operasi utamanya, hanya dicatat
 * ke log error.
 */
trait Auditable
{
    /** @var array<int|string, array<string, mixed>> row sebelum update/delete, keyed by PK */
    private array $auditOldRows = [];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    protected function registerAuditCallbacks(): void
    {
        $this->afterInsert[]  = 'auditInsert';
        $this->beforeUpdate[] = 'auditCaptureOld';
        $this->afterUpdate[]  = 'auditUpdate';
        $this->beforeDelete[] = 'auditCaptureOld';
        $this->afterDelete[]  = 'auditDelete';
    }

    protected function auditInsert(array $event): array
    {
        $id = $event['id'] ?? null;
        if (! $id) {
            return $event;
        }

        $row     = (array) ($event['data'] ?? []);
        $changes = [];
        foreach ($row as $field => $value) {
            if ($field !== $this->primaryKey && ! $this->auditIsIgnored($field) && $this->auditNormalize($value) !== '') {
                $changes[$field] = [null, $value];
            }
        }

        $this->auditWrite('create', $id, $row, $changes);

        return $event;
    }

    protected function auditCaptureOld(array $event): array
    {
        $ids = $event['id'] ?? null;
        if ($ids === null) {
            return $event;
        }

        $this->auditOldRows = [];
        $rows = $this->db->table($this->table)
            ->whereIn($this->primaryKey, (array) $ids)
            ->get()->getResultArray();
        foreach ($rows as $row) {
            $this->auditOldRows[$row[$this->primaryKey]] = $row;
        }

        return $event;
    }

    protected function auditUpdate(array $event): array
    {
        if (empty($event['result'])) {
            return $event;
        }

        $new = (array) ($event['data'] ?? []);
        foreach ((array) ($event['id'] ?? []) as $id) {
            $old = $this->auditOldRows[$id] ?? null;
            if ($old === null) {
                continue;
            }

            $changes = [];
            foreach ($new as $field => $value) {
                if ($field === $this->primaryKey || $this->auditIsIgnored($field) || ! array_key_exists($field, $old)) {
                    continue;
                }
                if ($this->auditNormalize($old[$field]) !== $this->auditNormalize($value)) {
                    $changes[$field] = [$old[$field], $value];
                }
            }

            if ($changes !== []) {
                $this->auditWrite('update', $id, $old, $changes);
            }
        }
        $this->auditOldRows = [];

        return $event;
    }

    protected function auditDelete(array $event): array
    {
        if (empty($event['result'])) {
            return $event;
        }

        foreach ((array) ($event['id'] ?? []) as $id) {
            $old = $this->auditOldRows[$id] ?? null;
            if ($old === null) {
                continue;
            }

            $changes = [];
            foreach ($old as $field => $value) {
                if ($field !== $this->primaryKey && ! $this->auditIsIgnored($field) && $this->auditNormalize($value) !== '') {
                    $changes[$field] = [$value, null];
                }
            }
            $this->auditWrite('delete', $id, $old, $changes);
        }
        $this->auditOldRows = [];

        return $event;
    }

    private function auditIsIgnored(string $field): bool
    {
        return property_exists($this, 'auditIgnoredFields') && in_array($field, (array) $this->auditIgnoredFields, true);
    }

    /** null dan '' dianggap sama; form kosong tidak boleh terhitung perubahan. */
    private function auditNormalize($value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function auditWrite(string $action, $recordId, array $row, array $changes): void
    {
        try {
            $userId   = null;
            $userName = 'system';
            if (! is_cli() || ENVIRONMENT === 'testing') {
                $user = auth()->user();
                if ($user !== null) {
                    $userId   = (int) $user->id;
                    $userName = $user->username ?: ('user#' . $user->id);
                }
            }

            $request = service('request');

            $this->db->table('activity_log')->insert([
                'id_rt'        => $row['id_rt'] ?? current_rt_id(),
                'user_id'      => $userId,
                'user_name'    => $userName,
                'module'       => $this->auditModule,
                'record_id'    => (int) $recordId,
                'record_label' => $row[$this->auditLabelField] ?? null,
                'action'       => $action,
                'changes'      => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address'   => $request instanceof IncomingRequest ? $request->getIPAddress() : null,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Auditable: gagal menulis activity_log: ' . $e->getMessage());
        }
    }
}
