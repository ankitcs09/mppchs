<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthAttemptModel extends Model
{
    protected $table      = 'auth_attempts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'username',
        'user_id',
        'method',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
