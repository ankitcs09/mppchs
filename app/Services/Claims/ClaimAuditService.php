<?php

namespace App\Services\Claims;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Config\Database;

class ClaimAuditService
{
    private string $driver;

    public function __construct(private readonly ConnectionInterface $db)
    {
        $this->driver = strtolower($this->db->DBDriver ?? '');
    }

    public static function new(?ConnectionInterface $connection = null): self
    {
        return new self($connection ?? Database::connect());
    }

    public function listIngestBatches(array $filters = [], ?array $companyScope = null, int $page = 1, int $perPage = 25): array
    {
        $normalized = $this->normalizeBatchFilters($filters, $companyScope);
        $perPage    = max(1, $perPage);
        $page       = max(1, $page);
        $offset     = ($page - 1) * $perPage;

        $countBuilder = $this->db->table('claim_ingest_batches');
        $this->applyBatchFilters($countBuilder, $normalized);
        $total = (int) $countBuilder->countAllResults();

        $dataBuilder = $this->db->table('claim_ingest_batches');
        $this->applyBatchFilters($dataBuilder, $normalized);
        $dataBuilder->orderBy('processed_at', 'DESC')->orderBy('id', 'DESC');
        $rows = $dataBuilder->get($perPage, $offset)->getResultArray();

        $summaryBuilder = $this->db->table('claim_ingest_batches');
        $summaryBuilder->selectSum('claims_received', 'total_received');
        $summaryBuilder->selectSum('claims_success', 'total_success');
        $summaryBuilder->selectSum('claims_failed', 'total_failed');
        $this->applyBatchFilters($summaryBuilder, $normalized);
        $summaryRow = $summaryBuilder->get()->getRowArray() ?? [];

        $batches = array_map([$this, 'formatBatchRow'], $rows);

        $documentsAggregate = [
            'totals' => [
                'attempted' => 0,
                'ingested'  => 0,
                'updated'   => 0,
                'failed'    => 0,
                'missing'   => 0,
                'partial'   => 0,
            ],
            'matrix' => [
                'record_ok_doc_ok'        => 0,
                'record_ok_doc_partial'   => 0,
                'record_ok_doc_failed'    => 0,
                'record_ok_doc_missing'   => 0,
                'record_fail_doc_ok'      => 0,
                'record_fail_doc_partial' => 0,
                'record_fail_doc_failed'  => 0,
                'record_fail_doc_missing' => 0,
            ],
        ];

        foreach ($batches as $batchRow) {
            $docSummary = $batchRow['documents_summary'] ?? [];
            $docTotals  = $docSummary['totals'] ?? [];
            $docMatrix  = $docSummary['matrix'] ?? [];

            foreach ($documentsAggregate['totals'] as $key => $value) {
                if (isset($docTotals[$key])) {
                    $documentsAggregate['totals'][$key] += (int) $docTotals[$key];
                }
            }

            foreach ($documentsAggregate['matrix'] as $key => $value) {
                if (isset($docMatrix[$key])) {
                    $documentsAggregate['matrix'][$key] += (int) $docMatrix[$key];
                }
            }
        }

        return [
            'data'       => $batches,
            'pagination' => [
                'total'   => $total,
                'page'    => $page,
                'perPage' => $perPage,
                'pages'   => (int) ceil($total / $perPage),
            ],
            'summary'    => [
                'total_received' => (int) ($summaryRow['total_received'] ?? 0),
                'total_success'  => (int) ($summaryRow['total_success'] ?? 0),
                'total_failed'   => (int) ($summaryRow['total_failed'] ?? 0),
                'documents'      => $documentsAggregate,
            ],
            'filters'    => $normalized,
        ];
    }

    public function getIngestBatch(int $id): ?array
    {
        $row = $this->db->table('claim_ingest_batches')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        return $this->formatBatchRow($row, includeItems: true);
    }

    public function listDocumentDownloads(array $filters = [], ?array $companyScope = null, int $page = 1, int $perPage = 25): array
    {
        $normalized = $this->normalizeDownloadFilters($filters, $companyScope);
        $perPage    = max(1, $perPage);
        $page       = max(1, $page);
        $offset     = ($page - 1) * $perPage;

        $countBuilder = $this->baseDownloadBuilder();
        $this->applyDownloadFilters($countBuilder, $normalized);
        $total = (int) $countBuilder->countAllResults();

        $dataBuilder = $this->baseDownloadBuilder();
        $this->applyDownloadFilters($dataBuilder, $normalized);
        $dataBuilder->orderBy('l.downloaded_at', 'DESC')->orderBy('l.id', 'DESC');
        $rows = $dataBuilder->get($perPage, $offset)->getResultArray();

        $summaryBuilder = $this->baseDownloadBuilder();
        $summaryBuilder->select('l.access_channel, COUNT(*) AS total');
        $this->applyDownloadFilters($summaryBuilder, $normalized);
        $summaryBuilder->groupBy('l.access_channel');
        $summaryRows = $summaryBuilder->get()->getResultArray();

        $downloads = array_map([$this, 'formatDownloadRow'], $rows);

        return [
            'data'       => $downloads,
            'pagination' => [
                'total'   => $total,
                'page'    => $page,
                'perPage' => $perPage,
                'pages'   => (int) ceil($total / $perPage),
            ],
            'summary'    => [
                'channel_breakdown' => array_map(static function (array $row): array {
                    return [
                        'channel' => $row['access_channel'] ?? 'unknown',
                        'total'   => (int) ($row['total'] ?? 0),
                    ];
                }, $summaryRows),
            ],
            'filters'    => $normalized,
        ];
    }

    private function formatBatchRow(array $row, bool $includeItems = false): array
    {
        $metadata = [];
        if (! empty($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $items = $metadata['items'] ?? [];
        if (! $includeItems) {
            $items = array_slice($items, 0, 5);
        }

        $notes = [];
        if (! empty($row['notes'])) {
            $notes = array_filter(array_map('trim', explode(';', (string) $row['notes'])));
        }

        $companyIds = [];
        if (! empty($row['company_ids'])) {
            foreach (explode(',', $row['company_ids']) as $id) {
                $id = (int) trim($id);
                if ($id > 0) {
                    $companyIds[] = $id;
                }
            }
            $companyIds = array_values(array_unique($companyIds));
        }

        $documentsSummary = [
            'totals' => $metadata['summary']['documents']['totals'] ?? [],
            'matrix' => $metadata['summary']['documents']['matrix'] ?? [],
        ];

        return [
            'id'              => (int) $row['id'],
            'batch_reference' => $row['batch_reference'] ?? null,
            'claims'          => [
                'received' => (int) ($row['claims_received'] ?? 0),
                'success'  => (int) ($row['claims_success'] ?? 0),
                'failed'   => (int) ($row['claims_failed'] ?? 0),
            ],
            'source'          => [
                'ip'         => $row['requested_ip'] ?? null,
                'user_agent' => $row['user_agent'] ?? null,
            ],
            'company_ids'     => $companyIds,
            'notes'           => $notes,
            'processed_at'    => $row['processed_at'] ?? null,
            'created_at'      => $row['created_at'] ?? null,
            'items'           => $items,
            'documents_summary'=> $documentsSummary,
            'metadata'        => $metadata,
        ];
    }

    private function formatDownloadRow(array $row): array
    {
        return [
            'id'          => (int) $row['log_id'],
            'channel'     => $row['access_channel'] ?? null,
            'user'        => [
                'id'       => $row['user_id'] ? (int) $row['user_id'] : null,
                'type'     => $row['user_type'] ?? null,
                'name'     => $row['user_name'] ?? null,
                'email'    => $row['user_email'] ?? null,
                'company'  => $row['user_company'] ?? null,
            ],
            'claim'       => [
                'id'        => $row['claim_id'] ? (int) $row['claim_id'] : null,
                'reference' => $row['claim_reference'] ?? null,
                'company_id'=> $row['claim_company_id'] ? (int) $row['claim_company_id'] : null,
            ],
            'document'    => [
                'id'        => $row['document_id'] ? (int) $row['document_id'] : null,
                'title'     => $row['document_title'] ?? null,
                'type_code' => $row['document_type_code'] ?? null,
                'type_label'=> $row['document_type_label'] ?? null,
                'mime'      => $row['document_mime'] ?? null,
            ],
            'client'      => [
                'ip'        => $row['client_ip'] ?? null,
                'user_agent'=> $row['log_user_agent'] ?? null,
            ],
            'downloaded_at'=> $row['downloaded_at'] ?? null,
        ];
    }

    private function normalizeBatchFilters(array $filters, ?array $companyScope): array
    {
        $sanitize = static fn ($value): ?string => ($value === null || trim((string) $value) === '') ? null : trim((string) $value);

        $from = $this->parseDateTime($filters['from'] ?? $filters['from_date'] ?? null);
        $to   = $this->parseDateTime($filters['to'] ?? $filters['to_date'] ?? null);

        $hasFailures = null;
        if (isset($filters['has_failures'])) {
            $value = $filters['has_failures'];
            if (is_string($value)) {
                $value = strtolower($value);
                if (in_array($value, ['1', 'true', 'yes'], true)) {
                    $hasFailures = true;
                } elseif (in_array($value, ['0', 'false', 'no'], true)) {
                    $hasFailures = false;
                }
            } elseif (is_bool($value)) {
                $hasFailures = $value;
            }
        }

        $companyIds = null;
        if ($companyScope !== null) {
            $companyIds = array_map('intval', array_filter($companyScope, static fn ($id) => $id !== null));
        }
        if (! empty($filters['company_id'])) {
            $requested = (int) $filters['company_id'];
            if ($companyIds === null || in_array($requested, $companyIds, true)) {
                $companyIds = [$requested];
            } else {
                $companyIds = [];
            }
        }

        return [
            'reference'    => $sanitize($filters['reference'] ?? $filters['batch_reference'] ?? null),
            'source_ip'    => $sanitize($filters['source_ip'] ?? $filters['ip'] ?? null),
            'from'         => $from,
            'to'           => $to,
            'has_failures' => $hasFailures,
            'company_ids'  => $companyIds,
        ];
    }

    private function normalizeDownloadFilters(array $filters, ?array $companyScope): array
    {
        $sanitize = static fn ($value): ?string => ($value === null || trim((string) $value) === '') ? null : trim((string) $value);

        $from = $this->parseDateTime($filters['from'] ?? $filters['from_date'] ?? null);
        $to   = $this->parseDateTime($filters['to'] ?? $filters['to_date'] ?? null);

        $companyIds = null;
        if ($companyScope !== null) {
            $companyIds = array_map('intval', array_filter($companyScope, static fn ($id) => $id !== null));
        }
        if (! empty($filters['company_id'])) {
            $requested = (int) $filters['company_id'];
            if ($companyIds === null || in_array($requested, $companyIds, true)) {
                $companyIds = [$requested];
            } else {
                $companyIds = [];
            }
        }

        return [
            'channel'          => $sanitize($filters['channel'] ?? $filters['access_channel'] ?? null),
            'user_type'        => $sanitize($filters['user_type'] ?? null),
            'claim_reference'  => $sanitize($filters['claim_reference'] ?? null),
            'document_type'    => $sanitize($filters['document_type'] ?? $filters['document_type_code'] ?? null),
            'from'             => $from,
            'to'               => $to,
            'company_ids'      => $companyIds,
            'user_id'          => isset($filters['user_id']) ? (int) $filters['user_id'] : null,
            'beneficiary_id'   => isset($filters['beneficiary_id']) ? (int) $filters['beneficiary_id'] : null,
        ];
    }

    private function applyBatchFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['reference']) {
            $builder->like('batch_reference', $filters['reference'], 'both');
        }

        if ($filters['source_ip']) {
            $builder->like('requested_ip', $filters['source_ip'], 'both');
        }

        if ($filters['from']) {
            $builder->where('processed_at >=', $filters['from']);
        }

        if ($filters['to']) {
            $builder->where('processed_at <=', $filters['to']);
        }

        if ($filters['has_failures'] === true) {
            $builder->where('claims_failed >', 0);
        } elseif ($filters['has_failures'] === false) {
            $builder->where('claims_failed', 0);
        }

        if (is_array($filters['company_ids'])) {
            if ($filters['company_ids'] === []) {
                $builder->where('1 = 0', null, false);
            } elseif ($this->driver === 'sqlite3') {
                $builder->groupStart();
                foreach ($filters['company_ids'] as $id) {
                    $id = (int) $id;
                    $builder->orWhere("instr(',' || coalesce(company_ids, '') || ',', ',' || {$id} || ',') > 0", null, false);
                }
                $builder->groupEnd();
            } else {
                $builder->groupStart();
                foreach ($filters['company_ids'] as $id) {
                    $builder->orWhere("FIND_IN_SET(" . (int) $id . ", company_ids) >", 0, false);
                }
                $builder->groupEnd();
            }
        }
    }

    private function baseDownloadBuilder(): BaseBuilder
    {
        return $this->db->table('claim_document_access_log l')
            ->select([
                'l.id AS log_id',
                'l.claim_id',
                'l.document_id',
                'l.user_id',
                'l.user_type',
                'l.access_channel',
                'l.client_ip',
                'l.user_agent AS log_user_agent',
                'l.downloaded_at',
                'c.claim_reference',
                'c.company_id AS claim_company_id',
                'd.title AS document_title',
                'd.mime_type AS document_mime',
                'dt.code AS document_type_code',
                'dt.label AS document_type_label',
                'u.display_name AS user_name',
                'u.email AS user_email',
                'companies.name AS user_company',
            ])
            ->join('claims c', 'c.id = l.claim_id', 'left')
            ->join('claim_documents d', 'd.id = l.document_id', 'left')
            ->join('claim_document_types dt', 'dt.id = d.document_type_id', 'left')
            ->join('app_users u', 'u.id = l.user_id', 'left')
            ->join('companies', 'companies.id = u.company_id', 'left');
    }

    private function applyDownloadFilters(BaseBuilder $builder, array $filters): void
    {
        if ($filters['channel']) {
            $builder->where('l.access_channel', $filters['channel']);
        }

        if ($filters['user_type']) {
            $builder->where('l.user_type', $filters['user_type']);
        }

        if ($filters['claim_reference']) {
            $builder->like('c.claim_reference', $filters['claim_reference'], 'both');
        }

        if ($filters['document_type']) {
            $builder->where('dt.code', $filters['document_type']);
        }

        if ($filters['from']) {
            $builder->where('l.downloaded_at >=', $filters['from']);
        }

        if ($filters['to']) {
            $builder->where('l.downloaded_at <=', $filters['to']);
        }

        if (is_array($filters['company_ids'])) {
            if ($filters['company_ids'] === []) {
                $builder->where('1 = 0', null, false);
            } else {
                $builder->whereIn('c.company_id', $filters['company_ids']);
            }
        }

        if ($filters['user_id']) {
            $builder->where('l.user_id', $filters['user_id']);
        }

        if ($filters['beneficiary_id']) {
            $builder->join('beneficiaries_v2 b', 'b.id = c.beneficiary_id', 'left');
            $builder->where('b.id', $filters['beneficiary_id']);
        }
    }

    private function parseDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
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
}
