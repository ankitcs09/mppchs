<?php

namespace App\Services;

use App\Models\AuthAttemptModel;
use App\Models\PasswordResetModel;
use CodeIgniter\I18n\Time;

class PasswordResetService
{
    private PasswordResetModel $resetModel;
    private AuthAttemptModel $attemptModel;

    public function __construct(
        ?PasswordResetModel $resetModel = null,
        ?AuthAttemptModel $attemptModel = null
    ) {
        $this->resetModel = $resetModel ?? new PasswordResetModel();
        $this->attemptModel = $attemptModel ?? new AuthAttemptModel();
    }

    public function hasExceededDailyLimit(int $userId, int $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        $startOfDay = Time::now('UTC')->setTime(0, 0, 0)->toDateTimeString();

        $count = $this->resetModel
            ->where('user_id', $userId)
            ->where('created_at >=', $startOfDay)
            ->countAllResults();

        return $count >= $limit;
    }

    /**
     * @param Time $expiresAt Expiration moment (UTC).
     *
     * @return array{ id:int, selector:string, token_plain:string }
     */
    public function createToken(int $userId, string $ip, string $userAgent, Time $expiresAt): array
    {
        $selector = bin2hex(random_bytes(8)); // 16 hex chars to match DB column
        $tokenPlain = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenPlain);

        $now = Time::now('UTC')->toDateTimeString();

        $id = $this->resetModel->insert([
            'user_id'              => $userId,
            'selector'             => $selector,
            'token_hash'           => $tokenHash,
            'expires_at'           => $expiresAt->toDateTimeString(),
            'attempts'             => 0,
            'requested_ip'         => $ip,
            'requested_user_agent' => mb_substr($userAgent, 0, 255),
            'created_at'           => $now,
        ], true);

        $this->attemptModel->insert([
            'username'   => null,
            'user_id'    => $userId,
            'method'     => 'reset',
            'status'     => 'requested',
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'metadata'   => json_encode(['selector' => $selector], JSON_THROW_ON_ERROR),
            'created_at' => $now,
        ]);

        return [
            'id'          => $id,
            'selector'    => $selector,
            'token_plain' => $tokenPlain,
        ];
    }

    public function recordFailure(?int $userId, ?string $username, string $ip, string $userAgent, array $meta = []): void
    {
        $payload = [
            'username'   => $username,
            'user_id'    => $userId,
            'method'     => 'reset',
            'status'     => 'failure',
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'metadata'   => $meta ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            'created_at' => Time::now('UTC')->toDateTimeString(),
        ];

        $this->attemptModel->insert($payload);
    }

    /**
     * Validate selector/token pair. Returns record with plain token for convenience.
     *
     * @return array|null
     */
    public function verifyToken(string $selector, string $token): ?array
    {
        $record = $this->resetModel
            ->where('selector', $selector)
            ->first();

        if (! $record) {
            log_message('debug', sprintf('[PasswordReset] verifyToken: selector %s not found', $selector));
            return null;
        }

        if ($record['used_at'] !== null) {
            log_message('debug', sprintf('[PasswordReset] verifyToken: selector %s already used', $selector));
            return null;
        }

        $now = Time::now('UTC');
        if (Time::parse($record['expires_at'], 'UTC')->isBefore($now)) {
            log_message('debug', sprintf('[PasswordReset] verifyToken: selector %s expired at %s (now %s)', $selector, $record['expires_at'], $now->toDateTimeString()));
            return null;
        }

        $tokenHash = hash('sha256', $token);
        if (! hash_equals($record['token_hash'], $tokenHash)) {
            $this->resetModel->update($record['id'], ['attempts' => ((int) $record['attempts']) + 1]);
            log_message('debug', sprintf('[PasswordReset] verifyToken: selector %s token mismatch', $selector));
            return null;
        }

        $record['token_plain'] = $token;
        return $record;
    }

    public function markTokenUsed(int $resetId): void
    {
        $this->resetModel->update($resetId, [
            'used_at'    => Time::now('UTC')->toDateTimeString(),
            'updated_at' => Time::now('UTC')->toDateTimeString(),
        ]);
    }

    public function recordSuccess(int $userId, string $ip, string $userAgent, array $meta = []): void
    {
        $this->attemptModel->insert([
            'username'   => null,
            'user_id'    => $userId,
            'method'     => 'reset',
            'status'     => 'success',
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'metadata'   => $meta ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            'created_at' => Time::now('UTC')->toDateTimeString(),
        ]);
    }

    /**
     * Placeholder for notification dispatch (SMS/Email). Currently logs for dev.
     */
    public function notifyUser(array $user, array $beneficiary, array $resetRecord): void
    {
        log_message(
            'info',
            'Password reset requested for user {username} (ID: {id}). Selector: {selector}',
            [
                'username' => $user['username'] ?? 'unknown',
                'id'       => $user['id'] ?? '0',
                'selector' => $resetRecord['selector'],
            ]
        );

        if (ENVIRONMENT === 'development') {
            session()->setFlashdata('debug_reset_link', site_url('password/reset/' . $resetRecord['selector'] . '/' . $resetRecord['token_plain']));
        }
    }
}
