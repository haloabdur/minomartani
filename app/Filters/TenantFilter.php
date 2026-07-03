<?php

namespace App\Filters;

use App\Models\RtModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Establishes the tenant context for the admin panel. Runs after
 * Shield's 'session' filter (route group order), so an unauthenticated
 * request never reaches the tenant checks.
 *
 * This is one of two isolation layers: the second is the explicit
 * WHERE id_rt = current_rt_id() in every model query.
 */
class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return; // Shield's session filter handles this case
        }

        $user    = auth()->user();
        $session = session();
        $path    = implode('/', $request->getUri()->getSegments());

        if ($user->inGroup('superadmin')) {
            if ($session->get('tenant_rt_id') === null) {
                $first = model(RtModel::class)->aktif()[0] ?? null;
                if ($first !== null) {
                    $session->set('tenant_rt_id', (int) $first->id_rt);
                }
            }

            return;
        }

        if ($user->inGroup('rw')) {
            if (empty($user->id_rw)) {
                auth()->logout();

                return redirect()->to('login');
            }

            $session->set('tenant_rw_id', (int) $user->id_rw);

            // RW accounts are read-only: rekap is their only surface.
            if (strpos($path, 'admin/rekap') !== 0) {
                return redirect()->to('admin/rekap');
            }

            return;
        }

        // Regular RT admin: must belong to an RT.
        if (empty($user->id_rt)) {
            auth()->logout();
            // kbw helper is normally loaded by BaseController, which
            // has not run yet inside a before-filter.
            helper('kbw');
            setFlashData('error', 'Akun Anda belum terhubung ke RT mana pun. Hubungi superadmin.');

            return redirect()->to('login');
        }

        $session->set('tenant_rt_id', (int) $user->id_rt);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
