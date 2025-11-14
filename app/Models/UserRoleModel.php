<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table      = 'user_roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'role_id',
        'company_id',
        'region_id',
        'assigned_by',
        'assigned_at',
        'revoked_at',
        'metadata',
    ];

    public $useTimestamps = false;

    public function assign(int $userId, int $roleId, ?int $companyId, ?int $assignedBy = null): void
    {
        $this->insert([
            'user_id'     => $userId,
            'role_id'     => $roleId,
            'company_id'  => $companyId,
            'region_id'   => null,
            'assigned_by' => $assignedBy,
            'assigned_at' => utc_now(),
            'metadata'    => null,
        ]);
    }
}
