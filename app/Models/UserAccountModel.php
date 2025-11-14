<?php
namespace App\Models;

use CodeIgniter\Model;

class UserAccountModel extends Model
{
    protected $table      = 'tmusers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'beneficiary_id',
        'beneficiary_v2_id',
        'username',
        'bname',
        'password',
        'force_password_reset',
        'password_changed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first() ?: null;
    }
}
