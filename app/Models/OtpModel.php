<?php

namespace App\Models;

use CodeIgniter\Model;

class OtpModel extends Model
{
    protected $table         = 'otp_verifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'user_id',
        'mobile',
        'otp_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
        'resend_count',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    /**
     * Removes all expired OTP rows to keep the table tidy.
     */
    public function purgeExpired(): void
    {
        $this->where('expires_at <', utc_now())->delete();
    }
}
