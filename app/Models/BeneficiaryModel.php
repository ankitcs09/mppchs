<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model representing beneficiaries (cashless form owners).
 *
 * Each row in the `beneficiaries` table corresponds to a single form
 * submission. The model lists all fields that can be mass‑assigned via
 * `$allowedFields` so that controllers can safely save data to the
 * database. For details about how models manage data and business rules
 * before passing it to views, see the CodeIgniter documentation on MVC
 * where models maintain and enforce business rules on the data【176354241900552†L248-L259】.
 */
class BeneficiaryModel extends Model
{
    protected $table      = 'beneficiaries';
    protected $primaryKey = 'id';

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    /**
     * List of fields that can be set by the user. Using this list
     * prevents mass‑assignment vulnerabilities.
     */
    protected $allowedFields = [
        'unique_ref_number',
        'category',
        'scheme_option',
        'is_family_pensioner',
        'deceased_employee_name',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'blood_group',
        'samagra_id',
        'retirement_date',
        'rao',
        'raw_rao',
        'office_at_retirement',
        'raw_office_at_retirement',
        'designation',
        'raw_designation',
        'mobile_number',
        'alternate_mobile',
        'email',
        'ppo_number',
        'gpf_number',
        'pran_number',
        'bank_name',
        'bank_account_number',
        'aadhar_number',
        'pan_number',
        'terms_accepted_at',
        'terms_version',
        'data_source',
        'load_batch_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
