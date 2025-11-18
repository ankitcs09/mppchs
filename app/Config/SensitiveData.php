<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class SensitiveData extends BaseConfig
{
    /**
     * Which columns in beneficiaries_v2 need encryption.
     */
    public array $beneficiaryColumns = [
        'ppo_number'      => ['column' => 'ppo_number_enc', 'mask' => 'ppo_number_masked', 'masker' => 'alpha'],
        'pran_number'     => ['column' => 'pran_number_enc', 'mask' => 'pran_number_masked', 'masker' => 'alpha'],
        'gpf_number'      => ['column' => 'gpf_number_enc', 'mask' => 'gpf_number_masked', 'masker' => 'alpha'],
        'bank_account'    => ['column' => 'bank_account_enc', 'mask' => 'bank_account_masked', 'masker' => 'digits'],
        'aadhaar_number'  => ['column' => 'aadhaar_enc', 'mask' => 'aadhaar_masked', 'masker' => 'digits'],
        'pan_number'      => ['column' => 'pan_enc', 'mask' => 'pan_masked', 'masker' => 'alpha'],
        'primary_mobile'  => ['column' => 'primary_mobile_enc', 'mask' => 'primary_mobile_masked', 'masker' => 'digits'],
        'alternate_mobile'=> ['column' => 'alternate_mobile_enc', 'mask' => 'alternate_mobile_masked', 'masker' => 'digits'],
        'samagra'         => ['column' => 'samagra_enc', 'mask' => 'samagra_masked', 'masker' => 'digits'],
    ];

    /**
     * Fields in dependents needing encryption.
     */
    public array $dependentColumns = [
        'aadhaar_number' => ['column' => 'aadhaar_enc', 'mask' => 'aadhaar_masked', 'masker' => 'digits'],
    ];
}
