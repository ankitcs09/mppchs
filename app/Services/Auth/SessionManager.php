<?php

namespace App\Services\Auth;

use App\Services\Auth\SessionRegistry;
use App\Services\RbacService;
use Config\Services;
use Throwable;

class SessionManager
{
    private RbacService $rbac;
    private SessionRegistry $registry;

    public function __construct(?RbacService $rbac = null, ?SessionRegistry $registry = null)
    {
        $this->rbac      = $rbac ?? Services::rbac();
        $this->registry  = $registry ?? new SessionRegistry();
    }

    /**
     * Finalises a successful login by updating session state, ensuring roles,
     * and computing the appropriate redirect/flash payload.
     *
     * @return array{redirect:string,flash:array{type:string,message:string}}
     */
    public function finalizeLogin(array $user, string $authTable, string $method = 'password', ?array $flashOverride = null): array
    {
        $this->ensureBeneficiaryRole($user);
        $this->setUserSession($user, $authTable, $method);

        $redirect = $this->resolvePostLoginRedirect($user, $authTable);
        $flash    = $flashOverride ?? $this->defaultFlashFor($user, $method);

        log_message('debug', '[Auth][SessionManager] Finalized login for user {id} via {method} redirect={redirect}', [
            'id'       => $user['id'] ?? null,
            'method'   => $method,
            'redirect' => $redirect,
        ]);

        return [
            'redirect' => $redirect,
            'flash'    => $flash,
        ];
    }

    public function logout(string $message = 'You have been signed out.', string $flashType = 'success'): void
    {
        $session   = session();
        $userId    = (int) ($session->get('id') ?? 0);
        $authTable = (string) ($session->get('authUserTable') ?? 'app_users');

        try {
            $this->rbac->clearContextCache();
        } catch (Throwable $exception) {
            log_message('debug', '[Auth] Unable to clear RBAC cache on logout: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        $session->remove([
            'id',
            'authUserTable',
            'beneficiary_id',
            'beneficiary_v2_id',
            'bname',
            'username',
            'isLoggedIn',
            'login_method',
            'forcePasswordReset',
            'company_id',
            'session_version',
            'otp_login_mobile',
            'otp_login_mobile_masked',
            'otp_login_user_id',
            'otp_login_mobile_full',
            'rbac.context',
        ]);

        if ($message !== '') {
            $session->setFlashdata($flashType, $message);
        }
        $session->regenerate(true);

        if ($userId > 0) {
            $this->registry->clearUser($userId, $authTable);
        }

        log_message('info', '[Auth][SessionManager] Session cleared during logout');
    }

    public function clearOtpSession(): void
    {
        session()->remove([
            'otp_login_mobile',
            'otp_login_mobile_masked',
            'otp_login_user_id',
            'otp_last_sent',
            'otp_login_mobile_full',
        ]);
    }

    private function setUserSession(array $user, string $authTable, string $method): void
    {
        $isAppUser = $authTable === 'app_users';

        $beneficiaryIdLegacy = 0;
        $beneficiaryV2Id     = null;
        $displayName         = $user['display_name'] ?? $user['bname'] ?? $user['username'] ?? null;
        $companyId           = $isAppUser ? ($user['company_id'] ?? null) : null;

        if ($isAppUser) {
            $beneficiaryV2Id     = $user['beneficiary_v2_id'] ?? null;
            $beneficiaryIdLegacy = $user['legacy_beneficiary_id'] ?? 0;
        } else {
            $beneficiaryIdLegacy = $user['beneficiary_id'] ?? 0;
            $beneficiaryV2Id     = $user['beneficiary_v2_id'] ?? null;
        }

        $sessionVersion = $isAppUser ? (int) ($user['session_version'] ?? 1) : 0;

        $sessionData = [
            'id'                 => $user['id'],
            'authUserTable'      => $authTable,
            'beneficiary_id'     => $beneficiaryIdLegacy,
            'beneficiary_v2_id'  => $beneficiaryV2Id,
            'bname'              => $displayName,
            'username'           => $user['username'] ?? null,
            'isLoggedIn'         => true,
            'login_method'       => $method,
            'forcePasswordReset' => ! empty($user['force_password_reset']),
            'company_id'         => $companyId,
            'session_version'    => $sessionVersion,
        ];

        $session = session();
        $session->remove('rbac.context');
        $session->set($sessionData);
        $session->regenerate(true);

        if (! empty($user['id'])) {
            $this->registry->recordActiveSession((int) $user['id'], session_id(), $authTable);
        }

        try {
            $this->rbac->clearContextCache();
        } catch (Throwable $exception) {
            log_message('debug', '[Auth] Unable to clear RBAC cache on login: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        log_message('debug', '[Auth][SessionManager] Session initialised for user {id} method={method}', [
            'id'     => $user['id'] ?? null,
            'method' => $method,
        ]);
    }

    private function resolvePostLoginRedirect(array $user, string $authTable): string
    {
        $default = site_url('dashboard');

        try {
            $context = $this->rbac->context(true);
        } catch (Throwable $exception) {
            log_message('debug', '[Auth] Unable to fetch RBAC context for redirect: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return $default;
        }

        log_message(
            'debug',
            '[Auth] post-login context for user {user} :: roles={roles} permissions={perms}',
            [
                'user'  => $context['user_id'] ?? session()->get('id'),
                'roles' => json_encode(array_map(static function (array $role): array {
                    return [
                        'slug'             => $role['slug'] ?? null,
                        'default_redirect' => $role['default_redirect'] ?? null,
                        'company_id'       => $role['company_id'] ?? null,
                    ];
                }, $context['roles'] ?? [])),
                'perms' => json_encode(array_keys($context['permission_set'] ?? [])),
            ]
        );

        foreach ($context['roles'] ?? [] as $role) {
            if (empty($role['default_redirect'])) {
                continue;
            }

            $target = ltrim((string) $role['default_redirect'], '/');
            if ($target === '') {
                continue;
            }

            if ($this->canAccessDashboardTarget($target, $context)) {
                log_message('debug', '[Auth] redirecting via role default {target}', ['target' => $target]);
                return site_url($target);
            }

            log_message('debug', '[Auth] role default blocked {target}', ['target' => $target]);
        }

        $userType      = $user['user_type'] ?? null;
        $isBeneficiary = $userType === 'beneficiary' || $authTable !== 'app_users';

        if ($isBeneficiary) {
            log_message('debug', '[Auth] beneficiary fallback redirect to /dashboard');
            return site_url('dashboard');
        }

        if ($this->canAccessDashboardTarget('dashboard/v2', $context)) {
            log_message('debug', '[Auth] staff fallback redirect to /dashboard/v2');
            return site_url('dashboard/v2');
        }

        log_message('debug', '[Auth] default redirect to /dashboard');
        return $default;
    }

    private function defaultFlashFor(array $user, string $method): array
    {
        $displayName = $user['display_name'] ?? $user['bname'] ?? $user['username'] ?? 'user';

        if ($method === 'password') {
            if (! empty($user['force_password_reset'])) {
                return [
                    'type'    => 'warning',
                    'message' => 'Signed in with a temporary password. Please change it after logging in.',
                ];
            }

            return [
                'type'    => 'success',
                'message' => 'Welcome back, ' . $displayName . '!',
            ];
        }

        return [
            'type'    => 'success',
            'message' => 'Signed in successfully.',
        ];
    }

    private function ensureBeneficiaryRole(array $user): void
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $userType = strtolower((string) ($user['user_type'] ?? ''));
        $isBeneficiary = $userType === 'beneficiary' || ! empty($user['beneficiary_v2_id']);

        if (! $isBeneficiary) {
            return;
        }

        $companyId = isset($user['company_id']) ? (int) $user['company_id'] : null;

        try {
            $this->rbac->ensureRoleAssignment($userId, 'pensioner', $companyId);
        } catch (Throwable $exception) {
            log_message('error', '[Auth] Unable to ensure pensioner role for user {user}: {message}', [
                'user'    => $userId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function canAccessDashboardTarget(string $target, array $context): bool
    {
        if (str_starts_with($target, 'dashboard/v2')) {
            return isset($context['permission_set']['view_dashboard_company'])
                || isset($context['permission_set']['view_dashboard_global']);
        }

        return true;
    }
}
