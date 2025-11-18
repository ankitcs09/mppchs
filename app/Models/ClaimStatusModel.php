<?php

namespace App\Models;

use CodeIgniter\Model;

class ClaimStatusModel extends Model
{
    protected $table          = 'claim_statuses';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    protected $allowedFields = [
        'code',
        'label',
        'description',
        'is_terminal',
        'display_order',
    ];
}

