<?php

namespace App\Models;

use CodeIgniter\Model;

class HelpdeskEditRequestModel extends Model
{
    protected $table      = 'helpdesk_edit_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'beneficiary_id',
        'helpdesk_user_id',
        'company_id',
        'notes',
        'attachments',
        'status',
        'admin_user_id',
        'resolution_notes',
        'resolved_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
