<?php

namespace App\Models;

use CodeIgniter\Model;

class ClaimEventModel extends Model
{
    protected $table            = 'claim_events';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'claim_id',
        'status_id',
        'event_code',
        'event_label',
        'description',
        'event_time',
        'source',
        'payload',
    ];
}

