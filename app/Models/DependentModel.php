<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model representing dependents associated with a beneficiary.
 *
 * Each record in the `dependents` table links back to a beneficiary via
 * `beneficiary_id`. This allows storing an arbitrary number of children
 * or parents. Using a separate model makes it easy to query all
 * dependents for a given form and to insert or update dependent
 * information independently of the main form.
 */
class DependentModel extends Model
{
    protected $table      = 'beneficiary_dependents';
    protected $primaryKey = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'beneficiary_id',
        'relation',
        'relation_group',
        'source_relation',
        'sequence',
        'status',
        'alive',
        'is_dependent_for_health',
        'name',
        'gender',
        'blood_group',
        'date_of_birth',
        'aadhar_number',
        'notes',
        'data_source',
        'load_batch_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
