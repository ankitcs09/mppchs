<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryDependentV2Model extends Model
{
    protected $table      = 'beneficiary_dependents_v2';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'beneficiary_id',
        'relationship',
        'dependant_order',
        'twin_group',
        'is_alive',
        'is_health_dependant',
        'first_name',
        'gender',
        'blood_group_id',
        'date_of_birth',
        'aadhaar_enc',
        'aadhaar_masked',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'is_active',
        'deleted_at',
        'deleted_by',
        'restored_at',
        'restored_by',
    ];
}
