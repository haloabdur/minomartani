<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Route-level enforcement for the per-user "menu.*" permissions assigned
 * via Admin\Users (menu access checkboxes). Runs after Shield's 'session'
 * filter, so an unauthenticated request never reaches here.
 *
 * Usage: 'filter' => 'menuaccess:warga' checks can('menu.warga'). Extra
 * arguments after the first are Shield groups that bypass the permission
 * check entirely, e.g. 'menuaccess:kesehatan,rw' for a menu that a whole
 * group (not just individually-permissioned users) should always reach.
 * Superadmin/developer already hold a 'menu.*' wildcard in the group
 * matrix (see Config\AuthGroups), so they pass the can() check as-is.
 */
class MenuAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth()->user();

        if ($user === null) {
            return redirect()->to('login');
        }

        $arguments  = $arguments ?? [];
        $menu       = array_shift($arguments);
        $bypassGroups = $arguments;

        if ($menu !== null && $user->can('menu.' . $menu)) {
            return;
        }

        foreach ($bypassGroups as $group) {
            if ($user->inGroup($group)) {
                return;
            }
        }

        helper('kbw');
        setFlashData('error', 'Anda tidak memiliki akses ke menu ini.');

        return redirect()->to(default_admin_route());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
