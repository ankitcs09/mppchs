<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryChangeAuditModel extends Model
{
    protected $table          = 'beneficiary_change_audit';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'change_request_id',
        'action',
        'actor_id',
        'notes',
        'created_at',
    ];

    protected $validationRules = [
        'change_request_id' => 'required|is_natural_no_zero',
        'action'            => 'required',
    ];
}
