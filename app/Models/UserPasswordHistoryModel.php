<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class UserPasswordHistoryModel extends Model
{
    protected $table      = 'user_password_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'password_hash',
        'changed_at',
    ];

    protected $useTimestamps = false;

    /**
     * Return the most recent password hashes for a user (newest first).
     *
     * @return string[]
     */
    public function getRecentHashes(int $userId, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->where('user_id', $userId)
            ->orderBy('changed_at', 'DESC')
            ->limit($limit)
            ->findColumn('password_hash') ?? [];
    }

    /**
     * Store a new password hash and prune history beyond the configured depth.
     */
    public function recordPassword(int $userId, string $hash, int $retain): void
    {
        $this->insert([
            'user_id'       => $userId,
            'password_hash' => $hash,
            'changed_at'    => Time::now('UTC')->toDateTimeString(),
        ]);

        if ($retain <= 0) {
            return;
        }

        $ids = $this->where('user_id', $userId)
            ->orderBy('changed_at', 'DESC')
            ->findColumn('id') ?? [];

        if (count($ids) <= $retain) {
            return;
        }

        $idsToDelete = array_slice($ids, $retain);
        if (! empty($idsToDelete)) {
            $this->whereIn('id', $idsToDelete)->delete();
        }
    }
}

