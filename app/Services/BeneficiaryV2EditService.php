<?php

namespace App\Services;

use App\Config\EnrollmentV2Masters;
use App\Models\BeneficiaryChangeRequestModel;
use App\Models\BeneficiaryDependentV2Model;
use App\Models\BeneficiaryV2Model;
use CodeIgniter\I18n\Time;
use CodeIgniter\Validation\ValidationInterface;
use RuntimeException;
use const JSON_THROW_ON_ERROR;

class BeneficiaryV2EditService
{
    private const OTHER_OPTION_VALUE = '900';

    private BeneficiaryV2SnapshotService $snapshot;
    private SensitiveDataService $crypto;
    private EnrollmentV2Masters $masters;
    private BeneficiaryChangeRequestService $changeRequests;
    private BeneficiaryV2Model $beneficiaries;
    private BeneficiaryDependentV2Model $dependents;
    private BeneficiaryChangeRequestModel $changeRequestModel;
    private array $dependentValidationErrors = [];
    private ?array $diffLookupCache = null;

    public function __construct(
        ?BeneficiaryV2SnapshotService $snapshot = null,
        ?SensitiveDataService $crypto = null,
        ?EnrollmentV2Masters $masters = null,
        ?BeneficiaryChangeRequestService $changeRequests = null,
        ?BeneficiaryV2Model $beneficiaries = null,
        ?BeneficiaryDependentV2Model $dependents = null,
        ?BeneficiaryChangeRequestModel $changeRequestModel = null
    ) {
        $this->snapshot       = $snapshot ?? new BeneficiaryV2SnapshotService();
        $this->crypto         = $crypto ?? new SensitiveDataService();
        $this->masters        = $masters ?? config('EnrollmentV2Masters');
        $this->changeRequests = $changeRequests ?? service('beneficiaryChangeRequest');
        $this->beneficiaries  = $beneficiaries ?? new BeneficiaryV2Model();
        $this->dependents     = $dependents ?? new BeneficiaryDependentV2Model();
        $this->changeRequestModel = $changeRequestModel ?? new BeneficiaryChangeRequestModel();
    }

    public function getMasterOptions(): array
    {
        $data = $this->masters->data ?? [];
        $raoOfficeMap = $this->buildRaoOfficeMap($data);

        return [
            'planOptions'       => $data['planOptions'] ?? [],
            'categories'        => $data['beneficiaryCategories'] ?? [],
            'genders'           => $data['genders'] ?? [],
            'raos'              => $data['raos'] ?? [],
            'retirementOffices' => $data['retirementOffices'] ?? [],
            'designations'      => $data['designations'] ?? [],
            'bloodGroups'       => $data['bloodGroups'] ?? [],
            'bankSources'       => $data['bankSources'] ?? [],
            'bankServicing'     => $data['bankServicing'] ?? [],
            'states'            => $data['states'] ?? [],
            'raoOfficeMap'      => $raoOfficeMap,
            'dependentRelationships' => $data['dependentRelationships'] ?? [
                ['id' => 'SPOUSE', 'label' => 'Spouse'],
                ['id' => 'SON', 'label' => 'Son'],
                ['id' => 'DAUGHTER', 'label' => 'Daughter'],
                ['id' => 'MOTHER', 'label' => 'Mother'],
                ['id' => 'FATHER', 'label' => 'Father'],
                ['id' => 'OTHER', 'label' => 'Other'],
            ],
            'dependentStatuses'      => [
                ['id' => 'ALIVE', 'label' => 'Alive'],
                ['id' => 'NOT_ALIVE', 'label' => 'Not Alive'],
                ['id' => 'NOT_APPLICABLE', 'label' => 'Not Applicable'],
            ],
            'healthCoverageOptions' => [
                ['id' => 'YES', 'label' => 'Yes'],
                ['id' => 'NO', 'label' => 'No'],
                ['id' => 'NOT_APPLICABLE', 'label' => 'Not Applicable'],
            ],
        ];
    }

    /**
     * Lookup tables for presenting diff values with human-friendly labels.
     *
     * @return array<string,array<string,string>>
     */
    public function getDiffLookups(): array
    {
        if ($this->diffLookupCache !== null) {
            return $this->diffLookupCache;
        }

        $data = $this->masters->data ?? [];
        $buildLookup = static function (array $rows, string $primaryKey = 'id'): array {
            $map = [];
            foreach ($rows as $row) {
                if (! isset($row[$primaryKey])) {
                    continue;
                }

                $value = $row[$primaryKey];
                $label = null;
                foreach (['label', 'name', 'title'] as $key) {
                    if (isset($row[$key]) && $row[$key] !== '') {
                        $label = (string) $row[$key];
                        break;
                    }
                }
                if ($label === null && isset($row['code'])) {
                    $label = (string) $row['code'];
                }
                if ($label === null) {
                    $label = (string) $value;
                }

                $map[(string) $value] = $label;
                if (is_numeric($value)) {
                    $map[(int) $value] = $label;
                }
                if (isset($row['code'])) {
                    $map[(string) $row['code']] = $label;
                }
            }

            return $map;
        };

        $this->diffLookupCache = [
            'plan_option_id'      => $buildLookup($data['planOptions'] ?? []),
            'category_id'         => $buildLookup($data['beneficiaryCategories'] ?? []),
            'rao_id'              => $buildLookup($data['raos'] ?? []),
            'retirement_office_id'=> $buildLookup($data['retirementOffices'] ?? []),
            'designation_id'      => $buildLookup($data['designations'] ?? []),
            'bank_source_id'      => $buildLookup($data['bankSources'] ?? []),
            'bank_servicing_id'   => $buildLookup($data['bankServicing'] ?? []),
            'state_id'            => $buildLookup($data['states'] ?? []),
            'blood_group_id'      => $buildLookup($data['bloodGroups'] ?? []),
            'gender'              => $buildLookup($data['genders'] ?? []),
            'relationship'        => $buildLookup($data['dependentRelationships'] ?? []),
            'is_alive'            => [
                'ALIVE'          => 'Alive',
                'NOT_ALIVE'      => 'Not Alive',
                'NOT_APPLICABLE' => 'Not Applicable',
            ],
            'is_health_dependant' => [
                'YES'            => 'Yes',
                'NO'             => 'No',
                'NOT_APPLICABLE' => 'Not Applicable',
            ],
        ];

        return $this->diffLookupCache;
    }

    public function buildInitialForm(int $beneficiaryId): array
    {
        $snapshot = $this->snapshot->findByBeneficiaryId($beneficiaryId);

        if (! $snapshot) {
            throw new RuntimeException('Beneficiary snapshot not found.');
        }

        return [
            'snapshot' => $snapshot,
            'form'     => $this->hydrateFormFromSnapshot($snapshot),
        ];
    }

    public function validateDraft(int $beneficiaryId, array $input, ValidationInterface $validation): bool
    {
        $this->dependentValidationErrors = [];

        $snapshot = $this->snapshot->findByBeneficiaryId($beneficiaryId);
        if (! $snapshot) {
            $validation->setError('general', 'Beneficiary record not found.');
            return false;
        }

        $stringFields = [
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'bank_account',
            'bank_source_other',
            'bank_servicing_other',
            'ppo_number',
            'pran_number',
            'gpf_number',
            'aadhaar',
            'pan',
            'samagra',
            'deceased_employee_name',
        ];

        foreach ($stringFields as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }

        $alphaRule = 'regex_match[/^[A-Za-z]+$/]';
        $emailRule = 'regex_match[/^[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)*@[A-Za-z0-9]+(?:\.[A-Za-z0-9]+)+$/]';
        $bankAccountRule = 'regex_match[/^(?!([0-9])\1{8,17}$)[0-9]{9,18}$/]';
        $aadhaarRule = 'regex_match[/^(?!([0-9])\1{11}$)[0-9]{12}$/]';
        $gpfRule = 'regex_match[/^(?!([0-9])\1{7}$)[0-9]{8}$/]';
        $samagraRule = 'regex_match[/^(?!([0-9])\1{7,8}$)[0-9]{8,9}$/]';
        $ppoRule = 'regex_match[/^[A-Za-z0-9@&\/\-\s]+$/]';

        $rules = [
            'plan_option_id'           => 'required|is_natural_no_zero',
            'category_id'              => 'required|is_natural_no_zero',
            'first_name'               => [
                'label'  => 'First Name',
                'rules'  => "required|min_length[2]|max_length[120]|{$alphaRule}",
                'errors' => [
                    'regex_match' => 'First Name should contain alphabets only (no spaces).',
                ],
            ],
            'middle_name'              => [
                'label'  => 'Middle Name',
                'rules'  => "permit_empty|max_length[120]|{$alphaRule}",
                'errors' => [
                    'regex_match' => 'Middle Name should contain alphabets only (no spaces).',
                ],
            ],
            'last_name'                => [
                'label'  => 'Last Name',
                'rules'  => "required|min_length[2]|max_length[120]|{$alphaRule}",
                'errors' => [
                    'regex_match' => 'Last Name should contain alphabets only (no spaces).',
                ],
            ],
            'gender'                   => 'required|in_list[MALE,FEMALE,TRANSGENDER]',
            'date_of_birth'            => 'required|valid_date[Y-m-d]',
            'retirement_or_death_date' => 'permit_empty|valid_date[Y-m-d]',
            'rao_id'                   => 'permit_empty|is_natural',
            'retirement_office_id'     => 'permit_empty|is_natural',
            'designation_id'           => 'permit_empty|is_natural',
            'primary_mobile'           => 'permit_empty',
            'alternate_mobile'         => 'permit_empty',
            'email'                    => [
                'label'  => 'Email',
                'rules'  => "permit_empty|{$emailRule}",
                'errors' => [
                    'regex_match' => 'Enter a valid email without spaces (allowed special characters: @ and .).',
                ],
            ],
            'state_id'                 => 'required|is_natural_no_zero',
            'postal_code'              => 'required|regex_match[/^[0-9]{6}$/]',
            'bank_account'             => [
                'label'  => 'Bank Account Number',
                'rules'  => "permit_empty|{$bankAccountRule}",
                'errors' => [
                    'regex_match' => 'Bank account number must be 9-18 digits and not all identical.',
                ],
            ],
            'ppo_number'               => [
                'label'  => 'PPO Number',
                'rules'  => "permit_empty|max_length[100]|{$ppoRule}",
                'errors' => [
                    'regex_match' => 'PPO number allows letters, numbers, spaces and @, &, /, - characters only.',
                ],
            ],
            'gpf_number'               => [
                'label'  => 'GPF Number',
                'rules'  => "permit_empty|{$gpfRule}",
                'errors' => [
                    'regex_match' => 'GPF number must be exactly 8 digits and not all identical.',
                ],
            ],
            'aadhaar'                  => [
                'label'  => 'Aadhaar Number',
                'rules'  => "permit_empty|{$aadhaarRule}",
                'errors' => [
                    'regex_match' => 'Aadhaar must be 12 digits without spaces and not all identical.',
                ],
            ],
            'pan'                      => 'permit_empty|regex_match[/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i]',
            'samagra'                  => [
                'label'  => 'Samagra ID',
                'rules'  => "permit_empty|{$samagraRule}",
                'errors' => [
                    'regex_match' => 'Samagra ID must be 8 or 9 digits and not all identical.',
                ],
            ],
            'undertaking_confirmed'    => [
                'label'  => 'Undertaking',
                'rules'  => 'required|in_list[yes]',
                'errors' => [
                    'required' => 'Please accept the undertaking before submitting.',
                    'in_list'  => 'Please accept the undertaking before submitting.',
                ],
            ],
        ];

        $validation->setRules($rules);

        if (! $validation->run($input)) {
            return false;
        }

        $dobTime = null;
        $dob = $input['date_of_birth'] ?? null;
        if ($dob) {
            try {
                $dobTime = Time::createFromFormat('Y-m-d', $dob);
            } catch (\Throwable $exception) {
                $dobTime = null;
            }

            if (! $dobTime instanceof Time || $dobTime->isAfter(Time::today())) {
                $validation->setError('date_of_birth', 'Date of Birth cannot be in the future.');
                return false;
            }
        }

        $retirementDate = $input['retirement_or_death_date'] ?? null;
        if ($retirementDate) {
            try {
                $retirementTime = Time::createFromFormat('Y-m-d', $retirementDate);
            } catch (\Throwable $exception) {
                $retirementTime = null;
            }

            if (! $retirementTime instanceof Time) {
                $validation->setError('retirement_or_death_date', 'Enter a valid retirement / death date.');
                return false;
            }

            if ($retirementTime->isAfter(Time::today())) {
                $validation->setError('retirement_or_death_date', 'Retirement / death date cannot be in the future.');
                return false;
            }

            if ($dobTime instanceof Time && $retirementTime->isBefore($dobTime)) {
                $validation->setError('retirement_or_death_date', 'Retirement / death date cannot be earlier than the date of birth.');
                return false;
            }
        }

        $familyPensionerId = $this->getCategoryIdByCode('FAMILY_PENSIONER');
        if ($familyPensionerId !== null && (string) ($input['category_id'] ?? '') === (string) $familyPensionerId) {
            $name = trim((string) ($input['deceased_employee_name'] ?? ''));
            if ($name === '') {
                $validation->setError('deceased_employee_name', 'Please enter the deceased employee’s name for family pensioners.');
                return false;
            }
        }

        $otherFieldRules = [
            ['select' => 'rao_id', 'other' => 'rao_other', 'label' => 'Regional Account Office'],
            ['select' => 'retirement_office_id', 'other' => 'retirement_office_other', 'label' => 'Office at Retirement'],
            ['select' => 'designation_id', 'other' => 'designation_other', 'label' => 'Designation at Retirement'],
            ['select' => 'bank_source_id', 'other' => 'bank_source_other', 'label' => 'Bank (Source)'],
            ['select' => 'bank_servicing_id', 'other' => 'bank_servicing_other', 'label' => 'Bank (Servicing Branch)'],
        ];

        foreach ($otherFieldRules as $rule) {
            if (! $this->validateOtherField($input, $validation, $rule['select'], $rule['other'], $rule['label'])) {
                return false;
            }
        }

        $dependentErrors = $this->validateDependentDraft($input['dependents'] ?? []);
        if (! empty($dependentErrors)) {
            $this->dependentValidationErrors = $dependentErrors;
            $validation->setError('dependents', 'Please review dependent entries highlighted below.');
            return false;
        }

        $raoId = $input['rao_id'] ?? null;
        $officeId = $input['retirement_office_id'] ?? null;
        if ($raoId !== null && $officeId !== null && $officeId !== '' && (string) $officeId !== '900') {
            $raoOfficeMap = $this->buildRaoOfficeMap($this->masters->data ?? []);
            $allowedOffices = $raoOfficeMap[(string) $raoId] ?? null;
            if (is_array($allowedOffices) && ! empty($allowedOffices) && ! in_array((string) $officeId, $allowedOffices, true)) {
                $validation->setError('retirement_office_id', 'Select an office associated with the chosen RAO.');
                return false;
            }
        }

        $beforeBeneficiary = $this->normalizeBeneficiarySnapshot($snapshot);
        $beforeDependents  = $this->normalizeDependentsSnapshot($snapshot['dependents'] ?? []);
        $afterBeneficiary  = $this->normalizeBeneficiaryInput($input, $beforeBeneficiary);
        $afterDependents   = $this->normalizeDependentsInput($input['dependents'] ?? $input['dependents_json'] ?? [], $beforeDependents);

        if (! $this->checkIdentifierConflicts($beneficiaryId, $afterBeneficiary, $afterDependents, $validation)) {
            return false;
        }

        return true;
    }

    public function buildPreviewPayload(int $beneficiaryId, array $input): array
    {
        $snapshot = $this->snapshot->findByBeneficiaryId($beneficiaryId);
        if (! $snapshot) {
            throw new RuntimeException('Beneficiary snapshot not found.');
        }

        $beforeBeneficiary = $this->normalizeBeneficiarySnapshot($snapshot);
        $beforeDependents  = $this->normalizeDependentsSnapshot($snapshot['dependents'] ?? []);

        $afterBeneficiary = $this->normalizeBeneficiaryInput($input, $beforeBeneficiary);
        $afterDependents  = $this->normalizeDependentsInput($input['dependents'] ?? $input['dependents_json'] ?? [], $beforeDependents);

        $diff = $this->calculateDiff($beforeBeneficiary, $afterBeneficiary, $beforeDependents, $afterDependents);

        return [
            'beneficiary_id'    => $beneficiaryId,
            'reference_number'  => $snapshot['reference_number'] ?? null,
            'legacy_reference'  => $snapshot['legacy_reference'] ?? null,
            'before'            => [
                'beneficiary' => $beforeBeneficiary,
                'dependents'  => $beforeDependents,
            ],
            'after'             => [
                'beneficiary' => $afterBeneficiary,
                'dependents'  => $afterDependents,
            ],
            'diff'              => $diff,
            'summary'           => $this->summarizeDiff($diff),
            'undertaking_confirmed' => isset($input['undertaking_confirmed']) && $input['undertaking_confirmed'] === 'yes',
        ];
    }

    /**
     * Submit a change request using the preview payload.
     *
     * @param array $meta Additional metadata such as ip_address and user_agent.
     */
    public function submitChangeRequest(int $beneficiaryId, int $userId, array $preview, array $meta = []): array
    {
        $draft = $this->changeRequests->saveDraft(
            $beneficiaryId,
            $userId,
            $preview['before'],
            $preview['after'],
            $preview['summary'],
            $preview['diff'] ?? [],
            [
                'reference_number' => $preview['reference_number'] ?? null,
                'legacy_reference' => $preview['legacy_reference'] ?? null,
                'ip_address'       => $meta['ip_address'] ?? null,
                'user_agent'       => $meta['user_agent'] ?? null,
                'undertaking_confirmed' => ! empty($preview['undertaking_confirmed']),
            ]
        );

        $this->changeRequests->syncDependentDiffs($draft['id'], $preview['diff']['dependents']);

        return $this->changeRequests->submitDraft($draft['id'], $userId);
    }

    /**
     * Backwards-compatible alias used by controllers.
     */
    public function applyConfirmedChanges(int $beneficiaryId, array $payload, array $meta = []): array
    {
        if (empty($payload['after']) || empty($payload['before'])) {
            throw new RuntimeException('Invalid change payload.');
        }

        $userId = $payload['user_id'] ?? null;
        if (! $userId) {
            throw new RuntimeException('User context missing for change submission.');
        }

        return $this->submitChangeRequest($beneficiaryId, (int) $userId, $payload, $meta);
    }

    private function hydrateFormFromSnapshot(array $snapshot): array
    {
        return [
            'beneficiary' => [
                'plan_option_id'           => $snapshot['plan_option_id'] ?? null,
                'category_id'              => $snapshot['category_id'] ?? null,
                'first_name'               => $snapshot['first_name'] ?? '',
                'middle_name'              => $snapshot['middle_name'] ?? '',
                'last_name'                => $snapshot['last_name'] ?? '',
                'gender'                   => $this->normalizeCode($snapshot['gender'] ?? null),
                'date_of_birth'            => $snapshot['date_of_birth'] ?? null,
                'retirement_or_death_date' => $snapshot['retirement_or_death_date'] ?? null,
                'deceased_employee_name'   => $snapshot['deceased_employee_name'] ?? '',
                'rao_id'                   => $snapshot['rao_id'] ?? null,
                'rao_other'                => $snapshot['rao_other'] ?? '',
                'retirement_office_id'     => $snapshot['retirement_office_id'] ?? null,
                'retirement_office_other'  => $snapshot['retirement_office_other'] ?? '',
                'designation_id'           => $snapshot['designation_id'] ?? null,
                'designation_other'        => $snapshot['designation_other'] ?? '',
                'correspondence_address'   => $snapshot['correspondence_address'] ?? '',
                'city'                     => $snapshot['city'] ?? '',
                'state_id'                 => $snapshot['state_id'] ?? null,
                'postal_code'              => $snapshot['postal_code'] ?? '',
                'primary_mobile'           => $snapshot['primary_mobile_masked'] ?? '',
                'alternate_mobile'         => $snapshot['alternate_mobile_masked'] ?? '',
                'email'                    => $snapshot['email'] ?? '',
                'bank_source_id'           => $snapshot['bank_source_id'] ?? null,
                'bank_source_other'        => $snapshot['bank_source_other'] ?? '',
                'bank_servicing_id'        => $snapshot['bank_servicing_id'] ?? null,
                'bank_servicing_other'     => $snapshot['bank_servicing_other'] ?? '',
                'bank_account'             => $snapshot['bank_account_masked'] ?? '',
                'ppo_number'               => $snapshot['ppo_number_masked'] ?? '',
                'pran_number'              => $snapshot['pran_number_masked'] ?? '',
                'gpf_number'               => $snapshot['gpf_number_masked'] ?? '',
                'aadhaar'                  => $snapshot['aadhaar_masked'] ?? '',
                'pan'                      => $snapshot['pan_masked'] ?? '',
                'samagra'                  => $snapshot['samagra_masked'] ?? '',
                'blood_group_id'           => $snapshot['blood_group_id'] ?? null,
            ],
            'dependents' => $this->normalizeDependentsSnapshot($snapshot['dependents'] ?? []),
        ];
    }

    private function normalizeBeneficiarySnapshot(array $snapshot): array
    {
        $fields = array_keys($this->normalizeBeneficiaryInput([], []));
        $result = [];
        $aliases = [
            'ppo_number'    => 'ppo_number_masked',
            'pran_number'   => 'pran_number_masked',
            'gpf_number'    => 'gpf_number_masked',
            'bank_account'  => 'bank_account_masked',
            'aadhaar'       => 'aadhaar_masked',
            'pan'           => 'pan_masked',
            'samagra'       => 'samagra_masked',
            'primary_mobile'=> 'primary_mobile_masked',
            'alternate_mobile' => 'alternate_mobile_masked',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $snapshot)) {
                $result[$field] = $snapshot[$field];
                continue;
            }

            if (isset($aliases[$field]) && array_key_exists($aliases[$field], $snapshot)) {
                $result[$field] = $snapshot[$aliases[$field]];
                continue;
            }

            $result[$field] = null;
        }

        if (isset($result['gender']) && is_string($result['gender'])) {
            $result['gender'] = $this->normalizeCode($result['gender']);
        }

        return $result;
    }

    private function normalizeBeneficiaryInput(array $input, array $current): array
    {
        $fields = [
            'plan_option_id',
            'category_id',
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'date_of_birth',
            'retirement_or_death_date',
            'deceased_employee_name',
            'rao_id',
            'rao_other',
            'retirement_office_id',
            'retirement_office_other',
            'designation_id',
            'designation_other',
            'correspondence_address',
            'city',
            'state_id',
            'postal_code',
            'primary_mobile',
            'alternate_mobile',
            'email',
            'bank_source_id',
            'bank_source_other',
            'bank_servicing_id',
            'bank_servicing_other',
            'bank_account',
            'ppo_number',
            'pran_number',
            'gpf_number',
            'aadhaar',
            'pan',
            'samagra',
            'blood_group_id',
        ];

        $lockedFields = ['primary_mobile', 'alternate_mobile'];

        $normalized = [];
        $inputTouched = [];
        foreach ($fields as $field) {
            if (in_array($field, $lockedFields, true)) {
                $normalized[$field] = $current[$field] ?? null;
                $inputTouched[$field] = false;
                continue;
            }

            if (! array_key_exists($field, $input)) {
                $normalized[$field] = $current[$field] ?? null;
                $inputTouched[$field] = false;
                continue;
            }

            $value = $input[$field];
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '' || $value === null) {
                $normalized[$field] = $current[$field] ?? null;
                $inputTouched[$field] = false;
            } else {
                $normalized[$field] = $value;
                $inputTouched[$field] = true;
            }
        }

        $normalized = $this->applyBeneficiaryCleanups($normalized, $inputTouched);

        return $normalized;
    }

    private function applyBeneficiaryCleanups(array $normalized, array $inputTouched = []): array
    {
        if (! empty($inputTouched['aadhaar'])) {
            $normalized['aadhaar'] = $this->normalizeDigits($normalized['aadhaar'] ?? null);
        }

        if (! empty($inputTouched['samagra'])) {
            $normalized['samagra'] = $this->normalizeDigits($normalized['samagra'] ?? null);
        }

        if (! empty($inputTouched['pan']) && isset($normalized['pan'])) {
            $normalized['pan'] = $this->normalizePan($normalized['pan']);
        }

        if (! empty($inputTouched['bank_account']) && isset($normalized['bank_account']) && is_string($normalized['bank_account'])) {
            $normalized['bank_account'] = preg_replace('/\s+/', '', $normalized['bank_account']);
            $normalized['bank_account'] = $normalized['bank_account'] === '' ? null : $normalized['bank_account'];
        }

        $normalized['rao_other'] = $this->normalizeOtherValue($normalized['rao_id'] ?? null, $normalized['rao_other'] ?? null);
        $normalized['retirement_office_other'] = $this->normalizeOtherValue($normalized['retirement_office_id'] ?? null, $normalized['retirement_office_other'] ?? null);
        $normalized['designation_other'] = $this->normalizeOtherValue($normalized['designation_id'] ?? null, $normalized['designation_other'] ?? null);
        $normalized['bank_source_other'] = $this->normalizeOtherValue($normalized['bank_source_id'] ?? null, $normalized['bank_source_other'] ?? null);
        $normalized['bank_servicing_other'] = $this->normalizeOtherValue($normalized['bank_servicing_id'] ?? null, $normalized['bank_servicing_other'] ?? null);

        return $normalized;
    }

    public function getDependentValidationErrors(): array
    {
        return $this->dependentValidationErrors;
    }

    private function validateDependentDraft($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $errors = [];
        foreach ($input as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $isDeleted = ! empty($row['is_deleted']);
            if ($isDeleted) {
                continue;
            }

            $rowErrors = [];
            $firstName = trim((string) ($row['first_name'] ?? ''));
            if ($firstName === '') {
                $rowErrors['first_name'] = 'Enter the dependent\'s full name.';
            }

            $relationship = strtoupper(trim((string) ($row['relationship'] ?? '')));
            if ($relationship === '') {
                $rowErrors['relationship'] = 'Select a relationship.';
            }

            $alive = strtoupper(trim((string) ($row['is_alive'] ?? '')));
            if ($alive === '') {
                $rowErrors['is_alive'] = 'Select alive status.';
            }

            $gender = strtoupper(trim((string) ($row['gender'] ?? '')));
            if ($gender === '') {
                $rowErrors['gender'] = 'Select gender.';
            }

            $dob = trim((string) ($row['date_of_birth'] ?? ''));
            $requiresDob = ! in_array($alive, ['NOT_ALIVE', 'NOT_APPLICABLE'], true);
            if ($dob === '') {
                if ($requiresDob) {
                    $rowErrors['date_of_birth'] = 'Enter date of birth.';
                }
            } else {
                try {
                    $dobTime = Time::createFromFormat('Y-m-d', $dob);
                } catch (\Throwable $exception) {
                    $dobTime = null;
                }

                if (! $dobTime instanceof Time || $dobTime->isAfter(Time::today())) {
                    $rowErrors['date_of_birth'] = 'Date of birth cannot be in the future.';
                }
            }

            $order = $row['dependant_order'] ?? null;
            if ($order !== null && $order !== '') {
                if (! ctype_digit((string) $order) || (int) $order < 1) {
                    $rowErrors['dependant_order'] = 'Order must be a positive number.';
                }
            }

            $aadhaar = preg_replace('/\D+/', '', (string) ($row['aadhaar'] ?? ''));
            if ($aadhaar !== '' && strlen($aadhaar) !== 12) {
                $rowErrors['aadhaar'] = 'Aadhaar must be 12 digits.';
            }

            if (! empty($rowErrors)) {
                $errors[$index] = $rowErrors;
            }
        }

        return $errors;
    }

    private function normalizeDependentsSnapshot(array $dependents): array
    {
        $normalized = [];

        foreach ($dependents as $dependent) {
            if (isset($dependent['is_active']) && (int) $dependent['is_active'] === 0) {
                continue;
            }

            $relationship = $dependent['relationship_key'] ?? $dependent['relationship'] ?? null;
            if (is_string($relationship)) {
                $relationship = $this->normalizeCode($relationship);
            }

            $dob = $dependent['date_of_birth'] ?? null;
            if ($dob === '') {
                $dob = null;
            }

            $normalized[] = [
                'id'                  => $dependent['id'] ?? null,
                'temp_id'             => null,
                'relationship'        => $relationship,
                'dependant_order'     => $dependent['dependant_order'] ?? null,
                'twin_group'          => $dependent['twin_group'] ?? null,
                'is_alive'            => strtoupper($dependent['alive_status_raw'] ?? $dependent['is_alive'] ?? '') ?: null,
                'is_health_dependant' => strtoupper($dependent['health_status_raw'] ?? $dependent['is_health_dependant'] ?? '') ?: null,
                'first_name'          => $dependent['full_name'] ?? $dependent['first_name'] ?? null,
                'gender'              => strtoupper($dependent['gender'] ?? '') ?: null,
                'blood_group_id'      => $dependent['blood_group_id'] ?? null,
                'date_of_birth'       => $dob,
                'aadhaar'             => null,
                'aadhaar_masked'      => $dependent['aadhaar_masked'] ?? null,
            ];
        }

        return $normalized;
    }

    private function validateOtherField(array $input, ValidationInterface $validation, string $selectField, string $otherField, string $label): bool
    {
        $selected = $input[$selectField] ?? null;
        if ((string) $selected !== self::OTHER_OPTION_VALUE) {
            return true;
        }

        $value = trim((string) ($input[$otherField] ?? ''));
        if ($value === '') {
            $validation->setError($otherField, "Kindly specify the {$label}.");
            return false;
        }

        if (mb_strlen($value) > 150) {
            $validation->setError($otherField, "{$label} details must be 150 characters or fewer.");
            return false;
        }

        return true;
    }

    private function normalizeOtherValue($selected, $otherValue): ?string
    {
        if ((string) $selected !== self::OTHER_OPTION_VALUE) {
            return null;
        }

        if ($otherValue === null) {
            return null;
        }

        $value = trim((string) $otherValue);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 150);
    }

    private function normalizeDigits($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    private function normalizePan($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', (string) $value));
        return $normalized === '' ? null : $normalized;
    }

    private function getCategoryIdByCode(string $code): ?int
    {
        $categories = $this->masters->data['beneficiaryCategories'] ?? [];
        foreach ($categories as $category) {
            if (($category['code'] ?? null) === $code) {
                return isset($category['id']) ? (int) $category['id'] : null;
            }
        }

        return null;
    }

    private function checkIdentifierConflicts(
        int $beneficiaryId,
        array $afterBeneficiary,
        array $afterDependents,
        ValidationInterface $validation
    ): bool {
        $hasError = false;
        $dependentErrors = [];

        $beneficiaryAadhaar = $afterBeneficiary['aadhaar'] ?? null;
        $beneficiaryPan     = $afterBeneficiary['pan'] ?? null;

        $dependentAadhaarMap = [];
        foreach ($afterDependents as $index => $row) {
            if (! empty($row['is_deleted'])) {
                continue;
            }

            $aadhaar = $row['aadhaar'] ?? null;
            if ($aadhaar === null) {
                continue;
            }

            if ($beneficiaryAadhaar !== null && $aadhaar === $beneficiaryAadhaar) {
                $dependentErrors[$index]['aadhaar'] = 'Matches beneficiary Aadhaar.';
                if (! $validation->hasError('aadhaar')) {
                    $validation->setError('aadhaar', 'Beneficiary Aadhaar matches one of the dependents.');
                }
                $hasError = true;
            }

            if (isset($dependentAadhaarMap[$aadhaar])) {
                $firstIndex = $dependentAadhaarMap[$aadhaar];
                $dependentErrors[$firstIndex]['aadhaar'] = $dependentErrors[$firstIndex]['aadhaar'] ?? 'Duplicate Aadhaar among dependents.';
                $dependentErrors[$index]['aadhaar']      = 'Duplicate Aadhaar among dependents.';
                $hasError = true;
            } else {
                $dependentAadhaarMap[$aadhaar] = $index;
            }
        }

        [$pendingAadhaars, $pendingPans] = $this->collectPendingIdentifierIndex($beneficiaryId);

        if ($beneficiaryAadhaar !== null) {
            $conflictId = $this->findBeneficiaryAadhaarConflict($beneficiaryId, $beneficiaryAadhaar);
            if ($conflictId !== null || isset($pendingAadhaars[$beneficiaryAadhaar])) {
                $message = $conflictId !== null
                    ? 'This Aadhaar is already registered with another beneficiary.'
                    : 'This Aadhaar is already part of a pending change request.';
                $validation->setError('aadhaar', $message);
                $hasError = true;
            }
        }

        if ($beneficiaryPan !== null) {
            $conflictId = $this->findBeneficiaryPanConflict($beneficiaryId, $beneficiaryPan);
            if ($conflictId !== null || isset($pendingPans[$beneficiaryPan])) {
                $message = $conflictId !== null
                    ? 'This PAN is already registered with another beneficiary.'
                    : 'This PAN is already part of a pending change request.';
                $validation->setError('pan', $message);
                $hasError = true;
            }
        }

        $aadhaarDbCache = [];
        foreach ($afterDependents as $index => $row) {
            if (! empty($row['is_deleted'])) {
                continue;
            }
            $aadhaar = $row['aadhaar'] ?? null;
            if ($aadhaar === null) {
                continue;
            }

            $cacheKey = $aadhaar;
            if (! array_key_exists($cacheKey, $aadhaarDbCache)) {
                $conflict = $this->findDependentAadhaarConflict($beneficiaryId, $row['id'] ?? null, $aadhaar);
                if ($conflict !== null) {
                    $aadhaarDbCache[$cacheKey] = 'existing';
                } elseif (isset($pendingAadhaars[$aadhaar])) {
                    $aadhaarDbCache[$cacheKey] = 'pending';
                } else {
                    $aadhaarDbCache[$cacheKey] = null;
                }
            }

            $status = $aadhaarDbCache[$cacheKey];
            if ($status !== null) {
                $dependentErrors[$index]['aadhaar'] = $status === 'existing'
                    ? 'This Aadhaar is already registered with another beneficiary.'
                    : 'This Aadhaar is already part of a pending change request.';
                $hasError = true;
            }
        }

        if ($hasError && ! empty($dependentErrors)) {
            $this->dependentValidationErrors = $dependentErrors;
            if (! $validation->hasError('dependents')) {
                $validation->setError('dependents', 'Kindly resolve the highlighted Aadhaar entries for dependents.');
            }
        }

        return ! $hasError;
    }

    private function collectPendingIdentifierIndex(int $excludeBeneficiaryId): array
    {
        $pendingStatuses = ['pending', 'needs_info'];
        $rows = $this->changeRequestModel
            ->select('beneficiary_v2_id, payload_after')
            ->whereIn('status', $pendingStatuses)
            ->where('beneficiary_v2_id !=', $excludeBeneficiaryId)
            ->findAll();

        $aadhaarIndex = [];
        $panIndex     = [];

        foreach ($rows as $row) {
            if (empty($row['payload_after'])) {
                continue;
            }

            try {
                $payload = json_decode($row['payload_after'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $exception) {
                continue;
            }

            $beneficiary = $payload['beneficiary'] ?? [];
            if (! empty($beneficiary['aadhaar'])) {
                $normalizedAadhaar = $this->normalizeDigits($beneficiary['aadhaar']);
                if ($normalizedAadhaar !== null) {
                    $aadhaarIndex[$normalizedAadhaar] = (int) $row['beneficiary_v2_id'];
                }
            }
            if (! empty($beneficiary['pan'])) {
                $normalizedPan = $this->normalizePan($beneficiary['pan']);
                if ($normalizedPan !== null) {
                    $panIndex[$normalizedPan] = (int) $row['beneficiary_v2_id'];
                }
            }

            foreach ($payload['dependents'] ?? [] as $dependent) {
                if (empty($dependent['aadhaar'])) {
                    continue;
                }

                $normalized = $this->normalizeDigits($dependent['aadhaar']);
                if ($normalized !== null) {
                    $aadhaarIndex[$normalized] = (int) $row['beneficiary_v2_id'];
                }
            }
        }

        return [$aadhaarIndex, $panIndex];
    }

    private function findBeneficiaryAadhaarConflict(int $beneficiaryId, string $aadhaar): ?int
    {
        $last4 = substr($aadhaar, -4);
        $builder = $this->beneficiaries->builder();
        $builder
            ->select('id, aadhaar_enc')
            ->where('id !=', $beneficiaryId)
            ->where('aadhaar_enc IS NOT NULL', null, false)
            ->like('aadhaar_masked', $last4, 'before');

        $candidates = $builder->get()->getResultArray();
        foreach ($candidates as $candidate) {
            try {
                $raw = $this->crypto->decrypt($candidate['aadhaar_enc']);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($raw === $aadhaar) {
                return (int) $candidate['id'];
            }
        }

        return null;
    }

    private function findBeneficiaryPanConflict(int $beneficiaryId, string $pan): ?int
    {
        $last4 = substr($pan, -4);
        $builder = $this->beneficiaries->builder();
        $builder
            ->select('id, pan_enc')
            ->where('id !=', $beneficiaryId)
            ->where('pan_enc IS NOT NULL', null, false)
            ->like('pan_masked', $last4, 'before');

        $candidates = $builder->get()->getResultArray();
        foreach ($candidates as $candidate) {
            try {
                $raw = $this->crypto->decrypt($candidate['pan_enc']);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($this->normalizePan($raw) === $this->normalizePan($pan)) {
                return (int) $candidate['id'];
            }
        }

        return null;
    }

    private function findDependentAadhaarConflict(int $beneficiaryId, ?int $dependentId, string $aadhaar): ?array
    {
        $last4 = substr($aadhaar, -4);
        $builder = $this->dependents->builder();
        $builder
            ->select('id, beneficiary_id, aadhaar_enc')
            ->where('aadhaar_enc IS NOT NULL', null, false)
            ->where('is_active', 1)
            ->groupStart()
                ->where('deleted_at', null)
                ->orWhere('deleted_at', '')
            ->groupEnd()
            ->like('aadhaar_masked', $last4, 'before');

        if ($dependentId) {
            $builder->where('id !=', $dependentId);
        }

        $candidates = $builder->get()->getResultArray();
        foreach ($candidates as $candidate) {
            try {
                $raw = $this->crypto->decrypt($candidate['aadhaar_enc']);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($raw === $aadhaar) {
                return [
                    'beneficiary_id' => (int) $candidate['beneficiary_id'],
                    'dependent_id'   => (int) $candidate['id'],
                ];
            }
        }

        return null;
    }

    private function normalizeDependentsInput($input, array $currentDependents = []): array
    {
        if (is_string($input) && $input !== '') {
            $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        }

        if (! is_array($input)) {
            return [];
        }

        $currentMap = [];
        foreach ($currentDependents as $row) {
            if (! empty($row['id'])) {
                $currentMap[(int) $row['id']] = $row;
            }
        }

        $normalized = [];
        foreach ($input as $row) {
            $relationship = strtoupper(trim((string) ($row['relationship'] ?? '')));
            $isAlive = strtoupper(trim((string) ($row['is_alive'] ?? '')));
            $health = strtoupper(trim((string) ($row['is_health_dependant'] ?? '')));
            $gender = strtoupper(trim((string) ($row['gender'] ?? '')));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $dependantOrderRaw = trim((string) ($row['dependant_order'] ?? ''));
            $twinGroup = trim((string) ($row['twin_group'] ?? ''));
            $dobInput = trim((string) ($row['date_of_birth'] ?? ''));
            if ($dobInput === '') {
                $dobInput = null;
            }

            $aadhaar = preg_replace('/\D+/', '', (string) ($row['aadhaar'] ?? ''));
            if ($aadhaar === '') {
                $aadhaar = null;
            }

            $tempId = $row['temp_id'] ?? null;
            $existingId = $row['id'] ?? null;
            if (! empty($existingId)) {
                $tempId = null;
            }
            $existingRow = null;
            if ($existingId !== null && isset($currentMap[(int) $existingId])) {
                $existingRow = $currentMap[(int) $existingId];
            }

            $dependantOrder = null;
            if ($dependantOrderRaw !== '') {
                $dependantOrder = ctype_digit($dependantOrderRaw) ? (int) $dependantOrderRaw : null;
            }

            $bloodGroup = $row['blood_group_id'] ?? ($existingRow['blood_group_id'] ?? null);
            if ($bloodGroup === '' || $bloodGroup === null) {
                $bloodGroup = null;
            }

            $isDeleted = $row['is_deleted'] ?? false;
            $isDeleted = $isDeleted === true || $isDeleted === 1 || $isDeleted === '1';

            $normalized[] = [
                'id'                  => $row['id'] ?? null,
                'temp_id'             => $tempId,
                'relationship'        => $relationship !== '' ? $relationship : null,
                'dependant_order'     => $dependantOrder,
                'twin_group'          => $twinGroup !== '' ? $twinGroup : null,
                'is_alive'            => $isAlive !== '' ? $isAlive : null,
                'is_health_dependant' => $health !== '' ? $health : null,
                'first_name'          => $firstName !== '' ? $firstName : null,
                'gender'              => $gender !== '' ? $gender : null,
                'blood_group_id'      => $bloodGroup,
                'date_of_birth'       => $dobInput,
                'aadhaar'             => $aadhaar,
                'aadhaar_masked'      => $existingRow['aadhaar_masked'] ?? null,
                'is_deleted'          => $isDeleted,
            ];
        }

        return $normalized;
    }

    private function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim($value));

        return $value === '' ? null : $value;
    }

    private function calculateDiff(array $beforeBeneficiary, array $afterBeneficiary, array $beforeDependents, array $afterDependents): array
    {
        $beneficiaryDiff = [];
        foreach ($afterBeneficiary as $field => $value) {
            $before = $beforeBeneficiary[$field] ?? null;
            if ($value !== $before) {
                $beneficiaryDiff[$field] = [
                    'before' => $before,
                    'after'  => $value,
                ];
            }
        }

        $dependentDiffs = [];
        $beforeMap = [];
        foreach ($beforeDependents as $row) {
            if (! empty($row['id'])) {
                $beforeMap['id:' . $row['id']] = $row;
            }
        }

        $afterMap = [];
        foreach ($afterDependents as $row) {
            $key = ! empty($row['id'])
                ? 'id:' . $row['id']
                : ('temp:' . ($row['temp_id'] ?? spl_object_id((object) $row)));
            $afterMap[$key] = $row;
        }

        // Removed rows
        foreach ($beforeMap as $key => $row) {
            if (! isset($afterMap[$key]) || ! empty($afterMap[$key]['is_deleted'])) {
                $dependentDiffs[] = [
                    'action'       => 'remove',
                    'dependent_id' => $row['id'],
                    'before'       => $row,
                    'after'        => null,
                ];
            }
        }

        // Added / updated
        foreach ($afterMap as $key => $row) {
            if (! empty($row['is_deleted'])) {
                continue;
            }

            if (str_starts_with($key, 'temp:')) {
                $dependentDiffs[] = [
                    'action' => 'add',
                    'before' => null,
                    'after'  => $row,
                ];
                continue;
            }

            $before = $beforeMap[$key] ?? null;
            if (! $before) {
                $dependentDiffs[] = [
                    'action' => 'add',
                    'before' => null,
                    'after'  => $row,
                ];
                continue;
            }

            $changed = [];
            foreach (['relationship', 'dependant_order', 'is_alive', 'is_health_dependant', 'first_name', 'gender', 'blood_group_id', 'date_of_birth', 'aadhaar_masked'] as $field) {
                if (($before[$field] ?? null) != ($row[$field] ?? null)) {
                    $changed[$field] = [
                        'before' => $before[$field] ?? null,
                        'after'  => $row[$field] ?? null,
                    ];
                }
            }

            if (! empty($changed)) {
                $dependentDiffs[] = [
                    'action'       => 'update',
                    'dependent_id' => $before['id'],
                    'before'       => $before,
                    'after'        => $row,
                    'changes'      => $changed,
                ];
            }
        }

        return [
            'beneficiary' => $beneficiaryDiff,
            'dependents'  => $dependentDiffs,
        ];
    }

    private function summarizeDiff(array $diff): array
    {
        $beneficiaryCount = count($diff['beneficiary'] ?? []);
        $dependentAdds    = 0;
        $dependentUpdates = 0;
        $dependentDeletes = 0;

        foreach ($diff['dependents'] ?? [] as $row) {
            switch ($row['action']) {
                case 'add':
                    $dependentAdds++;
                    break;
                case 'remove':
                    $dependentDeletes++;
                    break;
                case 'update':
                    $dependentUpdates++;
                    break;
            }
        }

        return [
            'beneficiary_changes' => $beneficiaryCount,
            'dependent_adds'      => $dependentAdds,
            'dependent_updates'   => $dependentUpdates,
            'dependent_removals'  => $dependentDeletes,
        ];
    }

    private function buildRaoOfficeMap(array $data): array
    {
        $map = [];

        $raos          = $data['raos'] ?? [];
        $retiredOffices = $data['retirementOffices'] ?? [];
        $raoRetireConfig = $data['raoRetirementMap'] ?? [];

        if (empty($raos) || empty($retiredOffices) || empty($raoRetireConfig)) {
            return $map;
        }

        $raoCodeToId = [];
        foreach ($raos as $rao) {
            if (isset($rao['code'], $rao['id'])) {
                $raoCodeToId[$rao['code']] = (string) $rao['id'];
            }
        }

        $officeCodeToId = [];
        foreach ($retiredOffices as $office) {
            if (isset($office['code'], $office['id'])) {
                $officeCodeToId[$office['code']] = (string) $office['id'];
            }
        }

        foreach ($raoRetireConfig as $raoCode => $officeCodes) {
            if (! isset($raoCodeToId[$raoCode])) {
                continue;
            }

            $raoId = $raoCodeToId[$raoCode];
            $map[$raoId] = [];

            foreach ($officeCodes as $officeCode) {
                if (isset($officeCodeToId[$officeCode])) {
                    $map[$raoId][] = $officeCodeToId[$officeCode];
                }
            }
        }

        return $map;
    }
}
