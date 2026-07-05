<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController - Migrated from CI3
 * Provides loadViews() and load_view() layout patterns
 */
abstract class BaseController extends Controller
{
    protected $global = [];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['form', 'url', 'kbw'];

        parent::initController($request, $response, $logger);
    }

    /**
     * Load admin layout views (header + sidebar + content + footer)
     */
    protected function loadViews(string $viewName, ?array $headerInfo = null, ?array $pageInfo = null, ?array $footerInfo = null): string
    {
        $headerData = $headerInfo ?? [];

        return view('layouts/header', $headerData)
            . view($viewName, $pageInfo ?? [])
            . view('layouts/footer', $footerInfo ?? []);
    }

    /**
     * Load front-end layout views (includes/header + content + includes/footer)
     */
    protected function load_view(string $viewName, ?array $data = null): string
    {
        return view('includes/header', $data ?? [])
            . view($viewName, $data ?? [])
            . view('includes/footer', $data ?? []);
    }

    /**
     * Resolve the current tenant from the host header or public slug
     */
    protected function resolveTenant(?string $slug): void
    {
        $hostTenant = resolve_tenant_by_host(request_host($this->request));

        if ($hostTenant !== null && $hostTenant['type'] === 'rw') {
            // RW hosts have no RT-scoped public content beyond the
            // dedicated RW landing page (Home::index() branches to that
            // page before ever calling resolveTenant() — see below). Any
            // other RT-shaped public route (alamat/berita/layanan) hit on
            // an RW host is a 404 by design.
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($hostTenant !== null && $hostTenant['type'] === 'rt') {
            // Host wins over path-slug/default.
            tenant_set_rt((int) $hostTenant['rt']->id_rt);
            return;
        }

        if (empty($slug)) {
            // Default fallback is RT 29 (id_rt = 1)
            tenant_set_rt(1);
            return;
        }

        $rt = model(\App\Models\RtModel::class)->where('slug', $slug)->first();
        if ($rt !== null && (int)$rt->is_aktif === 1) {
            tenant_set_rt((int) $rt->id_rt);
        } else {
            // Fallback to RT 29 (id_rt = 1) if not found or inactive
            tenant_set_rt(1);
        }
    }
}
