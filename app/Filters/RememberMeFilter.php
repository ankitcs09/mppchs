<?php

namespace App\Filters;

use App\Services\Auth\RememberMeService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class RememberMeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = Services::session();

        if ($session->get('isLoggedIn')) {
            return null;
        }

        $service = new RememberMeService();

        try {
            $service->attemptAutoLogin();
        } catch (Throwable $exception) {
            log_message('error', '[RememberMe] Auto-login failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
