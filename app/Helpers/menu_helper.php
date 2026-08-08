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
                'menu.presensi'  => 'admin/presensi',
            ]
            : [
                'menu.warga'     => 'admin/warga',
                'menu.alamat'    => 'admin/alamat',
                'menu.berita'    => 'admin/berita',
                'menu.kesehatan' => 'admin/kesehatan',
                'menu.presensi'  => 'admin/presensi',
            ];

        foreach ($ordered as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return 'admin/dashboard';
    }
}

if (! function_exists('can_export')) {
    /**
     * Whether the current user may download resident data anywhere in the
     * app - Warga export, Rekap RW export, Kesehatan export/cetak,
     * Presensi export. Views use this to hide the download buttons;
     * the URLs themselves are enforced by the 'menuaccess:export' route
     * filter (see Config\Routes), so hiding the button is presentation,
     * not the security boundary.
     *
     * Unlike the other menu permissions this one is never granted by
     * default and was never backfilled: an account can only export once a
     * superadmin explicitly ticks it in Admin\Users. superadmin and
     * developer pass via their 'menu.*' matrix wildcard.
     */
    function can_export(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('menu.export');
    }
}
