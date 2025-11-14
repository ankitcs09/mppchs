<?php

declare(strict_types=1);

if (! function_exists('format_display_value')) {
    function format_display_value($value, string $default = 'Not Provided'): string
    {
        if ($value === null) {
            return $default;
        }

        $string = trim((string) $value);

        return $string === '' ? $default : $string;
    }
}

if (! function_exists('format_date_for_display')) {
    function format_date_for_display($value, bool $withTime = false): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        try {
            $dateTime = new DateTime($value);
            return $withTime ? $dateTime->format('d-M-Y H:i') : $dateTime->format('d-M-Y');
        } catch (\Throwable $e) {
            return $value;
        }
    }
}

if (! function_exists('is_nps_pensioner')) {
    function is_nps_pensioner(array $beneficiary): bool
    {
        $category = strtolower(trim((string) ($beneficiary['category'] ?? '')));

        return $category !== '' && (str_contains($category, 'nps') || str_contains($category, 'new pension'));
    }
}

if (! function_exists('format_beneficiary_details')) {
    function format_beneficiary_details(
        array $beneficiary,
        ?array $residence = null,
        ?array $city = null,
        ?array $state = null
    ): array {
        $addressLine1 = $residence['address_line1'] ?? $beneficiary['address_line1'] ?? null;
        $addressLine2 = $residence['address_line2'] ?? $beneficiary['address_line2'] ?? null;
        $postalCode   = $residence['postal_code'] ?? $beneficiary['postal_code'] ?? null;
        $cityName     = $city['city_name'] ?? $beneficiary['city'] ?? null;
        $stateName    = $state['state_name'] ?? $beneficiary['state'] ?? null;

        $map = [
            'Unique Reference'        => $beneficiary['unique_ref_number'] ?? null,
            'Category'                => $beneficiary['category'] ?? null,
            'Scheme Option'           => $beneficiary['scheme_option'] ?? null,
            'First Name'              => $beneficiary['first_name'] ?? null,
            'Middle Name'             => $beneficiary['middle_name'] ?? null,
            'Last Name'               => $beneficiary['last_name'] ?? null,
            'Gender'                  => $beneficiary['gender'] ?? null,
            'Date of Birth'           => $beneficiary['date_of_birth'] ?? null,
            'Blood Group'             => $beneficiary['blood_group'] ?? null,
            'Samagra ID'              => $beneficiary['samagra_id'] ?? null,
            'Retirement Date'         => $beneficiary['retirement_date'] ?? null,
            'RAO'                     => $beneficiary['rao'] ?? null,
            'Office @ Retirement'     => $beneficiary['office_at_retirement'] ?? null,
            'Designation'             => $beneficiary['designation'] ?? null,
            'Address Line 1'          => $addressLine1,
            'Address Line 2'          => $addressLine2,
            'City'                    => $cityName,
            'State'                   => $stateName,
            'Postal Code'             => $postalCode,
            'Mobile'                  => $beneficiary['mobile_number'] ?? null,
            'Alternate Mobile'        => $beneficiary['alternate_mobile'] ?? null,
            'Email'                   => $beneficiary['email'] ?? null,
            'PPO Number'              => $beneficiary['ppo_number'] ?? null,
            'GPF Number'              => $beneficiary['gpf_number'] ?? null,
            'PRAN Number'             => $beneficiary['pran_number'] ?? null,
            'Bank Name'               => $beneficiary['bank_name'] ?? null,
            'Bank Account'            => $beneficiary['bank_account_number'] ?? null,
            'Aadhaar'                 => $beneficiary['aadhar_number'] ?? null,
            'PAN'                     => $beneficiary['pan_number'] ?? null,
            'Terms Accepted At'       => $beneficiary['terms_accepted_at'] ?? null,
            'Terms Version'           => $beneficiary['terms_version'] ?? null,
        ];

        $rows = [];
        $dateLabels     = ['Date of Birth', 'Retirement Date'];
        $dateTimeLabels = ['Terms Accepted At'];

        foreach ($map as $label => $value) {
            if ($label === 'PRAN Number' && ! is_nps_pensioner($beneficiary)) {
                $formatted = 'Not Applicable';
            } else {
                if (in_array($label, $dateLabels, true)) {
                    $value = format_date_for_display($value);
                }

                if (in_array($label, $dateTimeLabels, true)) {
                    $value = format_date_for_display($value, true);
                }

                $formatted = format_display_value($value);
            }

            $rows[] = [
                'label' => $label,
                'value' => $formatted,
            ];
        }

        return $rows;
    }
}

if (! function_exists('normalize_dependent_row')) {
    function normalize_dependent_row(array $dependent, int $order): array
    {
        $row = [
            'order'                  => $order,
            'relation'               => format_display_value($dependent['relation'] ?? null),
            'status'                 => format_display_value($dependent['status'] ?? null),
            'is_dependent_for_health'=> ((int) ($dependent['is_dependent_for_health'] ?? 0) === 1),
            'name'                   => format_display_value($dependent['name'] ?? null),
            'gender'                 => format_display_value($dependent['gender'] ?? null),
            'blood_group'            => format_display_value($dependent['blood_group'] ?? null),
            'date_of_birth'          => format_display_value(
                format_date_for_display($dependent['date_of_birth'] ?? null)
            ),
            'aadhar_number'          => format_display_value($dependent['aadhar_number'] ?? null),
            'notes'                  => format_display_value($dependent['notes'] ?? null, ''),
        ];

        return $row;
    }
}

if (! function_exists('prepare_dependents_sections')) {
    function prepare_dependents_sections(array $dependents): array
    {
        $order            = 1;
        $covered          = [];
        $notDependent     = [];

        foreach ($dependents as $dependent) {
            $row = normalize_dependent_row($dependent, $order++);

            if ($row['is_dependent_for_health']) {
                $covered[] = $row;
                continue;
            }

            // Treat NOT APPLICABLE / NOT ALIVE as not dependent for display purposes.
            $status = strtolower(trim((string) ($dependent['status'] ?? '')));
            if ($status === 'alive' && $row['is_dependent_for_health']) {
                $covered[] = $row;
            } else {
                $notDependent[] = $row;
            }
        }

        return [
            'covered'       => $covered,
            'not_dependent' => $notDependent,
            'all'           => array_merge($covered, $notDependent),
        ];
    }
}

if (! function_exists('count_dependents_covered')) {
    function count_dependents_covered(array $covered): int
    {
        return count($covered);
    }
}

if (! function_exists('collect_missing_detail_messages')) {
    function collect_missing_detail_messages(
        array $beneficiary,
        ?array $residence = null,
        ?string $city = null,
        ?string $state = null
    ): array {
        $messages = [];

        $required = [
            'scheme_option'        => $beneficiary['scheme_option'] ?? null,
            'mobile_number'        => $beneficiary['mobile_number'] ?? null,
            'email'                => $beneficiary['email'] ?? null,
            'bank_name'            => $beneficiary['bank_name'] ?? null,
            'bank_account_number'  => $beneficiary['bank_account_number'] ?? null,
            'address_line1'        => $residence['address_line1'] ?? null,
            'city'                 => $city,
            'state'                => $state,
        ];

        foreach ($required as $label => $value) {
            if (trim((string) ($value ?? '')) === '') {
                switch ($label) {
                    case 'scheme_option':
                        $messages[] = 'Scheme option not selected.';
                        break;
                    case 'mobile_number':
                        $messages[] = 'Primary mobile number not provided.';
                        break;
                    case 'email':
                        $messages[] = 'Email address not provided.';
                        break;
                    case 'bank_name':
                        $messages[] = 'Bank name not provided.';
                        break;
                    case 'bank_account_number':
                        $messages[] = 'Bank account number not provided.';
                        break;
                    case 'address_line1':
                        $messages[] = 'Address line 1 not provided.';
                        break;
                    case 'city':
                        $messages[] = 'City not provided.';
                        break;
                    case 'state':
                        $messages[] = 'State not provided.';
                        break;
                }
            }
        }

        $optional = [
            'blood_group'      => 'Beneficiary blood group not provided.',
            'alternate_mobile' => 'Alternate mobile number not provided.',
            'samagra_id'       => 'Samagra ID not provided.',
            'gpf_number'       => 'GPF number not provided.',
            'pan_number'       => 'PAN number not provided.',
        ];

        foreach ($optional as $field => $message) {
            if (trim((string) ($beneficiary[$field] ?? '')) === '') {
                $messages[] = $message;
            }
        }

        if (is_nps_pensioner($beneficiary) && trim((string) ($beneficiary['pran_number'] ?? '')) === '') {
            $messages[] = 'PRAN number is required for NPS pensioners.';
        }

        return array_values(array_unique($messages));
    }
}
