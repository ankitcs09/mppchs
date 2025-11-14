<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Config\Database;
use RuntimeException;

class BeneficiaryExportService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly SensitiveDataService $sensitiveDataService
    ) {
    }

    public static function create(): self
    {
        return new self(Database::connect(), service('sensitiveData'));
    }

    public function export(array $filters = []): array
    {
        $limit  = $this->normalizeLimit($filters['limit'] ?? 100);
        $page   = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $builder = $this->baseBuilder();
        $this->applyFilters($builder, $filters);
        $total = (clone $builder)->select('COUNT(*) AS total')->get()->getRow('total') ?? 0;

        $rows = $builder
            ->orderBy('b.id', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [
                'data' => [],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $limit,
                    'total' => 0,
                    'pages' => 0,
                ],
            ];
        }

        $beneficiaryIds = array_column($rows, 'id');
        $dependents = $this->fetchDependents($beneficiaryIds);

        $companyCode = $this->sanitizeCompanyCode($filters['company_code'] ?? null);

        $exportRows = [];
        foreach ($rows as $row) {
            $beneficiaryExport = $this->formatBeneficiaryRow($row, $companyCode);
            $exportRows[] = $beneficiaryExport;

            $deps = $dependents[$row['id']] ?? [];
            foreach ($deps as $dependent) {
                $exportRows[] = $this->formatDependentRow($row, $dependent, $companyCode);
            }
        }

        return [
            'data' => $exportRows,
            'pagination' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => (int) $total,
                'pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ];
    }

    private function baseBuilder()
    {
        return $this->db->table('beneficiaries_v2 b')
            ->select([
                'b.id',
                'b.reference_number',
                'b.plan_option_id',
                'b.category_id',
                'b.first_name',
                'b.middle_name',
                'b.last_name',
                'b.gender',
                'b.date_of_birth',
                'b.correspondence_address',
                'b.city',
                'b.state_id',
                'b.postal_code',
                'b.primary_mobile_enc',
                'b.primary_mobile_masked',
                'b.alternate_mobile_enc',
                'b.alternate_mobile_masked',
                'b.email',
                'b.updated_at',
                'b.created_at',
                'b.rao_id',
                'b.rao_other',
                'plan_options.label AS plan_option_label',
                'beneficiary_categories.label AS category_label',
                'states.state_name AS state_name',
                'regional_account_offices.name AS rao_name',
            ])
            ->join('plan_options', 'plan_options.id = b.plan_option_id', 'left')
            ->join('beneficiary_categories', 'beneficiary_categories.id = b.category_id', 'left')
            ->join('states', 'states.state_id = b.state_id', 'left')
            ->join('regional_account_offices', 'regional_account_offices.id = b.rao_id', 'left');
    }

    private function applyFilters($builder, array $filters): void
    {
        if (! empty($filters['updated_after'])) {
            $date = $this->parseDateTime($filters['updated_after']);
            if ($date === null) {
                throw new RuntimeException('Invalid updated_after filter.');
            }
            $builder->where('b.updated_at >=', $date);
        }

        if (! empty($filters['reference'])) {
            $builder->whereIn('b.reference_number', (array) $filters['reference']);
        }
    }

    private function fetchDependents(array $beneficiaryIds): array
    {
        if ($beneficiaryIds === []) {
            return [];
        }

        $rows = $this->db->table('beneficiary_dependents_v2 d')
            ->select([
                'd.id',
                'd.beneficiary_id',
                'd.relationship',
                'd.first_name',
                'd.gender',
                'd.date_of_birth',
                'd.is_alive',
                'd.is_health_dependant',
            ])
            ->whereIn('d.beneficiary_id', $beneficiaryIds)
            ->where('d.is_active', 1)
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['beneficiary_id']][] = $row;
        }

        return $grouped;
    }

    private function formatBeneficiaryRow(array $row, string $companyCode): array
    {
        $fullName = trim(implode(' ', array_filter([$row['first_name'], $row['middle_name'], $row['last_name']])));

        return [
            'company_id'        => $companyCode,
            'reference_number'  => $row['reference_number'],
            'relation'          => 'Self',
            'plan_option'       => $row['plan_option_label'] ?? '',
            'category'          => $row['category_label'] ?? '',
            'name'              => $fullName,
            'gender'            => strtoupper((string) $row['gender']),
            'date_of_birth'     => $this->formatDate($row['date_of_birth']),
            'rao'               => $row['rao_name'] ?? $row['rao_other'] ?? '',
            'address'           => $row['correspondence_address'] ?? '',
            'city'              => $row['city'] ?? '',
            'state'             => $row['state_name'] ?? '',
            'postal_code'       => $row['postal_code'] ?? '',
            'mobile'            => $this->decryptOrMasked($row['primary_mobile_enc'] ?? null, $row['primary_mobile_masked'] ?? null),
            'alternate_mobile'  => $this->decryptOrMasked($row['alternate_mobile_enc'] ?? null, $row['alternate_mobile_masked'] ?? null),
            'email'             => $row['email'] ?? '',
            'alive_status'      => $this->inferBeneficiaryAliveStatus($row),
            'health_dependant'  => 'YES',
        ];
    }

    private function formatDependentRow(array $beneficiary, array $dependent, string $companyCode): array
    {
        $name = trim($dependent['first_name'] ?? '');
        $relation = $this->formatRelation($dependent['relationship'] ?? '');

        $aliveStatus = $this->formatAliveStatus($dependent['is_alive'] ?? '');
        $health = $this->formatHealthDependancy($dependent['is_health_dependant'] ?? '');

        return [
            'company_id'        => $companyCode,
            'reference_number'  => $beneficiary['reference_number'],
            'relation'          => $relation,
            'plan_option'       => $beneficiary['plan_option_label'] ?? '',
            'category'          => $beneficiary['category_label'] ?? '',
            'name'              => $name,
            'gender'            => strtoupper((string) ($dependent['gender'] ?? '')),
            'date_of_birth'     => $this->formatDate($dependent['date_of_birth'] ?? null),
            'rao'               => $beneficiary['rao_name'] ?? $beneficiary['rao_other'] ?? '',
            'address'           => $beneficiary['correspondence_address'] ?? '',
            'city'              => $beneficiary['city'] ?? '',
            'state'             => $beneficiary['state_name'] ?? '',
            'postal_code'       => $beneficiary['postal_code'] ?? '',
            'mobile'            => $this->decryptOrMasked($beneficiary['primary_mobile_enc'] ?? null, $beneficiary['primary_mobile_masked'] ?? null),
            'alternate_mobile'  => $this->decryptOrMasked($beneficiary['alternate_mobile_enc'] ?? null, $beneficiary['alternate_mobile_masked'] ?? null),
            'email'             => $beneficiary['email'] ?? '',
            'alive_status'      => $aliveStatus,
            'health_dependant'  => $health,
        ];
    }

    private function decryptOrMasked(?string $encrypted, ?string $masked): string
    {
        if ($encrypted) {
            try {
                $value = $this->sensitiveDataService->decrypt($encrypted);
                if ($value) {
                    return $value;
                }
            } catch (\Throwable $exception) {
                // fall back to masked
            }
        }

        return $masked ?? '';
    }

    private function formatDate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if (! $timestamp) {
            return '';
        }

        return date('d-M-Y', $timestamp);
    }

    private function formatRelation(string $relation): string
    {
        $relation = strtolower($relation);
        return match ($relation) {
            'spouse'     => 'Spouse',
            'daughter'   => 'Daughter',
            'son'        => 'Son',
            'father'     => 'Father',
            'mother'     => 'Mother',
            'other'      => 'Other',
            default      => ucfirst($relation),
        };
    }

    private function formatAliveStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'alive'         => 'ALIVE',
            'not_alive'     => 'NOT ALIVE',
            'not_applicable'=> 'NOT APPLICABLE',
            default         => 'ALIVE',
        };
    }

    private function formatHealthDependancy(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return match ($value) {
            'yes'            => 'YES',
            'no'             => 'NO',
            'not_applicable' => 'NOT APPLICABLE',
            default          => 'YES',
        };
    }

    private function inferBeneficiaryAliveStatus(array $beneficiary): string
    {
        $category = strtolower((string) ($beneficiary['category_label'] ?? ''));
        if (str_contains($category, 'family')) {
            return 'NOT ALIVE';
        }

        return 'ALIVE';
    }

    private function parseDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);
        if (! $timestamp) {
            return null;
        }

        try {
            return Time::createFromTimestamp((int) $timestamp, 'UTC')->toDateTimeString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function normalizeLimit(mixed $limit): int
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 100;
        }

        return min($limit, 1000);
    }

    private function sanitizeCompanyCode(mixed $company): string
    {
        $company = strtoupper(trim((string) $company));
        return $company !== '' ? $company : 'MPPGCL';
    }
}
