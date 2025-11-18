<?php

namespace App\Services\Auth;

use App\Models\UserSessionModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SessionRegistry
{
    private const OFFSET_APP_USERS = 0;
    private const OFFSET_TMUSERS   = 1_000_000;
    private const DEFAULT_OFFSET   = 2_000_000;

    private UserSessionModel $model;
    private BaseConnection $db;

    public function __construct(?UserSessionModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new UserSessionModel();
        $this->db    = $db ?? Database::connect();

        log_message('debug', '[SessionRegistry] Connected to database {db}', [
            'db' => $this->db->getDatabase(),
        ]);
    }

    public function getActiveSession(int $userId, string $authTable = 'app_users'): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $scopedId = $this->scopedUserId($userId, $authTable);

        return $this->model->find($scopedId) ?: null;
    }

    public function recordActiveSession(int $userId, string $sessionId, string $authTable = 'app_users'): void
    {
        if ($userId <= 0 || $sessionId === '') {
            log_message('debug', '[SessionRegistry] Skipping record for user_id={id} (sessionId empty or invalid)', ['id' => $userId]);
            return;
        }

        $scopedId = $this->scopedUserId($userId, $authTable);
        $existing = $this->model->find($scopedId);
        if ($existing && $existing['session_id'] !== $sessionId) {
            $this->removeSessionData($existing['session_id']);
            log_message('debug', '[SessionRegistry] Replacing previous session for user_id={id} scope={scope}', [
                'id'    => $userId,
                'scope' => $scopedId,
            ]);
        }

        $payload = [
            'session_id'   => $sessionId,
            'last_seen_at' => utc_now(),
        ];

        if ($existing) {
            $this->model->update($scopedId, $payload);
        } else {
            $payload['user_id'] = $scopedId;
            $this->model->insert($payload);
        }

        log_message('debug', '[SessionRegistry] Recorded session for user_id={id} scope={scope} session={session}', [
            'id'      => $userId,
            'scope'   => $scopedId,
            'session' => $sessionId,
        ]);
    }

    public function clearUser(int $userId, string $authTable = 'app_users'): void
    {
        if ($userId <= 0) {
            return;
        }

        $scopedId = $this->scopedUserId($userId, $authTable);
        $existing = $this->model->find($scopedId);
        if ($existing) {
            $this->removeSessionData($existing['session_id'] ?? '');
            $this->model->delete($scopedId);
        }
    }

    private function scopedUserId(int $userId, string $authTable): int
    {
        $authTable = strtolower($authTable);

        $offset = match ($authTable) {
            'app_users' => self::OFFSET_APP_USERS,
            'tmusers'   => self::OFFSET_TMUSERS,
            default     => self::DEFAULT_OFFSET + (abs((int) crc32($authTable)) % 1_000_000),
        };

        return $offset + $userId;
    }

    private function removeSessionData(?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        $this->db->table('ci_sessions')
            ->where('id', $sessionId)
            ->delete();
    }

    public function sessionExists(string $sessionId): bool
    {
        if ($sessionId === '') {
            return false;
        }

        return (bool) $this->db->table('ci_sessions')
            ->where('id', $sessionId)
            ->countAllResults();
    }
}
