<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryPolicyCardModel extends Model
{
    protected $table            = 'beneficiary_policy_cards';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'company_id',
        'beneficiary_id',
        'policy_number',
        'card_number',
        'policy_program',
        'policy_provider',
        'tpa_name',
        'tpa_reference',
        'effective_from',
        'effective_to',
        'status',
        'metadata',
        'created_by',
        'updated_by',
    ];
}

