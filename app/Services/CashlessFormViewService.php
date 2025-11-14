<?php

namespace App\Services;

use App\Models\BeneficiaryModel;
use App\Models\BeneficiaryResidenceModel;
use App\Models\CityModel;
use App\Models\DependentModel;
use App\Models\StateModel;
use CodeIgniter\I18n\Time;

class CashlessFormViewService
{
    private BeneficiaryV2SnapshotService $snapshotService;

    public function __construct(?BeneficiaryV2SnapshotService $snapshotService = null)
    {
        $this->snapshotService = $snapshotService ?? new BeneficiaryV2SnapshotService();
    }

    public function buildViewData(array $pageInfo, ?int $beneficiaryV2Id, ?int $legacyBeneficiaryId): array
    {
        $viewData = $this->baseViewData($pageInfo);

        if ($beneficiaryV2Id > 0) {
            $record = $this->snapshotService->findByBeneficiaryId($beneficiaryV2Id);
            if ($record !== null) {
                return array_merge($viewData, $this->prepareV2ViewData($record));
            }
        }

        if ($legacyBeneficiaryId > 0) {
            $legacyData = $this->prepareLegacyViewData($legacyBeneficiaryId);
            if ($legacyData !== null) {
                $viewData = array_merge($viewData, $legacyData);
            }
        }

        return $viewData;
    }

    private function baseViewData(array $pageinfo): array
    {
        return [
            'pageinfo'               => $pageinfo,
            'beneficiary'            => null,
            'detailSections'         => [],
            'dependentsCovered'      => [],
            'dependentsNotDependent' => [],
            'dependentsOverview'     => [],
            'message'                => 'No form found for your account.',
            'lastUpdated'            => null,
            'lastUpdatedHuman'       => null,
        ];
    }

    private function prepareV2ViewData(array $record): array
    {
        $address = trim((string) ($record['correspondence_address'] ?? ''));
        if ($address !== '') {
            $address = preg_replace('/\s*\r?\n\s*/', ', ', $address);
        }

        $categoryLabel      = $record['category_label'] ?? null;
        $categoryNormalized = strtolower(trim((string) $categoryLabel));

        $personalDetails = [
            'First Name' => $record['first_name'] ?? null,
        ];

        if (trim((string) ($record['middle_name'] ?? '')) !== '') {
            $personalDetails['Middle Name'] = $record['middle_name'];
        }

        $personalDetails['Last Name']     = $record['last_name'] ?? null;
        $personalDetails['Gender']        = $this->humanizeGender($record['gender'] ?? null);
        $personalDetails['Blood Group']   = $record['blood_group_label'] ?? null;
        $personalDetails['Date of Birth'] = $record['date_of_birth'] ?? null;
        $retirementLabel                  = $this->resolveRetirementLabel($record['category_label'] ?? null, $record['deceased_employee_name'] ?? null);
        $personalDetails[$retirementLabel] = $record['retirement_or_death_date'] ?? null;

        if ($this->shouldIncludeDeceasedName($categoryNormalized, $record['deceased_employee_name'] ?? null)) {
            $personalDetails['Deceased Employee Name'] = $record['deceased_employee_name'] ?? null;
        }

        $sections = array_filter([
            $this->formatSection('Enrollment Overview', [
                'Reference Number'    => $record['reference_number'] ?? null,
                'Legacy Reference'    => $record['legacy_reference'] ?? null,
                'Category'            => $record['category_label'] ?? null,
                'Scheme Option'       => $record['plan_option_label'] ?? null,
                'Submission Recorded' => $record['updated_at'] ?? $record['created_at'] ?? null,
            ], dateTimeFields: ['Submission Recorded']),
            $this->formatSection('Personal Details', $personalDetails, ['Date of Birth', $retirementLabel]),
            $this->formatSection('Service & Office', [
                'Regional Account Office' => $record['rao_label'] ?? $record['rao_other'] ?? null,
                'Office at Retirement'    => $record['retirement_office_label'] ?? $record['retirement_office_other'] ?? null,
                'Designation'             => $record['designation_label'] ?? $record['designation_other'] ?? null,
            ]),
            $this->formatSection('Contact & Address', [
                'Correspondence Address' => $address,
                'City'                   => $record['city'] ?? null,
                'State'                  => $record['state_name'] ?? null,
                'Postal Code'            => $record['postal_code'] ?? null,
                'Primary Mobile'         => $record['primary_mobile_masked'] ?? null,
                'Alternate Mobile'       => $record['alternate_mobile_masked'] ?? null,
                'Email Address'          => $record['email'] ?? null,
            ]),
            $this->formatSection('Bank & Pension', [
                'Bank (Source)'      => $record['bank_source_label'] ?? $record['bank_source_other'] ?? null,
                'Bank (Servicing)'   => $record['bank_servicing_label'] ?? $record['bank_servicing_other'] ?? null,
                'Account Number'     => $record['bank_account_masked'] ?? null,
                'PPO Number'         => $record['ppo_number_masked'] ?? null,
                'PRAN Number'        => $record['pran_number_masked'] ?? null,
                'GPF Number'         => $record['gpf_number_masked'] ?? null,
            ]),
            $this->formatSection('Identifiers', [
                'Aadhaar'        => $record['aadhaar_masked'] ?? null,
                'PAN'            => $record['pan_masked'] ?? null,
                'Samagra ID'     => $record['samagra_masked'] ?? null,
            ]),
        ]);

        $covered      = [];
        $notDependent = [];

        $dependents = $record['dependents'] ?? [];
        if (is_array($dependents)) {
            $position = 1;
            foreach ($dependents as $dependent) {
                $healthRaw = strtolower((string) ($dependent['health_status_raw'] ?? ''));
                $aliveRaw  = strtolower((string) ($dependent['alive_status_raw'] ?? ''));

                $row = [
                    'order'         => $dependent['dependant_order'] ?? $position,
                    'name'          => format_display_value($dependent['full_name'] ?? null),
                    'relation'      => $this->humanizeRelationship($dependent['relationship_key'] ?? null),
                    'status'        => $this->humanizeAliveStatus($aliveRaw),
                    'health_label'  => $this->healthLabel($healthRaw),
                    'gender'        => $this->humanizeGender($dependent['gender'] ?? null),
                    'blood_group'   => format_display_value($dependent['blood_group_label'] ?? null),
                    'date_of_birth' => format_display_value(format_date_for_display($dependent['date_of_birth'] ?? null)),
                    'aadhar_number' => format_display_value($dependent['aadhaar_masked'] ?? null),
                ];

                if ($healthRaw === 'yes' && $aliveRaw === 'alive') {
                    $covered[] = $row;
                } elseif ($aliveRaw === 'alive') {
                    $notDependent[] = $row;
                }

                $position++;
            }
        }

        $lastUpdated = $record['updated_at'] ?? $record['created_at'] ?? null;
        $lastHuman   = $lastUpdated ? Time::parse($lastUpdated)->humanize() : null;

        return [
            'beneficiary'            => $record,
            'detailSections'         => $sections,
            'dependentsCovered'      => $covered,
            'dependentsNotDependent' => $notDependent,
            'dependentsOverview'     => array_merge($covered, $notDependent),
            'message'                => null,
            'lastUpdated'            => $lastUpdated,
            'lastUpdatedHuman'       => $lastHuman,
        ];
    }

    private function prepareLegacyViewData(int $beneficiaryId): ?array
    {
        $beneficiaries   = new BeneficiaryModel();
        $dependentsModel = new DependentModel();
        $residenceModel  = new BeneficiaryResidenceModel();
        $cityModel       = new CityModel();
        $stateModel      = new StateModel();

        $beneficiary = $beneficiaries->find($beneficiaryId);
        if (! $beneficiary) {
            return null;
        }

        $residence = $residenceModel->getCurrentResidence($beneficiaryId);
        $city      = null;
        $state     = null;

        if ($residence && ! empty($residence['city_id'])) {
            $city  = $cityModel->find((int) $residence['city_id']);
            $state = $city && isset($city['state_id'])
                ? $stateModel->find((int) $city['state_id'])
                : null;
        }

        $addressParts = [];
        if ($residence) {
            $addressParts[] = $residence['address_line1'] ?? null;
            $addressParts[] = $residence['address_line2'] ?? null;
        }

        $address = trim(implode(', ', array_filter(array_map('trim', $addressParts))));

        $cityName  = $city['city_name'] ?? null;
        $stateName = $state['state_name'] ?? null;

        $categoryLegacy = strtolower(trim((string) ($beneficiary['category'] ?? '')));

        $personalLegacy = [
            'First Name' => $beneficiary['first_name'] ?? null,
        ];

        if (trim((string) ($beneficiary['middle_name'] ?? '')) !== '') {
            $personalLegacy['Middle Name'] = $beneficiary['middle_name'];
        }

        $personalLegacy['Last Name'] = $beneficiary['last_name'] ?? null;
        $personalLegacy['Gender']    = $this->humanizeGender($beneficiary['gender'] ?? null);
        $personalLegacy['Date of Birth'] = $beneficiary['date_of_birth'] ?? null;
        $legacyRetirementLabel = $this->resolveRetirementLabel($beneficiary['category'] ?? null, $beneficiary['deceased_employee_name'] ?? null);
        $personalLegacy[$legacyRetirementLabel] = $beneficiary['retirement_date'] ?? null;

        if ($this->shouldIncludeDeceasedName($categoryLegacy, $beneficiary['deceased_employee_name'] ?? null)) {
            $personalLegacy['Deceased Employee Name'] = $beneficiary['deceased_employee_name'] ?? null;
        }

        $sections = array_filter([
            $this->formatSection('Enrollment Overview', [
                'Reference Number' => $beneficiary['unique_ref_number'] ?? null,
                'Category'         => $beneficiary['category'] ?? null,
                'Scheme Option'    => $beneficiary['scheme_option'] ?? null,
            ]),
            $this->formatSection('Personal Details', $personalLegacy, ['Date of Birth', $legacyRetirementLabel]),
            $this->formatSection('Service & Office', [
                'Regional Account Office' => $beneficiary['rao'] ?? $beneficiary['raw_rao'] ?? null,
                'Office at Retirement'    => $beneficiary['office_at_retirement'] ?? $beneficiary['raw_office_at_retirement'] ?? null,
                'Designation'             => $beneficiary['designation'] ?? $beneficiary['raw_designation'] ?? null,
            ]),
            $this->formatSection('Contact & Address', [
                'Correspondence Address'  => $address,
                'City'                    => $cityName,
                'State'                   => $stateName,
                'Postal Code'             => $residence['postal_code'] ?? null,
                'Primary Mobile'          => $beneficiary['mobile_number'] ?? null,
                'Alternate Mobile'        => $beneficiary['alternate_mobile'] ?? null,
                'Email Address'           => $beneficiary['email'] ?? null,
            ]),
            $this->formatSection('Bank & Pension', [
                'Bank Source'           => $beneficiary['bank_source'] ?? null,
                'Bank Name'             => $beneficiary['bank_name'] ?? null,
                'Branch'                => $beneficiary['bank_branch'] ?? null,
                'IFSC Code'             => $beneficiary['ifsc_code'] ?? null,
                'Account Number'        => $beneficiary['account_number_masked'] ?? $beneficiary['account_number'] ?? null,
                'Pension Paying Office' => $beneficiary['pension_paying_office'] ?? null,
                'PPO / PRAN Number'     => $beneficiary['ppo_pran_number'] ?? null,
            ]),
            $this->formatSection('Nominee / Authorization', [
                'Nominee Name'        => $beneficiary['nominee_name'] ?? null,
                'Nominee Relation'    => $beneficiary['nominee_relation'] ?? null,
                'Nominee Contact'     => $beneficiary['nominee_contact_number'] ?? null,
                'Nominee Address'     => $beneficiary['nominee_address'] ?? null,
                'Authorized Person'   => $beneficiary['authorized_person_name'] ?? null,
                'Authorized Relation' => $beneficiary['authorized_person_relation'] ?? null,
                'Authorized Contact'  => $beneficiary['authorized_person_contact_number'] ?? null,
            ]),
        ]);

        $dependents = $dependentsModel
            ->where('beneficiary_id', $beneficiaryId)
            ->orderBy('relation')
            ->findAll();

        $covered      = [];
        $notDependent = [];

        foreach ($dependents as $dependent) {
            $row = $this->withLegacyHealthLabel([
                'name'          => format_display_value($dependent['name'] ?? null),
                'relation'      => $this->humanizeRelationship($dependent['relation'] ?? null),
                'status'        => ucwords((string) ($dependent['status'] ?? '')),
                'health_status' => null,
                'blood_group'   => format_display_value($dependent['blood_group'] ?? null),
                'date_of_birth' => format_display_value(format_date_for_display($dependent['dob'] ?? null)),
                'aadhar_number' => format_display_value($dependent['aadhaar'] ?? null),
                'is_dependent_for_health' => $dependent['is_dependent_for_health'] ?? null,
            ]);

            $statusRaw = strtolower((string) ($dependent['status'] ?? ''));
            $isDependentForHealth = ! empty($dependent['is_dependent_for_health']);

            if ($isDependentForHealth && $statusRaw === 'alive') {
                $covered[] = $row;
            } elseif ($statusRaw === 'alive') {
                $notDependent[] = $row;
            }
        }

        $lastUpdated = $beneficiary['updated_at'] ?? $beneficiary['created_at'] ?? null;
        $lastHuman   = $lastUpdated ? Time::parse($lastUpdated)->humanize() : null;

        return [
            'beneficiary'            => $beneficiary,
            'detailSections'         => $sections,
            'dependentsCovered'      => $covered,
            'dependentsNotDependent' => $notDependent,
            'dependentsOverview'     => array_merge($covered, $notDependent),
            'message'                => null,
            'lastUpdated'            => $lastUpdated,
            'lastUpdatedHuman'       => $lastHuman,
        ];
    }

    private function formatSection(string $title, array $map, array $dateFields = [], array $dateTimeFields = []): ?array
    {
        if (! $this->sectionHasData($map)) {
            return null;
        }

        return [
            'title' => $title,
            'rows'  => $this->buildDetailRows($map, $dateFields, $dateTimeFields),
        ];
    }

    private function sectionHasData(array $map): bool
    {
        foreach ($map as $value) {
            if ($value instanceof Time) {
                return true;
            }

            if (is_array($value)) {
                $value = implode('', array_filter(array_map('trim', $value)));
            }

            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function buildDetailRows(array $map, array $dateFields = [], array $dateTimeFields = []): array
    {
        $rows = [];

        foreach ($map as $label => $value) {
            if (in_array($label, $dateFields, true)) {
                $value = format_date_for_display($value);
            }

            if (in_array($label, $dateTimeFields, true)) {
                $value = format_date_for_display($value, true);
            }

            $rows[] = [
                'label' => $label,
                'value' => format_display_value($value),
            ];
        }

        return $rows;
    }

    private function resolveRetirementLabel(?string $categoryLabel, ?string $deceasedName): string
    {
        $category        = strtolower(trim((string) ($categoryLabel ?? '')));
        $hasDeceasedName = trim((string) ($deceasedName ?? '')) !== '';

        $needles         = ['family', 'widow', 'widower', 'death', 'nps'];
        $isFamilyContext = $hasDeceasedName;
        if ($category !== '') {
            foreach ($needles as $needle) {
                if (str_contains($category, $needle)) {
                    $isFamilyContext = true;
                    break;
                }
            }
        }

        return $isFamilyContext ? 'Death Date' : 'Retirement Date';
    }

    private function shouldIncludeDeceasedName(?string $category, ?string $name): bool
    {
        if (trim((string) ($name ?? '')) !== '') {
            return true;
        }

        $normalized = strtolower(trim((string) ($category ?? '')));

        return ! in_array($normalized, ['pensioner', 'nps pensioner'], true);
    }

    private function humanizeRelationship(?string $value): string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        if ($value === '') {
            return 'Not specified';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    private function humanizeAliveStatus(?string $value): string
    {
        return match (strtolower((string) ($value ?? ''))) {
            'alive'          => 'Alive',
            'not_alive'      => 'Not Alive',
            'not_applicable' => 'Not Applicable',
            default          => 'Unknown',
        };
    }

    private function healthLabel(?string $value): string
    {
        return match (strtolower((string) ($value ?? ''))) {
            'yes'            => 'Yes',
            'no'             => 'No',
            'not_applicable' => 'Not Applicable',
            default          => 'Unknown',
        };
    }

    private function withLegacyHealthLabel(array $row): array
    {
        $status = strtolower((string) ($row['status'] ?? ''));

        if ($status === 'not applicable') {
            $row['health_label'] = 'Not Applicable';
        } elseif (! empty($row['is_dependent_for_health'])) {
            $row['health_label'] = 'Yes';
        } elseif ($status === 'not provided') {
            $row['health_label'] = 'Not Provided';
        } else {
            $row['health_label'] = 'No';
        }

        return $row;
    }

    private function humanizeGender(?string $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        if ($value === '') {
            return null;
        }

        return match ($value) {
            'male'        => 'Male',
            'female'      => 'Female',
            'transgender' => 'Transgender',
            default       => ucwords($value),
        };
    }
}


