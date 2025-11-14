<?php

namespace App\Models;

use CodeIgniter\Model;

class ClaimModel extends Model
{
    protected $table            = 'claims';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'company_id',
        'beneficiary_id',
        'dependent_id',
        'policy_card_id',
        'claim_reference',
        'external_reference',
        'claim_type_id',
        'status_id',
        'claim_category',
        'claim_sub_status',
        'claim_date',
        'admission_date',
        'discharge_date',
        'claimed_amount',
        'approved_amount',
        'cashless_amount',
        'copay_amount',
        'non_payable_amount',
        'reimbursed_amount',
        'hospital_name',
        'hospital_code',
        'hospital_city',
        'hospital_state',
        'diagnosis',
        'remarks',
        'source',
        'source_reference',
        'received_at',
        'last_synced_at',
        'payload',
    ];
}

