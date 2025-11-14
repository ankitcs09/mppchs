<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryChangeItemModel extends Model
{
    protected $table            = 'beneficiary_change_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'change_request_id',
        'entity_type',
        'entity_identifier',
        'field_key',
        'field_label',
        'old_value',
        'new_value',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $validationRules = [
        'change_request_id' => 'required|is_natural_no_zero',
        'field_key'         => 'required|string',
        'status'            => 'in_list[pending,approved,rejected,needs_info]',
    ];

    protected $validationMessages = [];
}
