<?php

namespace App\Models;

use CodeIgniter\Model;

class HospitalRequestModel extends Model
{
    protected $table      = 'hospital_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'reference_number',
        'requester_user_id',
        'requester_user_table',
        'requester_unique_ref',
        'state_id',
        'state_name',
        'city_id',
        'city_name',
        'hospital_name',
        'address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'notes',
        'status',
    ];

    protected $useTimestamps = true;

    public function generateReferenceNumber(): string
    {
        do {
            $reference = 'HR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->where('reference_number', $reference)->countAllResults(true) > 0);

        return $reference;
    }
}

