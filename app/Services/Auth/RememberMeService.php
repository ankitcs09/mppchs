<?php

namespace App\Services\Auth;

use App\Models\AppUserModel;
use App\Models\AuthRememberTokenModel;
use App\Models\BeneficiaryV2Model;
use App\Models\UserAccountModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;
use Config\RememberMe as RememberMeConfig;
use Config\Services;
use Throwable;

class RememberMeService
{
    private RememberMeConfig $config;
    private AuthRememberTokenModel $tokens;
    private SessionManager $sessionManager;
    private AppUserModel $appUsers;
    private UserAccountModel $legacyUsers;
    private BeneficiaryV2Model $beneficiaries;
    private IncomingRequest $request;

    public function __construct(
        ?RememberMeConfig $config = null,
        ?AuthRememberTokenModel $tokens = null,
        ?SessionManager $sessionManager = null,
        ?AppUserModel $appUsers = null,
        ?UserAccountModel $legacyUsers = null,
        ?BeneficiaryV2Model $beneficiaries = null
    ) {
        $this->config        = $config ?? config('RememberMe');
        $this->tokens        = $tokens ?? new AuthRememberTokenModel();
        $this->sessionManager = $sessionManager ?? new SessionManager();
        $this->appUsers      = $appUsers ?? new AppUserModel();
        $this->legacyUsers   = $legacyUsers ?? new UserAccountModel();
        $this->beneficiaries = $beneficiaries ?? new BeneficiaryV2Model();
        $this->request       = Services::request();
    }

    /**
     * Issues a persistent token for the supplied user/device.
     */
    public function rememberDevice(array $user, string $authTable): void
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $this->pruneExpiredTokens();
        $this->forgetCurrentDevice();

        try {
            $selectorBytes  = max(6, (int) $this->config->selectorBytes);
            $validatorBytes = max(16, (int) $this->config->validatorBytes);

            $selector  = bin2hex(random_bytes($selectorBytes));
            $validator = base64_encode(random_bytes($validatorBytes));
        } catch (Throwable $exception) {
            log_message('error', '[RememberMe] Unable to generate token: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return;
        }

        $now       = utc_now();
        $expiresAt = Time::now('UTC')
            ->addDays(max(1, $this->config->lifetimeDays))
            ->toDateTimeString();
        $hash      = $this->hashValidator($validator);
        $userAgent = mb_substr((string) $this->request->getUserAgent(), 0, 255);
        $ip        = mb_substr((string) $this->request->getIPAddress(), 0, 64);

        $this->tokens->insert([
            'user_id'    => $userId,
            'auth_table' => $authTable,
            'selector'   => $selector,
            'token_hash' => $hash,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
            'created_at' => $now,
            'expires_at' => $expiresAt,
        ]);

        $this->enforceDeviceLimit($userId, $authTable);
        $this->setCookie($selector, $validator, $expiresAt);

        log_message('info', '[RememberMe] Token issued for user_id={id} auth_table={table}', [
            'id'    => $userId,
            'table' => $authTable,
        ]);
    }

    /**
     * Attempts to restore a session using the remember-me cookie.
     */
    public function attemptAutoLogin(): bool
    {
        $parts = $this->getCookieParts();
        if ($parts === null) {
            return false;
        }

        $record = $this->tokens->where('selector', $parts['selector'])->first();
        if (! $record) {
            $this->forgetCurrentDevice();
            return false;
        }

        if ($this->isExpired($record['expires_at'] ?? null)) {
            $this->tokens->delete($record['id']);
            $this->forgetCurrentDevice();
            return false;
        }

        $incomingHash = $this->hashValidator($parts['validator']);
        if (! hash_equals((string) $record['token_hash'], $incomingHash)) {
            $this->tokens->delete($record['id']);
            $this->forgetCurrentDevice();
            return false;
        }

        if (! $this->userAgentMatches((string) ($record['user_agent'] ?? ''))) {
            $this->tokens->delete($record['id']);
            $this->forgetCurrentDevice();
            return false;
        }

        $authTable = (string) ($record['auth_table'] ?? 'app_users');
        $user      = $this->loadUser((int) $record['user_id'], $authTable);
        if ($user === null) {
            $this->tokens->delete($record['id']);
            $this->forgetCurrentDevice();
            return false;
        }

        if ($authTable === 'app_users') {
            $status = strtolower((string) ($user['status'] ?? 'active'));
            if (in_array($status, ['locked', 'disabled'], true)) {
                $this->tokens->delete($record['id']);
                $this->forgetCurrentDevice();
                return false;
            }

            $this->appUsers->update($user['id'], ['last_login_at' => utc_now()]);
        }

        $this->sessionManager->finalizeLogin($user, $authTable, 'remember');
        $this->rotateValidator((int) $record['id'], (string) $record['selector'], $authTable, (int) $record['user_id']);

        log_message('info', '[RememberMe] Auto-login succeeded for user_id={id}', [
            'id' => $record['user_id'],
        ]);

        return true;
    }

    /**
     * Removes the cookie/token pair for the current browser.
     */
    public function forgetCurrentDevice(bool $expireCookie = true): void
    {
        $parts = $this->getCookieParts();
        if ($parts !== null) {
            $this->tokens->deleteBySelector($parts['selector']);
        }

        if ($expireCookie) {
            $this->expireCookie();
        }
    }

    /**
     * Deletes every stored token for the user (e.g. password change).
     */
    public function forgetAllForUser(int $userId, string $authTable): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->tokens->deleteUserTokens($userId, $authTable);
        $this->forgetCurrentDevice();
    }

    private function rotateValidator(int $rowId, string $selector, string $authTable, int $userId): void
    {
        try {
            $validator = base64_encode(random_bytes(max(16, (int) $this->config->validatorBytes)));
        } catch (Throwable $exception) {
            log_message('error', '[RememberMe] Unable to rotate validator: {message}', [
                'message' => $exception->getMessage(),
            ]);
            $this->forgetCurrentDevice();
            return;
        }

        $hash      = $this->hashValidator($validator);
        $expiresAt = Time::now('UTC')
            ->addDays(max(1, $this->config->lifetimeDays))
            ->toDateTimeString();
        $userAgent = mb_substr((string) $this->request->getUserAgent(), 0, 255);
        $ip        = mb_substr((string) $this->request->getIPAddress(), 0, 64);

        $this->tokens->update($rowId, [
            'token_hash' => $hash,
            'user_agent' => $userAgent,
            'ip_address' => $ip,
            'created_at' => utc_now(),
            'expires_at' => $expiresAt,
        ]);

        $this->setCookie($selector, $validator, $expiresAt);
        $this->enforceDeviceLimit($userId, $authTable);
    }

    private function setCookie(string $selector, string $validator, string $expiresAt): void
    {
        $value     = base64_encode($selector . ':' . $validator);
        $expiresTs = Time::parse($expiresAt, 'UTC')->getTimestamp();
        $secure    = $this->config->secureOnly ? true : $this->request->isSecure();

        setcookie(
            $this->config->cookieName,
            $value,
            [
                'expires'  => $expiresTs,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => $this->config->httpOnly,
                'samesite' => 'Lax',
            ]
        );

        log_message('debug', '[RememberMe] Set cookie selector={selector} expires={expires}', [
            'selector' => $selector,
            'expires'  => $expiresAt,
        ]);
    }

    private function expireCookie(): void
    {
        setcookie(
            $this->config->cookieName,
            '',
            [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $this->config->secureOnly ? true : $this->request->isSecure(),
                'httponly' => $this->config->httpOnly,
                'samesite' => 'Lax',
            ]
        );
    }

    private function enforceDeviceLimit(int $userId, string $authTable): void
    {
        $max = max(1, (int) $this->config->maxDevicesPerUser);
        $builder = $this->tokens->builder();
        $builder->where([
            'user_id'    => $userId,
            'auth_table' => $authTable,
        ]);

        $total = (int) $builder->countAllResults(false);
        if ($total <= $max) {
            $builder->resetQuery();
            return;
        }

        $builder->resetQuery();

        $excess = $total - $max;
        $staleBuilder = $this->tokens->builder();
        $staleRows    = $staleBuilder
            ->where([
                'user_id'    => $userId,
                'auth_table' => $authTable,
            ])
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get($excess)
            ->getResultArray();

        $staleBuilder->resetQuery();

        foreach ($staleRows as $row) {
            $this->tokens->delete((int) $row['id']);
        }
    }

    private function getCookieParts(): ?array
    {
        $raw = $this->request->getCookie($this->config->cookieName);
        if (! $raw) {
            return null;
        }

        $decoded = base64_decode($raw, true);
        $payload = $decoded !== false ? $decoded : $raw;
        $parts   = explode(':', $payload, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $selector  = trim($parts[0]);
        $validator = trim($parts[1]);

        if ($selector === '' || $validator === '') {
            return null;
        }

        return [
            'selector'  => $selector,
            'validator' => $validator,
        ];
    }

    private function hashValidator(string $validator): string
    {
        return hash('sha256', $validator);
    }

    private function isExpired(?string $expiresAt): bool
    {
        if (! $expiresAt) {
            return true;
        }

        try {
            $expiry = Time::parse($expiresAt, 'UTC')->getTimestamp();
        } catch (Throwable $exception) {
            return true;
        }

        return $expiry < time();
    }

    private function userAgentMatches(string $stored): bool
    {
        if ($stored === '') {
            return true;
        }

        $current = (string) $this->request->getUserAgent();

        return hash_equals($stored, mb_substr($current, 0, 255));
    }

    private function loadUser(int $userId, string $authTable): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        if ($authTable === 'tmusers') {
            return $this->legacyUsers->find($userId) ?: null;
        }

        $user = $this->appUsers->find($userId);
        if ($user && ! empty($user['beneficiary_v2_id'])) {
            $beneficiary = $this->beneficiaries->find((int) $user['beneficiary_v2_id']);
            $user['legacy_beneficiary_id'] = $beneficiary['legacy_beneficiary_id'] ?? 0;
        }

        return $user ?: null;
    }

    private function pruneExpiredTokens(): void
    {
        try {
            $this->tokens->pruneExpired();
        } catch (Throwable $exception) {
            log_message('error', '[RememberMe] Failed pruning tokens: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
