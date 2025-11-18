<?php

namespace App\Models;

use CodeIgniter\Model;

class ClaimDocumentModel extends Model
{
    protected $table            = 'claim_documents';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'claim_id',
        'document_type_id',
        'title',
        'storage_disk',
        'storage_path',
        'checksum',
        'mime_type',
        'file_size',
        'source',
        'uploaded_by',
        'uploaded_at',
        'metadata',
    ];
}

