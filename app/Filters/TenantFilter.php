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

        $user       = auth()->user();
        $session    = session();
        $path       = implode('/', $request->getUri()->getSegments());
        $hostTenant = resolve_tenant_by_host(request_host($request));

        if ($user->inGroup('superadmin')) {
            // Superadmin can use any tenant's subdomain (auto-scoped) or the
            // central domain (existing session-dropdown behavior).
            if ($hostTenant !== null && $hostTenant['type'] === 'rt') {
                $session->set('tenant_rt_id', (int) $hostTenant['rt']->id_rt);
                return;
            }

            if ($hostTenant !== null && $hostTenant['type'] === 'rw') {
                $session->set('tenant_rw_id', (int) $hostTenant['rw']->id_rw);
                return;
            }

            // Central/apex/unmatched host: existing dropdown behaviour, unchanged.
            if ($session->get('tenant_rt_id') === null) {
                $first = model(RtModel::class)->aktif()[0] ?? null;
                if ($first !== null) {
                    $session->set('tenant_rt_id', (int) $first->id_rt);
                }
            }

            return;
        }

        // Cross-subdomain isolation: an authenticated RT/RW admin whose
        // id_rt/id_rw does not match the tenant resolved from THIS host is
        // rejected, even with otherwise-valid credentials for their own
        // tenant. Applies uniformly to RT and RW accounts.
        if ($hostTenant !== null && ! $this->hostMatchesUser($hostTenant, $user)) {
            auth()->logout();
            helper('kbw');
            setFlashData('error', 'Akun Anda tidak terdaftar untuk subdomain ini. Silakan masuk melalui subdomain RT/RW Anda sendiri.');

            return redirect()->to('login');
        }

        if ($user->inGroup('rw')) {
            if (empty($user->id_rw)) {
                auth()->logout();

                return redirect()->to('login');
            }

            $session->set('tenant_rw_id', (int) $user->id_rw);

            // RW accounts are otherwise read-only: rekap (read-only) and
            // kesehatan (read-write, cross-RT within their RW) are their
            // only surfaces.
            if (strpos($path, 'admin/rekap') !== 0 && strpos($path, 'admin/kesehatan') !== 0) {
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

    private function hostMatchesUser(array $hostTenant, $user): bool
    {
        if ($hostTenant['type'] === 'rt') {
            return ! empty($user->id_rt) && (int) $user->id_rt === (int) $hostTenant['rt']->id_rt;
        }

        return ! empty($user->id_rw) && (int) $user->id_rw === (int) $hostTenant['rw']->id_rw;
    }
}
