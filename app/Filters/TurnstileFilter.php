<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

/**
 * Verifies the Cloudflare Turnstile widget response on POST requests
 * before Shield's LoginController ever sees the credentials. Scoped to
 * the 'login' route only (see app/Config/Filters.php $filters) - GET
 * requests pass through untouched so the widget can render.
 */
class TurnstileFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! config('Turnstile')->enabled) {
            return;
        }

        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $token = $request->getPost('cf-turnstile-response');

        if (empty($token) || ! $this->verify($token, $request->getIPAddress())) {
            return redirect()->route('login')->withInput()->with('error', 'Verifikasi keamanan gagal. Silakan coba lagi.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function verify(string $token, string $ip): bool
    {
        $secretKey = config('Turnstile')->secretKey;

        if ($secretKey === '') {
            log_message('critical', 'Turnstile secretKey is not configured; blocking login until it is set.');

            return false;
        }

        try {
            $client   = Services::curlrequest(['http_errors' => false]);
            $response = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret'   => $secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ],
            ]);

            $result = json_decode($response->getBody(), true);
        } catch (Throwable $e) {
            log_message('error', 'Turnstile verification request failed: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }

        return (bool) ($result['success'] ?? false);
    }
}
