<?php

namespace App\Models;

use CodeIgniter\Model;

class AppUserModel extends Model
{
    protected $table      = 'app_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'username',
        'display_name',
        'bname',
        'email',
        'mobile',
        'password',
        'user_type',
        'company_id',
        'beneficiary_v2_id',
        'status',
        'force_password_reset',
        'password_changed_at',
        'last_login_at',
        'session_version',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUsername(string $username): ?array
    {
        $row = $this->where('username', $username)->first();
        if (! $row) {
            return null;
        }

        if (! isset($row['bname']) || $row['bname'] === null) {
            $row['bname'] = $row['display_name'] ?? $row['username'] ?? null;
        }

        if (! isset($row['display_name']) || $row['display_name'] === null) {
            $row['display_name'] = $row['bname'] ?? $row['username'] ?? null;
        }

        return $row;
    }
}




