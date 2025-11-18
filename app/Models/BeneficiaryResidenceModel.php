<?php

namespace App\Models;

use CodeIgniter\Model;

class BeneficiaryResidenceModel extends Model
{
    protected $table      = 'beneficiary_residences';
    protected $primaryKey = 'id';

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'beneficiary_id',
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

    public function getCurrentResidence(int $beneficiaryId): ?array
    {
        return $this->where('beneficiary_id', $beneficiaryId)
            ->where('residence_type', 'CURRENT')
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}

