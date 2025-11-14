<?php

namespace App\Services;

use CodeIgniter\I18n\Time;

class BeneficiaryDashboardAssembler
{
    public function assemble(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }

        [$profileCompletion, $beneficiaryMessages] = $this->computeProfileStatus($record);
        [$coveredDependents, $otherDependents]      = $this->groupDependents($record['dependents'] ?? []);

        $actionCenter           = $this->buildActionCenter($coveredDependents, $otherDependents, $beneficiaryMessages);
        $beneficiaryIdentifiers = $this->buildBeneficiaryIdentifiers($record);
        $dependentIdentifiers   = $this->buildDependentIdentifiers($coveredDependents, $otherDependents);

        $lastUpdated     = $record['updated_at'] ?? $record['created_at'] ?? null;
        $lastUpdatedTime = $lastUpdated ? Time::parse($lastUpdated) : null;

        return [
            'beneficiary'            => $record,
            'planOption'             => $record['plan_option_label'] ?? null,
            'category'               => $record['category_label'] ?? null,
            'stateName'              => $record['state_name'] ?? null,
            'address'                => $this->formatAddress($record),
            'contact'                => [
                'mobile'    => $record['primary_mobile_masked'] ?? null,
                'alternate' => $record['alternate_mobile_masked'] ?? null,
                'email'     => $record['email'] ?? null,
            ],
            'bank'                   => [
                'source'        => $record['bank_source_label'] ?? $record['bank_source_other'] ?? null,
                'servicing'     => $record['bank_servicing_label'] ?? $record['bank_servicing_other'] ?? null,
                'accountMasked' => $record['bank_account_masked'] ?? null,
            ],
            'beneficiaryIdentifiers' => $beneficiaryIdentifiers,
            'dependentIdentifiers'   => $dependentIdentifiers,
            'dependentsCovered'      => $coveredDependents,
            'dependentsOthers'       => $otherDependents,
            'profileCompletion'      => $profileCompletion,
            'missingMessages'        => $beneficiaryMessages,
            'actionCenter'           => $actionCenter,
            'dependentsCount'        => count($coveredDependents),
            'lastUpdated'            => $lastUpdated,
            'lastUpdatedHuman'       => $lastUpdatedTime ? $lastUpdatedTime->humanize() : null,
        ];
    }

    private function formatAddress(array $record): array
    {
        $address = trim((string) ($record['correspondence_address'] ?? ''));
        $city    = trim((string) ($record['city'] ?? ''));
        $state   = trim((string) ($record['state_name'] ?? ''));
        $postal  = trim((string) ($record['postal_code'] ?? ''));

        $lines = $address !== '' ? preg_split('/\r?\n/', $address) : [];
        if ($city !== '') {
            $lines[] = $city;
        }
        if ($state !== '' || $postal !== '') {
            $lines[] = trim($state . ' ' . $postal);
        }

        return array_values(array_filter($lines, static fn ($line) => trim((string) $line) !== ''));
    }

    private function computeProfileStatus(array $record): array
    {
        $fields = [
            'plan_option_label'      => 'Scheme option not selected.',
            'primary_mobile_masked'  => 'Primary mobile number not provided.',
            'email'                  => 'Email address not provided.',
            'bank_account_masked'    => 'Bank account number not provided.',
            'correspondence_address' => 'Correspondence address not provided.',
            'city'                   => 'City not provided.',
            'state_name'             => 'State not provided.',
        ];

        $filled  = 0;
        $missing = [];

        foreach ($fields as $field => $message) {
            $value = $record[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            if (! empty($value)) {
                $filled++;
            } else {
                $missing[] = $message;
            }
        }

        $total   = count($fields);
        $percent = $total > 0 ? (int) round(($filled / $total) * 100) : 0;

        return [$percent, $missing];
    }

    private function groupDependents(array $rows): array
    {
        $covered = [];
        $others  = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $isDependent = strtolower((string) ($row['is_health_dependant'] ?? '')) === 'yes';

            $item = [
                'name'         => $row['full_name'] ?? $row['first_name'] ?? null,
                'relationship' => $this->humanizeRelationship($row['relationship'] ?? ''),
                'isAlive'      => $this->humanizeStatus($row['is_alive'] ?? ''),
                'isHealthDependant' => $isDependent,
                'bloodGroup'   => $row['blood_group_label'] ?? null,
                'dateOfBirth'  => $row['date_of_birth'] ?? null,
                'aadhaar'      => $row['aadhaar_masked'] ?? null,
            ];

            if ($isDependent) {
                $covered[] = $item;
            } else {
                $others[] = $item;
            }
        }

        return [$covered, $others];
    }

    private function buildActionCenter(array $covered, array $others, array $beneficiaryMessages): array
    {
        $payload = [
            'beneficiary' => $beneficiaryMessages,
            'dependents'  => [],
        ];

        foreach (array_merge($covered, $others) as $dependent) {
            if (($dependent['isHealthDependant'] ?? false) !== true) {
                continue;
            }

            if (strtolower((string) ($dependent['isAlive'] ?? '')) !== 'alive') {
                continue;
            }

            $missing = [];
            if (trim((string) ($dependent['bloodGroup'] ?? '')) === '') {
                $missing[] = 'Blood group not provided.';
            }
            if (trim((string) ($dependent['dateOfBirth'] ?? '')) === '') {
                $missing[] = 'Date of birth not provided.';
            }
            if (trim((string) ($dependent['aadhaar'] ?? '')) === '') {
                $missing[] = 'Aadhaar number not provided.';
            }

            if ($missing === []) {
                continue;
            }

            $payload['dependents'][] = [
                'label' => $this->dependentLabel($dependent),
                'items' => $missing,
            ];
        }

        return $payload;
    }

    private function buildBeneficiaryIdentifiers(array $record): array
    {
        return [
            'Aadhaar' => $this->formatIdentifierValue($record['aadhaar_masked'] ?? null),
            'PAN'     => $this->formatIdentifierValue($record['pan_masked'] ?? null),
            'PPO'     => $this->formatIdentifierValue($record['ppo_number_masked'] ?? null),
            'GPF'     => $this->formatIdentifierValue($record['gpf_number_masked'] ?? null),
            'PRAN'    => $this->formatIdentifierValue($record['pran_number_masked'] ?? null),
            'Samagra' => $this->formatIdentifierValue($record['samagra_masked'] ?? null),
        ];
    }

    private function buildDependentIdentifiers(array $covered, array $others): array
    {
        $identifiers = [];
        foreach (array_merge($covered, $others) as $dependent) {
            $fields = [];
            $aadhaar = trim((string) ($dependent['aadhaar'] ?? ''));
            if ($aadhaar !== '') {
                $fields['Aadhaar'] = $aadhaar;
            }

            if ($fields === []) {
                continue;
            }

            $identifiers[] = [
                'label'  => $this->dependentLabel($dependent),
                'fields' => $fields,
            ];
        }

        return $identifiers;
    }

    private function formatIdentifierValue(?string $value): string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? 'Not provided' : $value;
    }

    private function dependentLabel(array $dependent): string
    {
        $name     = trim((string) ($dependent['name'] ?? ''));
        $relation = trim((string) ($dependent['relationship'] ?? ''));

        if ($name !== '' && $relation !== '') {
            return sprintf('%s (%s)', $name, $relation);
        }

        return $name !== '' ? $name : ($relation !== '' ? $relation : 'Family member');
    }

    private function humanizeRelationship(string $relationship): string
    {
        if ($relationship === '') {
            return 'Unknown';
        }

        return ucwords(str_replace('_', ' ', strtolower($relationship)));
    }

    private function humanizeStatus(string $status): string
    {
        return match (strtolower($status)) {
            'alive'          => 'Alive',
            'not_alive'      => 'Not Alive',
            'not_applicable' => 'Not Applicable',
            default          => 'Unknown',
        };
    }
}
