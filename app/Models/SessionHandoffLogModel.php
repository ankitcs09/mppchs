<?php

namespace App\Models;

use CodeIgniter\Model;

class SessionHandoffLogModel extends Model
{
    protected $table      = 'session_handoff_log';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps   = false;
    protected $allowedFields   = [
        'user_id',
        'method',
        'ip_address',
        'user_agent',
        'previous_session_id',
        'created_at',
    ];
}

