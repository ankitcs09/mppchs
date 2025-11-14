<?php

namespace App\Services\Claims;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;
use Config\Claims as ClaimsConfig;
use Config\Database;

class ClaimRepositoryService
{
    private ConnectionInterface $db;
    private ClaimsConfig $config;

    private ?array $statusOptions = null;
    private ?array $typeOptions = null;
    private ?array $documentTypeOptions = null;

    public function __construct(?ConnectionInterface $connection = null, ?ClaimsConfig $config = null)
    {
        $this->db     = $connection ?? Database::connect();
        $this->config = $config ?? config('Claims');
    }

    /**
     * Returns cached list of claim statuses.
     */
    public function getStatusOptions(): array
    {
        if ($this->statusOptions !== null) {
            return $this->statusOptions;
        }

        $builder = $this->db->table('claim_statuses')
            ->select('id, code, label, description, is_terminal')
            ->orderBy('display_order', 'ASC')
            ->orderBy('label', 'ASC');

        $this->statusOptions = $builder->get()->getResultArray();

        return $this->statusOptions;
    }

    /**
     * Returns cached list of claim types.
     */
    public function getTypeOptions(): array
    {
        if ($this->typeOptions !== null) {
            return $this->typeOptions;
        }

        $builder = $this->db->table('claim_types')
            ->select('id, code, label, description')
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->orderBy('label', 'ASC');

        $this->typeOptions = $builder->get()->getResultArray();

        return $this->typeOptions;
    }

    /**
     * Returns cached list of document type metadata.
     */
    public function getDocumentTypeOptions(): array
    {
        if ($this->documentTypeOptions !== null) {
            return $this->documentTypeOptions;
        }

        $builder = $this->db->table('claim_document_types')
            ->select('id, code, label, description')
            ->orderBy('label', 'ASC');

        $this->documentTypeOptions = $builder->get()->getResultArray();

        return $this->documentTypeOptions;
    }

    /**
     * Lists claims for a beneficiary with pagination and summary metrics.
     */
    public function listBeneficiaryClaims(int $beneficiaryId, array $filters = [], int $page = 1, ?int $perPage = null): array
    {
        $normalized = $this->normalizeFilters($filters);
        $perPage    = $perPage ?? $this->config->beneficiaryPageSize;
        $perPage    = max(1, $perPage);
        $page       = max(1, $page);
        $offset     = ($page - 1) * $perPage;

        $countBuilder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $countBuilder->where('c.beneficiary_id', $beneficiaryId);
        $this->applyFilters($countBuilder, $normalized, true);
        $total = (int) $countBuilder->countAllResults();

        $dataBuilder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $dataBuilder->where('c.beneficiary_id', $beneficiaryId);
        $this->applyFilters($dataBuilder, $normalized, true);
        $dataBuilder->orderBy('c.claim_date', 'DESC');
        $dataBuilder->orderBy('c.id', 'DESC');
        $rows = $dataBuilder->get($perPage, $offset)->getResultArray();

        $claims = array_map(fn (array $row) => $this->formatClaimRow($row), $rows);

        return [
            'data'       => $claims,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'perPage'  => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ],
            'summary'    => $this->buildBeneficiarySummary($beneficiaryId, $normalized),
        ];
    }

    /**
     * Lists claims for admin users with pagination, totals and breakdowns.
     */
    public function listAdminClaims(array $filters = [], ?array $companyScope = null, int $page = 1, ?int $perPage = null): array
    {
        $normalized = $this->normalizeFilters($filters, $companyScope);
        $perPage    = $perPage ?? $this->config->adminPageSize;
        $perPage    = max(1, $perPage);
        $page       = max(1, $page);
        $offset     = ($page - 1) * $perPage;

        $countBuilder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $this->applyFilters($countBuilder, $normalized, true);
        $total = (int) $countBuilder->countAllResults();

        $dataBuilder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $this->applyFilters($dataBuilder, $normalized, true);
        $dataBuilder->orderBy('c.claim_date', 'DESC');
        $dataBuilder->orderBy('c.id', 'DESC');
        $rows = $dataBuilder->get($perPage, $offset)->getResultArray();

        $claims = array_map(fn (array $row) => $this->formatClaimRow($row), $rows);

        return [
            'data'       => $claims,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'perPage'  => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ],
            'summary'    => $this->buildAdminSummary($normalized),
        ];
    }

    /**
     * Fetches a beneficiary claim ensuring ownership.
     */
    public function findBeneficiaryClaim(int $claimId, int $beneficiaryId): ?array
    {
        $builder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $builder->where('c.id', $claimId);
        $builder->where('c.beneficiary_id', $beneficiaryId);

        $row = $builder->get()->getRowArray();
        if (! $row) {
            return null;
        }

        $claim = $this->formatClaimRow($row);
        $claim['events']    = $this->fetchClaimEvents($claimId);
        $claim['documents'] = $this->fetchClaimDocuments($claimId);

        return $claim;
    }

    /**
     * Fetches a claim for privileged users while enforcing company scope.
     */
    public function findAdminClaim(int $claimId, ?array $companyScope = null): ?array
    {
        $builder = $this->prepareClaimQuery(includeCompany: true, includeBeneficiary: true);
        $builder->where('c.id', $claimId);

        $normalized = $this->normalizeFilters(['id_only' => true], $companyScope);
        $this->applyFilters($builder, $normalized, true);

        $row = $builder->get()->getRowArray();
        if (! $row) {
            return null;
        }

        $claim = $this->formatClaimRow($row);
        $claim['events']    = $this->fetchClaimEvents($claimId);
        $claim['documents'] = $this->fetchClaimDocuments($claimId);

        return $claim;
    }

    private function prepareClaimQuery(bool $includeCompany = false, bool $includeBeneficiary = false): BaseBuilder
    {
        $builder = $this->db->table('claims c')
            ->select([
                'c.*',
                'cs.code AS status_code',
                'cs.label AS status_label',
                'cs.is_terminal AS status_is_terminal',
                'ct.code AS claim_type_code',
                'ct.label AS claim_type_label',
                'bp.policy_number AS policy_number',
                'bp.card_number AS policy_card_number',
                'bp.policy_program AS policy_program',
                'bp.policy_provider AS policy_provider',
                'bd.first_name AS dependent_first_name',
                'bd.relationship AS dependent_relationship',
            ])
            ->join('claim_statuses cs', 'cs.id = c.status_id', 'left')
            ->join('claim_types ct', 'ct.id = c.claim_type_id', 'left')
            ->join('beneficiary_policy_cards bp', 'bp.id = c.policy_card_id', 'left')
            ->join('beneficiary_dependents_v2 bd', 'bd.id = c.dependent_id', 'left');

        if ($includeCompany) {
            $builder->select([
                'companies.code AS company_code',
                'companies.name AS company_name',
            ]);
            $builder->join('companies', 'companies.id = c.company_id', 'left');
        }

        if ($includeBeneficiary) {
            $builder->select([
                'b.reference_number AS beneficiary_reference_number',
                'b.first_name AS beneficiary_first_name',
                'b.middle_name AS beneficiary_middle_name',
                'b.last_name AS beneficiary_last_name',
                'b.primary_mobile_masked AS beneficiary_mobile_masked',
            ]);
            $builder->join('beneficiaries_v2 b', 'b.id = c.beneficiary_id', 'left');
        }

        return $builder;
    }

    private function applyFilters(BaseBuilder $builder, array $filters, bool $hasBeneficiaryJoin = false): void
    {
        if (! empty($filters['status_codes'])) {
            $builder->whereIn('cs.code', $filters['status_codes']);
        }

        if (! empty($filters['type_codes'])) {
            $builder->whereIn('ct.code', $filters['type_codes']);
        }

        if (! empty($filters['company_ids'])) {
            $builder->whereIn('c.company_id', $filters['company_ids']);
        } elseif (array_key_exists('company_ids', $filters) && $filters['company_ids'] === []) {
            // explicit empty scope should yield no results
            $builder->where('1 = 0');
        }

        if (! empty($filters['claim_reference'])) {
            $builder->where('c.claim_reference', $filters['claim_reference']);
        }

        if (! empty($filters['policy_number'])) {
            $builder->where('bp.policy_number', $filters['policy_number']);
        }

        if (! empty($filters['tpa_reference'])) {
            $builder->where('c.source_reference', $filters['tpa_reference']);
        }

        if (! empty($filters['hospital_code'])) {
            $builder->where('c.hospital_code', $filters['hospital_code']);
        }

        if ($filters['from_date'] !== null) {
            $builder->where('c.claim_date >=', $filters['from_date']);
        }

        if ($filters['to_date'] !== null) {
            $builder->where('c.claim_date <=', $filters['to_date']);
        }

        if ($filters['min_amount'] !== null) {
            $builder->where('c.claimed_amount >=', $filters['min_amount']);
        }

        if ($filters['max_amount'] !== null) {
            $builder->where('c.claimed_amount <=', $filters['max_amount']);
        }

        if (! empty($filters['search_term'])) {
            $term = '%' . $filters['search_term'] . '%';
            $builder->groupStart()
                ->like('c.claim_reference', $term, 'both')
                ->orLike('c.hospital_name', $term, 'both')
                ->orLike('c.diagnosis', $term, 'both')
                ->groupEnd();
        }

        if ($hasBeneficiaryJoin && ! empty($filters['beneficiary_term'])) {
            $term = '%' . $filters['beneficiary_term'] . '%';
            $builder->groupStart()
                ->like('b.reference_number', $filters['beneficiary_term'], 'both')
                ->orLike("CONCAT_WS(' ', b.first_name, b.middle_name, b.last_name)", $term, 'both', false)
                ->groupEnd();
        }
    }

    private function normalizeFilters(array $filters, ?array $companyScope = null): array
    {
        $normalizeList = static function ($value): array {
            if ($value === null) {
                return [];
            }
            if (is_string($value)) {
                $value = preg_split('/[\s,]+/', strtolower(trim($value)));
            }
            if (! is_array($value)) {
                return [];
            }

            $items = [];
            foreach ($value as $entry) {
                $entry = strtolower(trim((string) $entry));
                if ($entry !== '') {
                    $items[] = $entry;
                }
            }

            return array_values(array_unique($items));
        };

        $statusCodes = $normalizeList($filters['status'] ?? $filters['status_codes'] ?? null);
        $typeCodes   = $normalizeList($filters['type'] ?? $filters['type_codes'] ?? null);

        $fromDate = $this->parseDate($filters['from'] ?? $filters['from_date'] ?? null);
        $toDate   = $this->parseDate($filters['to'] ?? $filters['to_date'] ?? null);

        $searchTerm       = $this->sanitizeString($filters['search'] ?? $filters['search_term'] ?? null);
        $beneficiaryTerm  = $this->sanitizeString($filters['beneficiary'] ?? $filters['beneficiary_term'] ?? null);
        $claimReference   = $this->sanitizeString($filters['claim_reference'] ?? null);
        $policyNumber     = $this->sanitizeString($filters['policy_number'] ?? null);
        $hospitalCode     = $this->sanitizeString($filters['hospital_code'] ?? null);
        $tpaReference     = $this->sanitizeString($filters['tpa_reference'] ?? null);

        $minAmount = $this->parseAmount($filters['min_amount'] ?? null);
        $maxAmount = $this->parseAmount($filters['max_amount'] ?? null);

        $companyIds = null;
        if ($companyScope !== null) {
            $companyIds = array_map('intval', $companyScope);
        }
        if (! empty($filters['company_id'])) {
            $filterCompany = (int) $filters['company_id'];
            if ($companyIds === null || in_array($filterCompany, $companyIds, true)) {
                $companyIds = [$filterCompany];
            } elseif ($companyIds !== null && ! in_array($filterCompany, $companyIds, true)) {
                $companyIds = [];
            }
        }

        return [
            'status_codes'    => $statusCodes,
            'type_codes'      => $typeCodes,
            'from_date'       => $fromDate,
            'to_date'         => $toDate,
            'search_term'     => $searchTerm,
            'beneficiary_term'=> $beneficiaryTerm,
            'claim_reference' => $claimReference,
            'policy_number'   => $policyNumber,
            'hospital_code'   => $hospitalCode,
            'tpa_reference'   => $tpaReference,
            'min_amount'      => $minAmount,
            'max_amount'      => $maxAmount,
            'company_ids'     => $companyIds,
        ];
    }

    private function formatClaimRow(array $row): array
    {
        $beneficiaryName = $this->composeName(
            $row['beneficiary_first_name'] ?? null,
            $row['beneficiary_middle_name'] ?? null,
            $row['beneficiary_last_name'] ?? null
        );

        $dependentName = trim((string) ($row['dependent_first_name'] ?? ''));
        if ($dependentName !== '' && ! empty($row['dependent_relationship'])) {
            $dependentName .= ' (' . strtoupper($row['dependent_relationship']) . ')';
        }

        return [
            'id'                   => (int) $row['id'],
            'claim_reference'      => $row['claim_reference'],
            'external_reference'   => $row['external_reference'] ?? null,
            'status' => [
                'id'        => $row['status_id'] ? (int) $row['status_id'] : null,
                'code'      => $row['status_code'] ?? null,
                'label'     => $row['status_label'] ?? null,
                'isTerminal'=> (bool) ($row['status_is_terminal'] ?? 0),
            ],
            'type' => [
                'id'    => $row['claim_type_id'] ? (int) $row['claim_type_id'] : null,
                'code'  => $row['claim_type_code'] ?? null,
                'label' => $row['claim_type_label'] ?? null,
            ],
            'category'             => $row['claim_category'] ?? null,
            'sub_status'           => $row['claim_sub_status'] ?? null,
            'dates' => [
                'claim'     => $row['claim_date'] ?? null,
                'admission' => $row['admission_date'] ?? null,
                'discharge' => $row['discharge_date'] ?? null,
                'received'  => $row['received_at'] ?? null,
                'synced'    => $row['last_synced_at'] ?? null,
            ],
            'amounts' => [
                'claimed'     => $this->toFloat($row['claimed_amount'] ?? null),
                'approved'    => $this->toFloat($row['approved_amount'] ?? null),
                'cashless'    => $this->toFloat($row['cashless_amount'] ?? null),
                'copay'       => $this->toFloat($row['copay_amount'] ?? null),
                'non_payable' => $this->toFloat($row['non_payable_amount'] ?? null),
                'reimbursed'  => $this->toFloat($row['reimbursed_amount'] ?? null),
            ],
            'beneficiary' => [
                'id'              => (int) $row['beneficiary_id'],
                'reference'       => $row['beneficiary_reference_number'] ?? null,
                'name'            => $beneficiaryName ?: null,
                'mobile_masked'   => $row['beneficiary_mobile_masked'] ?? null,
            ],
            'dependent' => [
                'id'   => $row['dependent_id'] ? (int) $row['dependent_id'] : null,
                'name' => $dependentName ?: null,
            ],
            'policy' => [
                'card_id'       => $row['policy_card_id'] ? (int) $row['policy_card_id'] : null,
                'policy_number' => $row['policy_number'] ?? null,
                'card_number'   => $row['policy_card_number'] ?? null,
                'program'       => $row['policy_program'] ?? null,
                'provider'      => $row['policy_provider'] ?? null,
            ],
            'hospital' => [
                'name'  => $row['hospital_name'] ?? null,
                'code'  => $row['hospital_code'] ?? null,
                'city'  => $row['hospital_city'] ?? null,
                'state' => $row['hospital_state'] ?? null,
            ],
            'diagnosis'            => $row['diagnosis'] ?? null,
            'remarks'              => $row['remarks'] ?? null,
            'source' => [
                'channel'   => $row['source'] ?? null,
                'reference' => $row['source_reference'] ?? null,
            ],
            'company' => [
                'id'    => $row['company_id'] ? (int) $row['company_id'] : null,
                'code'  => $row['company_code'] ?? null,
                'name'  => $row['company_name'] ?? null,
            ],
            'timestamps' => [
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ],
        ];
    }

    private function buildBeneficiarySummary(int $beneficiaryId, array $filters): array
    {
        $builder = $this->db->table('claims c');
        $builder->selectCount('c.id', 'total_claims');
        $builder->selectSum('c.claimed_amount', 'total_claimed');
        $builder->selectSum('c.approved_amount', 'total_approved');
        $builder->selectSum('c.cashless_amount', 'total_cashless');
        $builder->selectSum('c.copay_amount', 'total_copay');
        $builder->selectSum('c.non_payable_amount', 'total_non_payable');
        $builder->where('c.beneficiary_id', $beneficiaryId);
        $this->applyFilters($builder, $filters, false);

        $row = $builder->get()->getRowArray() ?? [];

        $statusBuilder = $this->db->table('claims c');
        $statusBuilder->select('cs.code, cs.label, COUNT(c.id) AS total');
        $statusBuilder->join('claim_statuses cs', 'cs.id = c.status_id', 'left');
        $statusBuilder->where('c.beneficiary_id', $beneficiaryId);
        $this->applyFilters($statusBuilder, $filters, false);
        $statusBuilder->groupBy('cs.id');
        $statusBuilder->orderBy('cs.display_order', 'ASC');
        $statusRows = $statusBuilder->get()->getResultArray();

        $statusBreakdown = array_values(array_filter(array_map(static function (array $statusRow): array {
            return [
                'code'  => $statusRow['code'] ?? 'unknown',
                'label' => $statusRow['label'] ?? 'Unknown',
                'total' => isset($statusRow['total']) ? (int) $statusRow['total'] : 0,
            ];
        }, $statusRows), static fn (array $status): bool => $status['total'] > 0));

        return [
            'total_claims'      => (int) ($row['total_claims'] ?? 0),
            'total_claimed'     => $this->toFloat($row['total_claimed'] ?? null) ?? 0.0,
            'total_approved'    => $this->toFloat($row['total_approved'] ?? null) ?? 0.0,
            'total_cashless'    => $this->toFloat($row['total_cashless'] ?? null) ?? 0.0,
            'total_copay'       => $this->toFloat($row['total_copay'] ?? null) ?? 0.0,
            'total_non_payable' => $this->toFloat($row['total_non_payable'] ?? null) ?? 0.0,
            'status_breakdown'  => $statusBreakdown,
        ];
    }

    private function buildAdminSummary(array $filters): array
    {
        $builder = $this->db->table('claims c');
        $builder->selectCount('c.id', 'total_claims');
        $builder->selectSum('c.claimed_amount', 'total_claimed');
        $builder->selectSum('c.approved_amount', 'total_approved');
        $builder->selectSum('c.cashless_amount', 'total_cashless');
        $builder->selectSum('c.copay_amount', 'total_copay');
        $builder->selectSum('c.non_payable_amount', 'total_non_payable');
        $this->applyFilters($builder, $filters, false);
        $totals = $builder->get()->getRowArray() ?? [];

        $statusBuilder = $this->db->table('claims c');
        $statusBuilder->select('cs.code, cs.label, COUNT(c.id) AS total');
        $statusBuilder->join('claim_statuses cs', 'cs.id = c.status_id', 'left');
        $this->applyFilters($statusBuilder, $filters, false);
        $statusBuilder->groupBy('cs.id');
        $statusBuilder->orderBy('cs.display_order', 'ASC');
        $statusRows = $statusBuilder->get()->getResultArray();

        return [
            'totals' => [
                'total_claims'      => (int) ($totals['total_claims'] ?? 0),
                'total_claimed'     => $this->toFloat($totals['total_claimed'] ?? null) ?? 0.0,
                'total_approved'    => $this->toFloat($totals['total_approved'] ?? null) ?? 0.0,
                'total_cashless'    => $this->toFloat($totals['total_cashless'] ?? null) ?? 0.0,
                'total_copay'       => $this->toFloat($totals['total_copay'] ?? null) ?? 0.0,
                'total_non_payable' => $this->toFloat($totals['total_non_payable'] ?? null) ?? 0.0,
            ],
            'status_breakdown' => array_map(static function (array $row): array {
                return [
                    'code'  => $row['code'] ?? 'unknown',
                    'label' => $row['label'] ?? 'Unknown',
                    'total' => (int) ($row['total'] ?? 0),
                ];
            }, $statusRows),
        ];
    }

    private function fetchClaimEvents(int $claimId): array
    {
        $builder = $this->db->table('claim_events ce')
            ->select([
                'ce.id',
                'ce.claim_id',
                'ce.status_id',
                'cs.code AS status_code',
                'cs.label AS status_label',
                'ce.event_code',
                'ce.event_label',
                'ce.description',
                'ce.event_time',
                'ce.source',
                'ce.payload',
            ])
            ->join('claim_statuses cs', 'cs.id = ce.status_id', 'left')
            ->where('ce.claim_id', $claimId)
            ->orderBy('ce.event_time', 'DESC')
            ->orderBy('ce.id', 'DESC');

        $rows = $builder->get()->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'id'          => (int) $row['id'],
                'status'      => [
                    'id'    => $row['status_id'] ? (int) $row['status_id'] : null,
                    'code'  => $row['status_code'] ?? null,
                    'label' => $row['status_label'] ?? null,
                ],
                'event_code'  => $row['event_code'] ?? null,
                'event_label' => $row['event_label'] ?? null,
                'description' => $row['description'] ?? null,
                'event_time'  => $row['event_time'] ?? null,
                'source'      => $row['source'] ?? null,
                'payload'     => $row['payload'] ? json_decode($row['payload'], true) : null,
            ];
        }, $rows);
    }

    private function fetchClaimDocuments(int $claimId): array
    {
        $builder = $this->db->table('claim_documents cd')
            ->select([
                'cd.id',
                'cd.claim_id',
                'cd.document_type_id',
                'dt.code AS document_type_code',
                'dt.label AS document_type_label',
                'cd.title',
                'cd.storage_disk',
                'cd.storage_path',
                'cd.checksum',
                'cd.mime_type',
                'cd.file_size',
                'cd.source',
                'cd.uploaded_by',
                'cd.uploaded_at',
                'cd.metadata',
            ])
            ->join('claim_document_types dt', 'dt.id = cd.document_type_id', 'left')
            ->where('cd.claim_id', $claimId)
            ->orderBy('cd.created_at', 'DESC')
            ->orderBy('cd.id', 'DESC');

        $rows = $builder->get()->getResultArray();

        $allowedDisks = array_map('strtolower', $this->config->allowedDocumentDisks);

        return array_map(function (array $row) use ($allowedDisks): array {
            $disk = strtolower($row['storage_disk'] ?? '');

            return [
                'id'          => (int) $row['id'],
                'type'        => [
                    'id'    => $row['document_type_id'] ? (int) $row['document_type_id'] : null,
                    'code'  => $row['document_type_code'] ?? null,
                    'label' => $row['document_type_label'] ?? null,
                ],
                'title'       => $row['title'],
                'storage'     => [
                    'disk'        => $row['storage_disk'],
                    'path'        => $row['storage_path'],
                    'checksum'    => $row['checksum'] ?? null,
                    'mime_type'   => $row['mime_type'] ?? null,
                    'file_size'   => $row['file_size'] ? (int) $row['file_size'] : null,
                    'is_supported'=> in_array($disk, $allowedDisks, true),
                ],
                'source'      => $row['source'] ?? null,
                'uploaded_by' => $row['uploaded_by'] ? (int) $row['uploaded_by'] : null,
                'uploaded_at' => $row['uploaded_at'] ?? null,
                'metadata'    => $row['metadata'] ? json_decode($row['metadata'], true) : null,
            ];
        }, $rows);
    }

    private function composeName(?string $first, ?string $middle, ?string $last): string
    {
        $parts = array_filter([$first, $middle, $last], static fn (?string $value) => $value !== null && trim($value) !== '');
        return trim(implode(' ', $parts));
    }

    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function parseAmount($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function toFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return null;
    }
}
