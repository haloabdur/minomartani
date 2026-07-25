<?php

if (! function_exists('default_admin_route')) {
    /**
     * Where to land a logged-in admin-area user by default: the first
     * menu they can actually reach, in a fixed priority order - rather
     * than a blanket 'admin/dashboard' that per-user menu permissions
     * (see Config\AuthGroups + Admin\Users) may not grant them.
     *
     * superadmin/developer hold a 'menu.*' matrix wildcard, so they
     * always land on the dashboard as before. Every other menu -
     * including Kesehatan Lansia for 'rw' - is a normal per-user
     * permission with no group-wide bypass (see Config\AuthGroups +
     * Admin\Users), so 'rw' and 'admin' both fall through the same
     * "first menu they actually have" logic, just against a different
     * ordered list.
     */
    function default_admin_route(): string
    {
        $user = auth()->user();

        if ($user === null) {
            return 'login';
        }

        if ($user->inGroup('superadmin') || $user->inGroup('developer')) {
            return 'admin/dashboard';
        }

        $ordered = $user->inGroup('rw')
            ? [
                'menu.rekap'     => 'admin/rekap',
                'menu.kesehatan' => 'admin/kesehatan',
            ]
            : [
                'menu.warga'     => 'admin/warga',
                'menu.alamat'    => 'admin/alamat',
                'menu.berita'    => 'admin/berita',
                'menu.kesehatan' => 'admin/kesehatan',
            ];

        foreach ($ordered as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return 'admin/dashboard';
    }
}
