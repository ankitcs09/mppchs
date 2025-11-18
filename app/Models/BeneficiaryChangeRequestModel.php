<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryChangeRequestModel extends Model
{
    protected $table            = 'beneficiary_change_requests';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'beneficiary_v2_id',
        'user_id',
        'reference_number',
        'legacy_reference',
        'submission_no',
        'revision_no',
        'status',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
        'review_comment',
        'payload_before',
        'payload_after',
        'summary_diff',
        'undertaking_confirmed',
        'ip_address',
        'user_agent',
    ];

    protected $validationRules = [
        'beneficiary_v2_id' => 'required|is_natural_no_zero',
        'user_id'           => 'required|is_natural_no_zero',
        'status'            => 'required|in_list[pending,approved,rejected,needs_info,draft]',
    ];

    protected $validationMessages = [];
}
