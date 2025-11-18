<?php

namespace App\Models;

use CodeIgniter\Model;

class ClaimDocumentTypeModel extends Model
{
    protected $table          = 'claim_document_types';
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
    ];
}

