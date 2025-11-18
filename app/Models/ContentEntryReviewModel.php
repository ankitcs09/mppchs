<?php

namespace App\Models;

use CodeIgniter\Model;

class ContentEntryReviewModel extends Model
{
    protected $table            = 'content_entry_reviews';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = [
        'entry_id',
        'reviewer_id',
        'action',
        'note',
        'created_at',
    ];
}

