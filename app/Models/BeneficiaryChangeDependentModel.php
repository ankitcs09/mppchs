<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryChangeDependentModel extends Model
{
    protected $table          = 'beneficiary_change_dependents';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    protected $allowedFields = [
        'change_request_id',
        'dependent_id',
        'action',
        'order_index',
        'relationship_key',
        'alive_status',
        'health_status',
        'full_name',
        'payload_before',
        'payload_after',
    ];

    protected $validationRules = [
        'change_request_id' => 'required|is_natural_no_zero',
        'action'            => 'required|in_list[add,update,remove]',
    ];
}
