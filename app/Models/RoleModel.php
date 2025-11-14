<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table      = 'roles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'slug',
        'name',
        'description',
        'is_global',
        'is_assignable',
        'priority',
        'default_redirect',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySlug(string $slug): ?array
    {
        $row = $this->where('slug', $slug)->first();
        return $row ?: null;
    }
}

