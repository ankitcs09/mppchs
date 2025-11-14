<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSessionModel extends Model
{
    protected $table      = 'user_sessions';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps   = false;
    protected $allowedFields   = [
        'user_id',
        'session_id',
        'last_seen_at',
    ];
}

