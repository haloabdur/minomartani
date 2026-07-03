<?php

use App\Libraries\TenantContext;
use App\Models\RtModel;

if (! function_exists('tenant_set_rt')) {
    /**
     * Sets the request-scoped tenant (public slug pages, tests).
     */
    function tenant_set_rt(?int $idRt): void
    {
        TenantContext::$rtId = $idRt;
    }
}

if (! function_exists('current_rt_id')) {
    /**
     * The active tenant. Request override first (public pages), then
     * the session context established by TenantFilter (admin pages).
     */
    function current_rt_id(): ?int
    {
        if (TenantContext::$rtId !== null) {
            return TenantContext::$rtId;
        }

        $id = session('tenant_rt_id');

        return $id === null ? null : (int) $id;
    }
}

if (! function_exists('current_rt')) {
    function current_rt(): ?object
    {
        $id = current_rt_id();

        return $id === null ? null : model(RtModel::class)->find($id);
    }
}

if (! function_exists('current_rw_id')) {
    function current_rw_id(): ?int
    {
        $id = session('tenant_rw_id');

        return $id === null ? null : (int) $id;
    }
}
