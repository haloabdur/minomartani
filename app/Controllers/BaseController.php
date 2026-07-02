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
}
