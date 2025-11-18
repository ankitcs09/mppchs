<?php

namespace App\Services\Auth;

use App\Models\AppUserModel;
use App\Models\BeneficiaryV2Model;
use App\Models\UserAccountModel;
use CodeIgniter\I18n\Time;

class LoginService
{
    private AppUserModel $appUsers;
    private UserAccountModel $legacyUsers;
    private BeneficiaryV2Model $beneficiaries;
    private SessionManager $sessionManager;
    private SessionRegistry $sessionRegistry;
    private RememberMeService $rememberService;

    public function __construct(
        ?AppUserModel $appUsers = null,
        ?UserAccountModel $legacyUsers = null,
        ?BeneficiaryV2Model $beneficiaries = null,
        ?SessionManager $sessionManager = null,
        ?SessionRegistry $sessionRegistry = null,
        ?RememberMeService $rememberService = null
    ) {
        $this->appUsers        = $appUsers ?? new AppUserModel();
        $this->legacyUsers     = $legacyUsers ?? new UserAccountModel();
        $this->beneficiaries   = $beneficiaries ?? new BeneficiaryV2Model();
        $this->sessionManager  = $sessionManager ?? new SessionManager();
        $this->sessionRegistry = $sessionRegistry ?? new SessionRegistry();
        $this->rememberService = $rememberService ?? new RememberMeService(
            null,
            null,
            $this->sessionManager,
            $this->appUsers,
            $this->legacyUsers,
            $this->beneficiaries
        );
    }

    /**
     * Attempts to authenticate the supplied credentials.
     *
     * @return array{
     *     success:bool,
     *     error?:string,
     *     redirect?:string,
     *     flash?:array{type:string,message:string}
     * }
     */
    public function attempt(string $username, string $password, bool $remember = false): array
    {
        $username = trim($username);
        log_message('debug', '[Auth][LoginService] Login attempt for {username}', ['username' => $username]);
        $password = (string) $password;

        $user      = $this->appUsers->findByUsername($username);
        $authTable = 'app_users';

        if ($user === null) {
            $user      = $this->legacyUsers->findByUsername($username);
            $authTable = 'tmusers';
        }

        if ($user === null) {
            log_message('notice', '[Auth][LoginService] No account matched username {username}', ['username' => $username]);
            return $this->failure('Invalid username or password.');
        }

        if ($authTable === 'app_users') {
            $status = strtolower((string) ($user['status'] ?? 'active'));
            if ($status === 'locked') {
                log_message('warning', '[Auth][LoginService] Locked account login attempt {username}', ['username' => $username]);
                return $this->failure('Your account is locked. Please contact the administrator.');
            }
            if ($status === 'disabled') {
                log_message('warning', '[Auth][LoginService] Disabled account login attempt {username}', ['username' => $username]);
                return $this->failure('Your account is disabled. Please contact the administrator.');
            }
        }

        $model      = $authTable === 'app_users' ? $this->appUsers : $this->legacyUsers;
        $storedHash = (string) ($user['password'] ?? '');
        $isValid    = $storedHash !== '' && password_verify($password, $storedHash);

        if (! $isValid && $storedHash !== '' && hash_equals($storedHash, $password)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $model->update($user['id'], [
                'password'             => $newHash,
                'force_password_reset' => 1,
                'password_changed_at'  => null,
            ]);

            $user['password']             = $newHash;
            $user['force_password_reset'] = 1;
            $storedHash                   = $newHash;
            $isValid                      = true;
        }

        if (! $isValid) {
            log_message('notice', '[Auth][LoginService] Invalid password for user {username}', ['username' => $username]);
            return $this->failure('Invalid username or password.');
        }

        if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $model->update($user['id'], [
                'password'            => $newHash,
                'password_changed_at' => Time::now('UTC')->toDateTimeString(),
            ]);

            $user['password'] = $newHash;
        }

        $now = Time::now('UTC')->toDateTimeString();
        if ($authTable === 'app_users') {
            $this->appUsers->update($user['id'], ['last_login_at' => $now]);
            if (! empty($user['beneficiary_v2_id'])) {
                $beneficiary = $this->beneficiaries->find((int) $user['beneficiary_v2_id']);
                $user['legacy_beneficiary_id'] = $beneficiary['legacy_beneficiary_id'] ?? 0;
            }
        }

        $handoff = $this->maybeDeferLogin($user, $authTable, 'password', $username, $remember);
        if ($handoff !== null) {
            return $handoff;
        }

        return $this->completeLogin($user, $authTable, 'password', $username, null, $remember);
    }

    public function logout(): void
    {
        $this->rememberService->forgetCurrentDevice();
        $this->sessionManager->logout();
    }

    public function getPendingHandoff(): ?array
    {
        $session = session();
        $payload = $session->get('login_handoff');
        if (! $payload) {
            return null;
        }

        $existing = $session->get('login_handoff_existing');

        return [
            'user'     => $payload['user'] ?? null,
            'method'   => $payload['method'] ?? 'password',
            'existing' => $existing,
            'remember' => (bool) ($payload['remember'] ?? false),
        ];
    }

    public function cancelPendingHandoff(): void
    {
        session()->remove('login_handoff');
        session()->remove('login_handoff_existing');
    }

    public function completePendingHandoff(): array
    {
        $session = session();
        $payload = $session->get('login_handoff');
        $existing = $session->get('login_handoff_existing');

        if (! $payload) {
            return [
                'success' => false,
                'error'   => 'Your handoff session expired. Please sign in again.',
            ];
        }

        $session->remove('login_handoff');
        $session->remove('login_handoff_existing');

        $user      = $payload['user'] ?? null;
        $authTable = $payload['auth_table'] ?? 'app_users';
        $method    = $payload['method'] ?? 'password';
        $username  = $payload['username'] ?? ($user['username'] ?? null);
        $remember  = (bool) ($payload['remember'] ?? false);

        if (! is_array($user)) {
            return [
                'success' => false,
                'error'   => 'Unable to continue the session handoff. Please sign in again.',
            ];
        }

        $flash = [
            'type'    => 'success',
            'message' => 'Signed in on this device. The previous session was signed out.',
        ];

        $result = $this->completeLogin($user, $authTable, $method, $username, $flash, $remember);

        $this->logHandoff((int) ($user['id'] ?? 0), $method, $existing['session_id'] ?? null);

        return $result;
    }

    private function logHandoff(int $userId, string $method, ?string $previousSessionId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $model = new \App\Models\SessionHandoffLogModel();
            $request = service('request');

            $model->insert([
                'user_id'             => $userId,
                'method'              => $method,
                'ip_address'          => $request->getIPAddress(),
                'user_agent'          => (string) $request->getUserAgent(),
                'previous_session_id' => $previousSessionId,
                'created_at'          => utc_now(),
            ]);

            log_message('info', '[Auth][SessionHandoff] User {user} confirmed new session (method={method})', [
                'user'   => $userId,
                'method' => $method,
            ]);
        } catch (\Throwable $exception) {
            log_message('error', '[Auth][SessionHandoff] Unable to log handoff for user {user}: {message}', [
                'user'    => $userId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function failure(string $message): array
    {
        log_message('debug', '[Auth][LoginService] Login attempt failed: {message}', ['message' => $message]);
        return [
            'success' => false,
            'error'   => $message,
        ];
    }

    private function completeLogin(
        array $user,
        string $authTable,
        string $method,
        ?string $username = null,
        ?array $flashOverride = null,
        bool $remember = false
    ): array
    {
        $payload = $this->sessionManager->finalizeLogin($user, $authTable, $method, $flashOverride);

        if ($remember) {
            $this->rememberService->rememberDevice($user, $authTable);
        } else {
            $this->rememberService->forgetCurrentDevice();
        }

        if ($username !== null) {
            log_message('info', '[Auth][LoginService] Successful login for user {username}', [
                'username'  => $username,
                'authTable' => $authTable,
            ]);
        }

        return [
            'success'  => true,
            'redirect' => $payload['redirect'],
            'flash'    => $payload['flash'],
        ];
    }

    private function maybeDeferLogin(array &$user, string $authTable, string $method, ?string $username = null, bool $remember = false): ?array
    {
        if ($authTable !== 'app_users') {
            return null;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $existing = $this->sessionRegistry->getActiveSession($userId, $authTable);
        if ($existing === null) {
            return null;
        }

        $this->storeHandoffPayload($user, $authTable, $method, $username, $existing, $remember);

        log_message('info', '[Auth][LoginService] Login handoff required for user_id={id}', ['id' => $userId]);

        return [
            'success'  => false,
            'handoff'  => true,
            'redirect' => site_url('login/handoff'),
        ];
    }

    private function storeHandoffPayload(array $user, string $authTable, string $method, ?string $username, array $existing, bool $remember): void
    {
        $session = session();
        $session->set('login_handoff', [
            'user'       => $this->sanitizeUserForHandoff($user),
            'auth_table' => $authTable,
            'method'     => $method,
            'username'   => $username ?? ($user['username'] ?? null),
            'remember'   => $remember,
        ]);
        $session->set('login_handoff_existing', $existing);
    }

    private function sanitizeUserForHandoff(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
