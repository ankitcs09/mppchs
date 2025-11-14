<?php

namespace App\Models;

use CodeIgniter\Model;

class DependentResidenceModel extends Model
{
    protected $table      = 'dependent_residences';
    protected $primaryKey = 'id';

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'dependent_id',
        'address_line1',
        'address_line2',
        'postal_code',
        'city_id',
        'residence_type',
        'valid_from',
        'valid_to',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getCurrentResidence(int $dependentId): ?array
    {
        return $this->where('dependent_id', $dependentId)
            ->where('residence_type', 'CURRENT')
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}

