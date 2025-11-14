<?php

namespace App\Filters;

use App\Models\AppUserModel;
use App\Services\Auth\RememberMeService;
use App\Services\Auth\SessionManager;
use App\Services\Auth\SessionRegistry;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Config\Services;

class ForcePasswordReset implements FilterInterface
{
    private const HANDOFF_STALE_SECONDS = 7_200; // 120 minutes

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = Services::session();

        if (! $session->get('isLoggedIn')) {
            return null;
        }

        $registry = new SessionRegistry();
        $currentSessionId = session_id();
        $authTable        = (string) ($session->get('authUserTable') ?? 'app_users');

        if ($session->get('id')) {
            $userId = (int) $session->get('id');
            $active = $registry->getActiveSession((int) $session->get('id'), $authTable);
            if ($active && $active['session_id'] !== $currentSessionId) {
                $lastSeenTs = null;
                if (! empty($active['last_seen_at'])) {
                    try {
                        $lastSeenTs = Time::parse($active['last_seen_at'], 'UTC')->getTimestamp();
                    } catch (\Throwable $exception) {
                        $lastSeenTs = null;
                    }
                }
                $isStale = ! is_int($lastSeenTs)
                    || (time() - $lastSeenTs) >= self::HANDOFF_STALE_SECONDS;

                if ($isStale || ! $registry->sessionExists($active['session_id'])) {
                    $registry->recordActiveSession($userId, $currentSessionId, $authTable);
                } else {
                    $rememberService = new RememberMeService();
                    $rememberService->forgetCurrentDevice();
                    $sessionManager = new SessionManager();
                    $sessionManager->logout('Your session ended because you signed in on another device.', 'warning');

                    return redirect()->to(site_url('login'));
                }
            } else {
                // Ensure the current session keeps its registry entry fresh
                $registry->recordActiveSession($userId, $currentSessionId, $authTable);
            }
        }

        $loginMethod = (string) $session->get('login_method');
        if ($loginMethod === 'otp') {
            return null;
        }

        if ($authTable === 'app_users') {
            $userId = (int) $session->get('id');
            $storedVersion = (int) ($session->get('session_version') ?? 0);
            if ($userId > 0) {
                $appUsers = new AppUserModel();
                $row = $appUsers->select('session_version')->find($userId);
                $currentVersion = (int) ($row['session_version'] ?? 0);
                if ($currentVersion > 0) {
                    if ($storedVersion === 0) {
                        $session->set('session_version', $currentVersion);
                    } elseif ($storedVersion !== $currentVersion) {
                        $rememberService = new RememberMeService();
                        $rememberService->forgetCurrentDevice();
                        $sessionManager = new SessionManager();
                        $sessionManager->logout('Your session ended because your password or security settings changed.', 'warning');

                        return redirect()->to(site_url('login'));
                    }
                }
            }
        }

        if (! $session->get('forcePasswordReset')) {
            return null;
        }

        $currentPath = trim($request->getUri()->getPath(), '/');
        if (str_starts_with($currentPath, 'index.php/')) {
            $currentPath = substr($currentPath, strlen('index.php/'));
        }
        $allowed     = [
            'user/change-password',
            'logout',
            'logout/',
        ];

        foreach ($allowed as $pattern) {
            if ($currentPath === trim($pattern, '/')) {
                return null;
            }
        }

        if (str_starts_with($currentPath, 'user/change-password')) {
            return null;
        }

        return redirect()->to(site_url('user/change-password'))
            ->with('warning', 'Please update your password to continue.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no post-processing required
    }
}
