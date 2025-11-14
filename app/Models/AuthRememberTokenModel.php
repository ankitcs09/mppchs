<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthRememberTokenModel extends Model
{
    protected $table            = 'auth_remember_tokens';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'user_id',
        'auth_table',
        'selector',
        'token_hash',
        'user_agent',
        'ip_address',
        'created_at',
        'expires_at',
    ];
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    public function deleteBySelector(string $selector): bool
    {
        return (bool) $this->where('selector', $selector)->delete();
    }

    public function deleteUserTokens(int $userId, string $authTable): bool
    {
        return (bool) $this->where([
            'user_id'    => $userId,
            'auth_table' => $authTable,
        ])->delete();
    }

    public function pruneExpired(): int
    {
        return $this->where('expires_at <', utc_now())->delete();
    }
}
