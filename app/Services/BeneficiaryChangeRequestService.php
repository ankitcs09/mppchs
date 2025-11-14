<?php

namespace App\Services;

use App\Events\ChangeRequestEvent;
use App\Models\BeneficiaryChangeAuditModel;
use App\Models\BeneficiaryChangeDependentModel;
use App\Models\BeneficiaryChangeItemModel;
use App\Models\BeneficiaryChangeRequestModel;
use App\Models\BeneficiaryDependentV2Model;
use App\Models\BeneficiaryV2Model;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Events\Events;
use Config\Database;
use RuntimeException;
use const JSON_THROW_ON_ERROR;

class BeneficiaryChangeRequestService
{
    private const LIST_CACHE_TTL = 60;

    private BeneficiaryChangeRequestModel $requests;
    private BeneficiaryChangeDependentModel $changeDependents;
    private BeneficiaryChangeAuditModel $audit;
    private BeneficiaryChangeItemModel $changeItems;
    private BeneficiaryDependentV2Model $beneficiaryDependents;
    private BeneficiaryV2Model $beneficiaries;
    private BaseConnection $db;
    private SensitiveDataService $crypto;

    /**
     * List of beneficiary columns we will update during approval.
     */
    private array $beneficiaryUpdatableColumns = [
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
        'ppo_number_masked',
        'pran_number_masked',
        'gpf_number_masked',
        'bank_source_id',
        'bank_source_other',
        'bank_servicing_id',
        'bank_servicing_other',
        'bank_account_masked',
        'aadhaar_masked',
        'pan_masked',
        'primary_mobile_masked',
        'alternate_mobile_masked',
        'email',
        'blood_group_id',
        'samagra_masked',
    ];

    private array $beneficiarySensitiveMap = [
        'aadhaar'      => ['enc' => 'aadhaar_enc', 'masked' => 'aadhaar_masked', 'mask' => 'digits'],
        'pan'          => ['enc' => 'pan_enc', 'masked' => 'pan_masked', 'mask' => 'alnum'],
        'samagra'      => ['enc' => 'samagra_enc', 'masked' => 'samagra_masked', 'mask' => 'digits'],
        'ppo_number'   => ['enc' => 'ppo_number_enc', 'masked' => 'ppo_number_masked', 'mask' => 'alnum'],
        'pran_number'  => ['enc' => 'pran_number_enc', 'masked' => 'pran_number_masked', 'mask' => 'alnum'],
        'gpf_number'   => ['enc' => 'gpf_number_enc', 'masked' => 'gpf_number_masked', 'mask' => 'alnum'],
    ];

    private array $dependentSensitiveMap = [
        'aadhaar' => ['enc' => 'aadhaar_enc', 'masked' => 'aadhaar_masked', 'mask' => 'digits'],
    ];

    public function __construct(
        ?BeneficiaryChangeRequestModel $requests = null,
        ?BeneficiaryChangeDependentModel $changeDependents = null,
        ?BeneficiaryChangeAuditModel $audit = null,
        ?BeneficiaryChangeItemModel $changeItems = null,
        ?BeneficiaryDependentV2Model $beneficiaryDependents = null,
        ?BeneficiaryV2Model $beneficiaries = null,
        ?BaseConnection $db = null,
        ?SensitiveDataService $crypto = null
    ) {
        $this->requests              = $requests ?? new BeneficiaryChangeRequestModel();
        $this->changeDependents      = $changeDependents ?? new BeneficiaryChangeDependentModel();
        $this->audit                 = $audit ?? new BeneficiaryChangeAuditModel();
        $this->changeItems           = $changeItems ?? new BeneficiaryChangeItemModel();
        $this->beneficiaryDependents = $beneficiaryDependents ?? new BeneficiaryDependentV2Model();
        $this->beneficiaries         = $beneficiaries ?? new BeneficiaryV2Model();
        $this->db                    = $db ?? Database::connect();
        $this->crypto                = $crypto ?? new SensitiveDataService();
    }

    /**
     * Fetch active draft/pending request for a beneficiary.
     */
    public function getActiveRequest(int $beneficiaryId): ?array
    {
        return $this->requests
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->whereIn('status', ['draft', 'pending', 'needs_info'])
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * List change requests submitted by a beneficiary (most recent first).
     *
     * @return array<int,array<string,mixed>>
     */
    public function listForBeneficiary(int $beneficiaryId, int $limit = 50): array
    {
        $cacheKey = $this->listCacheKey($beneficiaryId);

        return cache()->remember($cacheKey, self::LIST_CACHE_TTL, function () use ($beneficiaryId, $limit) {
            $rows = $this->requests
                ->select('id, reference_number, submission_no, revision_no, status, requested_at, reviewed_at, review_comment, summary_diff, created_at, updated_at')
                ->where('beneficiary_v2_id', $beneficiaryId)
                ->orderBy('created_at', 'DESC')
                ->findAll($limit);

            return array_map(function (array $row): array {
                return [
                    'id'               => (int) $row['id'],
                    'reference_number' => $row['reference_number'] ?? null,
                    'submission_no'    => isset($row['submission_no']) ? (int) $row['submission_no'] : null,
                    'revision_no'      => isset($row['revision_no']) ? (int) $row['revision_no'] : null,
                    'status'           => $row['status'] ?? 'draft',
                    'requested_at'     => $row['requested_at'] ?? null,
                    'reviewed_at'      => $row['reviewed_at'] ?? null,
                    'review_comment'   => $row['review_comment'] ?? null,
                    'created_at'       => $row['created_at'] ?? null,
                    'updated_at'       => $row['updated_at'] ?? null,
                    'summary'          => $this->decodeSummary($row['summary_diff'] ?? null),
                ];
            }, $rows);
        });
    }

    /**
     * Fetch a specific change request for a beneficiary, including decoded payloads and diffs.
     */
    public function getRequestForBeneficiary(int $beneficiaryId, int $requestId): array
    {
        $request = $this->requests
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->find($requestId);

        if (! $request) {
            throw new RuntimeException('Change request not found for this beneficiary.');
        }

        $beforePayload = $this->decodePayload($request['payload_before'] ?? null);
        $afterPayload  = $this->decodePayload($request['payload_after'] ?? null);

        $beforeBeneficiaryRaw = $beforePayload['beneficiary'] ?? [];
        $afterBeneficiary  = $afterPayload['beneficiary'] ?? [];
        $beforeDependents  = $beforePayload['dependents'] ?? [];
        $afterDependents   = $afterPayload['dependents'] ?? [];

        $currentRecord = $this->beneficiaries->find($beneficiaryId) ?? [];
        $beforeBeneficiaryDisplay = $this->applyBeneficiaryFallbacks($beforeBeneficiaryRaw, $currentRecord);

        $dependentDiff = $this->loadDependentDiffs((int) $request['id'], $beforeDependents, $afterDependents);

        return [
            'request' => [
                'id'               => (int) $request['id'],
                'reference_number' => $request['reference_number'] ?? null,
                'submission_no'    => isset($request['submission_no']) ? (int) $request['submission_no'] : null,
                'revision_no'      => isset($request['revision_no']) ? (int) $request['revision_no'] : null,
                'status'           => $request['status'] ?? 'draft',
                'requested_at'     => $request['requested_at'] ?? null,
                'reviewed_at'      => $request['reviewed_at'] ?? null,
                'review_comment'   => $request['review_comment'] ?? null,
                'created_at'       => $request['created_at'] ?? null,
                'updated_at'       => $request['updated_at'] ?? null,
                'summary'          => $this->decodeSummary($request['summary_diff'] ?? null),
            ],
            'beneficiary' => [
                'before'         => $beforeBeneficiaryRaw,
                'before_display' => $beforeBeneficiaryDisplay,
                'after'  => $afterBeneficiary,
                'diff'   => $this->diffBeneficiary($beforeBeneficiaryRaw, $afterBeneficiary),
            ],
            'dependents' => [
                'before' => $beforeDependents,
                'after'  => $afterDependents,
                'diff'   => $dependentDiff,
            ],
            'items' => $this->listItems((int) $request['id']),
            'itemCounts' => $this->getItemStats((int) $request['id']),
        ];
    }

    /**
     * Create or update a draft request (either new or resubmission).
     */
    public function saveDraft(
        int $beneficiaryId,
        int $userId,
        array $payloadBefore,
        array $payloadAfter,
        array $summaryDiff = [],
        array $diff = [],
        array $meta = []
    ): array {
        $existing = $this->getActiveRequest($beneficiaryId);

        $data = [
            'beneficiary_v2_id' => $beneficiaryId,
            'user_id'           => $userId,
            'reference_number'  => $meta['reference_number'] ?? null,
            'legacy_reference'  => $meta['legacy_reference'] ?? null,
            'ip_address'        => $meta['ip_address'] ?? null,
            'user_agent'        => $meta['user_agent'] ?? null,
            'payload_before'    => json_encode($payloadBefore, JSON_THROW_ON_ERROR),
            'payload_after'     => json_encode($payloadAfter, JSON_THROW_ON_ERROR),
            'summary_diff'      => ! empty($summaryDiff) ? json_encode($summaryDiff, JSON_THROW_ON_ERROR) : null,
            'undertaking_confirmed' => ! empty($meta['undertaking_confirmed']) ? 1 : 0,
            'status'            => $existing['status'] ?? 'draft',
        ];

        if ($existing) {
            $data['revision_no'] = (int) ($existing['revision_no'] ?? 1) + 1;
            $this->requests->update($existing['id'], $data);
            $requestId = (int) $existing['id'];
        } else {
            $data['submission_no'] = $this->nextSubmissionNumber($beneficiaryId);
            $data['requested_at']  = null;
            $requestId             = $this->requests->insert($data, true);

            $this->audit->insert([
                'change_request_id' => $requestId,
                'action'            => 'draft_created',
                'actor_id'          => $userId,
                'notes'             => null,
                'created_at'        => utc_now(),
            ]);
        }

        log_message('debug', '[ChangeRequest] Draft saved request={request} beneficiary={beneficiary}', [
            'request'     => $requestId,
            'beneficiary' => $beneficiaryId,
        ]);

        $this->syncChangeItems($requestId, $diff);

        $draft = $this->requests->find($requestId);
        $this->updateBeneficiarySummaryDraft($beneficiaryId, $draft);
        $this->clearListCache($beneficiaryId);

        return $draft;
    }

    /**
     * Retrieve per-field change items for a request.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listItems(int $requestId): array
    {
        return $this->changeItems
            ->where('change_request_id', $requestId)
            ->orderBy('entity_type', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Aggregate counts of item statuses for quick summaries.
     */
    public function getItemStats(int $requestId): array
    {
        $rows = $this->changeItems
            ->select('status, COUNT(*) as total')
            ->where('change_request_id', $requestId)
            ->groupBy('status')
            ->findAll();

        $stats = [
            'total'     => 0,
            'pending'   => 0,
            'approved'  => 0,
            'rejected'  => 0,
            'needs_info'=> 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? 'pending';
            $count  = (int) ($row['total'] ?? 0);
            $stats['total'] += $count;
            if (isset($stats[$status])) {
                $stats[$status] += $count;
            }
        }

        return $stats;
    }

    /**
     * Submit (or resubmit) the current draft for review.
     */
    public function submitDraft(int $requestId, ?int $userId = null): array
    {
        $request = $this->requireRequest($requestId);
        if (! in_array($request['status'], ['draft', 'needs_info'], true)) {
            throw new RuntimeException('Only draft or needs-info requests can be submitted.');
        }

        $this->requests->update($requestId, [
            'status'       => 'pending',
            'requested_at' => utc_now(),
        ]);

        $updated = $this->requests->find($requestId);
        $this->updateBeneficiarySummaryPending((int) $updated['beneficiary_v2_id'], $updated);

        $this->dispatchEvent('submitted', $updated, $userId);

        log_message('info', '[ChangeRequest] Request submitted id={id}', ['id' => $requestId]);

        return $updated;
    }

    /**
     * Approve a change request and apply the payload to the live tables.
     */
    public function approve(int $requestId, int $reviewerId, string $comment = ''): array
    {
        $request = $this->requireRequest($requestId);
        if ($request['status'] !== 'pending') {
            throw new RuntimeException('Only pending requests can be approved.');
        }

        $this->db->transStart();

        $payloadAfter = $this->decodePayload($request['payload_after'] ?? null);
        $this->applyApprovedChanges($request, $payloadAfter, $reviewerId);

        $updateData = [
            'status'        => 'approved',
            'reviewed_at'   => utc_now(),
            'reviewed_by'   => $reviewerId,
            'review_comment'=> $comment ?: null,
        ];
        $this->requests->update($requestId, $updateData);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Failed to approve the change request.');
        }

        $updated = $this->requests->find($requestId);
        $this->updateBeneficiarySummaryApproved((int) $updated['beneficiary_v2_id'], $updated);

        $this->dispatchEvent('approved', $updated, $reviewerId, ['note' => $comment ?: null]);

        log_message('info', '[ChangeRequest] Request approved id={id} reviewer={reviewer}', [
            'id'       => $requestId,
            'reviewer' => $reviewerId,
        ]);

        return $updated;
    }

    /**
     * Reject a change request.
     */
    public function reject(int $requestId, int $reviewerId, string $comment = ''): array
    {
        $request = $this->requireRequest($requestId);
        if (! in_array($request['status'], ['pending', 'needs_info'], true)) {
            throw new RuntimeException('Only pending requests can be rejected.');
        }

        $updateData = [
            'status'        => 'rejected',
            'reviewed_at'   => utc_now(),
            'reviewed_by'   => $reviewerId,
            'review_comment'=> $comment ?: null,
        ];

        $this->requests->update($requestId, $updateData);

        $updated = $this->requests->find($requestId);
        $this->updateBeneficiarySummaryTerminal((int) $updated['beneficiary_v2_id'], $updated);

        $this->dispatchEvent('rejected', $updated, $reviewerId, ['note' => $comment ?: null]);

        log_message('info', '[ChangeRequest] Request rejected id={id}', ['id' => $requestId]);

        return $updated;
    }

    /**
     * Mark the request as needing additional information (sent back to beneficiary).
     */
    public function requestMoreInfo(int $requestId, int $reviewerId, string $comment): array
    {
        $request = $this->requireRequest($requestId);
        if ($request['status'] !== 'pending') {
            throw new RuntimeException('Only pending requests can be moved to needs-info.');
        }

        $this->requests->update($requestId, [
            'status'         => 'needs_info',
            'reviewed_at'    => utc_now(),
            'reviewed_by'    => $reviewerId,
            'review_comment' => $comment,
        ]);

        $updated = $this->requests->find($requestId);
        $this->updateBeneficiarySummaryPending((int) $updated['beneficiary_v2_id'], $updated);

        $this->dispatchEvent('needs_info', $updated, $reviewerId, ['note' => $comment]);

        log_message('info', '[ChangeRequest] Request flagged for more info id={id}', ['id' => $requestId]);

        return $updated;
    }

    public function reviewItem(int $requestId, int $itemId, string $status, int $reviewerId, ?string $note = null): array
    {
        $request = $this->requireRequest($requestId);
        if (! in_array($request['status'], ['pending', 'needs_info'], true)) {
            throw new RuntimeException('Only pending requests can be reviewed.');
        }

        $item = $this->requireItem($requestId, $itemId);
        $status = strtolower($status);

        $allowed = ['pending', 'approved', 'rejected', 'needs_info'];
        if (! in_array($status, $allowed, true)) {
            throw new RuntimeException('Unsupported item status.');
        }

        $payload = [
            'status'      => $status,
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];

        if ($status !== 'pending') {
            $payload['review_note'] = $note;
            $payload['reviewed_by'] = $reviewerId;
            $payload['reviewed_at'] = utc_now();
        }

        $this->changeItems->update($itemId, $payload);

        $updatedItem = $this->changeItems->find($itemId);

        $this->audit->insert([
            'change_request_id' => $requestId,
            'action'            => 'item_' . $status,
            'actor_id'          => $reviewerId,
            'notes'             => $note,
            'created_at'        => utc_now(),
        ]);

        $result = [
            'item'  => $updatedItem,
            'stats' => $this->getItemStats($requestId),
        ];

        $request = $this->requests->find($requestId);
        if ($request) {
            $this->dispatchEvent('item_' . $status, $request, $reviewerId, ['note' => $note]);
        }

        return $result;
    }

    /**
     * Persist row-level dependent changes for comparison/reporting.
     */
    public function syncDependentDiffs(int $requestId, array $dependentDiffs): void
    {
        $this->changeDependents
            ->where('change_request_id', $requestId)
            ->delete();

        foreach ($dependentDiffs as $diff) {
            $this->changeDependents->insert([
                'change_request_id' => $requestId,
                'dependent_id'      => $diff['dependent_id'] ?? null,
                'action'            => $diff['action'] ?? 'update',
                'order_index'       => $diff['order'] ?? null,
                'relationship_key'  => $diff['relationship_key'] ?? null,
                'alive_status'      => $diff['alive_status'] ?? null,
                'health_status'     => $diff['health_status'] ?? null,
                'full_name'         => $diff['full_name'] ?? null,
                'payload_before'    => isset($diff['before']) ? json_encode($diff['before'], JSON_THROW_ON_ERROR) : null,
                'payload_after'     => isset($diff['after']) ? json_encode($diff['after'], JSON_THROW_ON_ERROR) : null,
            ]);
        }
    }

    private function syncChangeItems(int $requestId, array $diff): void
    {
        $this->changeItems
            ->where('change_request_id', $requestId)
            ->delete();

        if (empty($diff)) {
            return;
        }

        $timestamp = utc_now();

        foreach ($diff['beneficiary'] ?? [] as $field => $change) {
            $this->changeItems->insert([
                'change_request_id' => $requestId,
                'entity_type'       => 'beneficiary',
                'entity_identifier' => null,
                'field_key'         => $field,
                'field_label'       => $this->formatFieldLabel($field),
                'old_value'         => $this->serializeValue($change['before'] ?? null),
                'new_value'         => $this->serializeValue($change['after'] ?? null),
                'status'            => 'pending',
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
            ]);
        }

        $dependents = $diff['dependents'] ?? [];
        foreach ($dependents as $index => $row) {
            foreach ($this->buildDependentItems($requestId, $row, $timestamp, $index) as $item) {
                $this->changeItems->insert($item);
            }
        }
    }

    /**
     * Apply approved payload to beneficiaries_v2 and dependents tables.
     */
    private function applyApprovedChanges(array $request, array $payload, int $reviewerId): void
    {
        $beneficiaryId = (int) $request['beneficiary_v2_id'];
        $beneficiaryData = $payload['beneficiary'] ?? [];
        $dependentsData  = $payload['dependents'] ?? [];

        if (! empty($beneficiaryData)) {
            $update = array_intersect_key($beneficiaryData, array_flip($this->beneficiaryUpdatableColumns));

            foreach ($this->beneficiarySensitiveMap as $field => $meta) {
                if (array_key_exists($field, $update)) {
                    unset($update[$field]);
                }

                if (! array_key_exists($field, $beneficiaryData)) {
                    continue;
                }

                $raw = trim((string) ($beneficiaryData[$field] ?? ''));
                if ($raw === '') {
                    continue;
                }

                $update[$meta['enc']]    = $this->crypto->encrypt($raw);
                $update[$meta['masked']] = $this->maskValue($raw, $meta['mask']);
            }

            if (! empty($update)) {
                $update['updated_at'] = utc_now();
                $this->beneficiaries->update($beneficiaryId, $update);
            }
        }

        if (! empty($dependentsData)) {
            $now = utc_now();
            foreach ($dependentsData as $row) {
                $action = $row['action'] ?? 'update';
                $data   = $row['data'] ?? $row;
                $dependentId = isset($row['id']) ? (int) $row['id'] : (isset($data['id']) ? (int) $data['id'] : null);
                $existing = $dependentId ? $this->beneficiaryDependents->find($dependentId) : null;

                if ($action === 'remove' && $dependentId) {
                    if ($existing) {
                        $this->beneficiaryDependents->update($dependentId, [
                            'is_active'   => 0,
                            'deleted_at'  => $now,
                            'deleted_by'  => $reviewerId,
                            'restored_at' => null,
                            'restored_by' => null,
                            'updated_at'  => $now,
                            'updated_by'  => $reviewerId,
                        ]);
                    }
                    continue;
                }

                $recordSource = $data;
                $record = [
                    'beneficiary_id'      => $beneficiaryId,
                    'relationship'        => $recordSource['relationship'] ?? null,
                    'dependant_order'     => $recordSource['dependant_order'] ?? null,
                    'twin_group'          => $recordSource['twin_group'] ?? null,
                    'is_alive'            => $recordSource['is_alive'] ?? null,
                    'is_health_dependant' => $recordSource['is_health_dependant'] ?? null,
                    'first_name'          => $recordSource['first_name'] ?? null,
                    'gender'              => $recordSource['gender'] ?? null,
                    'blood_group_id'      => $recordSource['blood_group_id'] ?? null,
                    'date_of_birth'       => $recordSource['date_of_birth'] ?? null,
                    'updated_by'          => $reviewerId,
                    'updated_at'          => $now,
                    'is_active'           => 1,
                    'deleted_at'          => null,
                    'deleted_by'          => null,
                ];

                $record['restored_at'] = $existing['restored_at'] ?? null;
                $record['restored_by'] = $existing['restored_by'] ?? null;
                if ($existing && (int) ($existing['is_active'] ?? 1) === 0) {
                    $record['restored_at'] = $now;
                    $record['restored_by'] = $reviewerId;
                }

                foreach ($this->dependentSensitiveMap as $field => $meta) {
                    $raw = trim((string) ($recordSource[$field] ?? ''));
                    if ($raw === '') {
                        continue;
                    }

                    $record[$meta['enc']]    = $this->crypto->encrypt($raw);
                    $record[$meta['masked']] = $this->maskValue($raw, $meta['mask']);
                }

                if ($action === 'add' || ! $dependentId) {
                    $record['created_at'] = $now;
                    $record['created_by'] = $reviewerId;
                    $record['restored_at'] = null;
                    $record['restored_by'] = null;
                    $this->beneficiaryDependents->insert($record);
                } else {
                    $this->beneficiaryDependents->update($dependentId, $record);
                }
            }
        }
    }

    private function updateBeneficiarySummaryDraft(int $beneficiaryId, array $request): void
    {
        $this->beneficiaries->update($beneficiaryId, [
            'last_change_request_id' => $request['id'],
            'pending_change_request' => $request['status'] === 'pending' ? 1 : 0,
            'change_requests_submitted' => $this->countRequests($beneficiaryId),
            'last_request_submitted_at' => $request['requested_at'],
            'last_request_status'       => $request['status'],
            'last_request_reviewed_at'  => $request['reviewed_at'],
            'last_request_reviewer_id'  => $request['reviewed_by'],
            'last_request_comment'      => $request['review_comment'],
        ]);
    }

    private function updateBeneficiarySummaryPending(int $beneficiaryId, array $request): void
    {
        $update = [
            'last_change_request_id'   => $request['id'],
            'pending_change_request'   => 1,
            'change_requests_submitted'=> $this->countRequests($beneficiaryId),
            'last_request_submitted_at'=> $request['requested_at'],
            'last_request_status'      => $request['status'],
            'last_request_reviewed_at' => $request['reviewed_at'],
            'last_request_reviewer_id' => $request['reviewed_by'],
            'last_request_comment'     => $request['review_comment'],
        ];
        $this->beneficiaries->update($beneficiaryId, $update);
    }

    private function updateBeneficiarySummaryApproved(int $beneficiaryId, array $request): void
    {
        $counts = $this->countRequestStatuses($beneficiaryId);

        $update = [
            'last_change_request_id'    => $request['id'],
            'pending_change_request'    => 0,
            'change_requests_submitted' => $counts['total'],
            'change_requests_approved'  => $counts['approved'],
            'last_request_submitted_at' => $request['requested_at'],
            'last_request_status'       => $request['status'],
            'last_request_reviewed_at'  => $request['reviewed_at'],
            'last_request_reviewer_id'  => $request['reviewed_by'],
            'last_request_comment'      => $request['review_comment'],
        ];

        $this->beneficiaries->update($beneficiaryId, $update);
    }

    private function updateBeneficiarySummaryTerminal(int $beneficiaryId, array $request): void
    {
        $counts = $this->countRequestStatuses($beneficiaryId);
        $hasPending = $this->requests
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->where('status', 'pending')
            ->countAllResults();

        $update = [
            'last_change_request_id'    => $request['id'],
            'pending_change_request'    => $hasPending > 0 ? 1 : 0,
            'change_requests_submitted' => $counts['total'],
            'change_requests_approved'  => $counts['approved'],
            'last_request_submitted_at' => $request['requested_at'],
            'last_request_status'       => $request['status'],
            'last_request_reviewed_at'  => $request['reviewed_at'],
            'last_request_reviewer_id'  => $request['reviewed_by'],
            'last_request_comment'      => $request['review_comment'],
        ];

        $this->beneficiaries->update($beneficiaryId, $update);
    }

    private function decodeSummary(?string $summaryJson): array
    {
        if (empty($summaryJson)) {
            return [
                'beneficiary_changes' => 0,
                'dependent_adds'      => 0,
                'dependent_updates'   => 0,
                'dependent_removals'  => 0,
            ];
        }

        try {
            $decoded = json_decode($summaryJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            log_message('debug', '[ChangeRequest] Unable to decode summary: {message}', ['message' => $exception->getMessage()]);
            $decoded = [];
        }

        return array_merge([
            'beneficiary_changes' => 0,
            'dependent_adds'      => 0,
            'dependent_updates'   => 0,
            'dependent_removals'  => 0,
        ], is_array($decoded) ? $decoded : []);
    }

    private function diffBeneficiary(array $before, array $after): array
    {
        $diff = [];

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allKeys as $field) {
            $prev = $before[$field] ?? null;
            $next = $after[$field] ?? null;
            if ($prev !== $next) {
                $diff[$field] = [
                    'before' => $prev,
                    'after'  => $next,
                ];
            }
        }

        ksort($diff);

        return $diff;
    }

    private function applyBeneficiaryFallbacks(array $before, array $current): array
    {
        $aliases = [
            'ppo_number'      => 'ppo_number_masked',
            'pran_number'     => 'pran_number_masked',
            'gpf_number'      => 'gpf_number_masked',
            'bank_account'    => 'bank_account_masked',
            'aadhaar'         => 'aadhaar_masked',
            'pan'             => 'pan_masked',
            'samagra'         => 'samagra_masked',
            'primary_mobile'  => 'primary_mobile_masked',
            'alternate_mobile'=> 'alternate_mobile_masked',
        ];

        foreach ($aliases as $field => $alias) {
            $value = $before[$field] ?? null;
            if ($value !== null && $value !== '') {
                continue;
            }

            if (isset($current[$field]) && $current[$field] !== null && $current[$field] !== '') {
                $before[$field] = $current[$field];
                continue;
            }

            if (isset($current[$alias]) && $current[$alias] !== null && $current[$alias] !== '') {
                $before[$field] = $current[$alias];
            }
        }

        return $before;
    }

    private function loadDependentDiffs(int $requestId, array $before, array $after): array
    {
        $rows = $this->changeDependents
            ->where('change_request_id', $requestId)
            ->orderBy('order_index', 'ASC')
            ->findAll();

        if ($rows === []) {
            return $this->diffDependentsFallback($before, $after);
        }

        $diffs = [];
        foreach ($rows as $row) {
            $diffs[] = [
                'dependent_id'     => isset($row['dependent_id']) ? (int) $row['dependent_id'] : null,
                'action'           => $row['action'] ?? 'update',
                'relationship_key' => $row['relationship_key'] ?? null,
                'alive_status'     => $row['alive_status'] ?? null,
                'health_status'    => $row['health_status'] ?? null,
                'order'            => isset($row['order_index']) ? (int) $row['order_index'] : null,
                'before'           => $this->decodeDiffPayload($row['payload_before'] ?? null),
                'after'            => $this->decodeDiffPayload($row['payload_after'] ?? null),
            ];
        }

        return $diffs;
    }

    private function decodeDiffPayload(?string $payload): ?array
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            log_message('debug', '[ChangeRequest] Unable to decode dependent payload: {message}', ['message' => $exception->getMessage()]);
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Fallback diff when the change_dependents log isn't available (e.g. for legacy records).
     */
    private function diffDependentsFallback(array $before, array $after): array
    {
        $diffs = [];

        $beforeMap = $this->mapDependentsForDiff($before);
        $afterMap  = $this->mapDependentsForDiff($after);

        foreach ($beforeMap as $key => $row) {
            $isRemoved = ! isset($afterMap[$key]) || ! empty($afterMap[$key]['is_deleted']);
            if ($isRemoved) {
                $diffs[] = [
                    'action'       => 'remove',
                    'dependent_id' => $row['id'] ?? null,
                    'before'       => $row,
                    'after'        => null,
                ];
            }
        }

        foreach ($afterMap as $key => $row) {
            if (! empty($row['is_deleted'])) {
                $original = $beforeMap[$key] ?? null;
                $diffs[] = [
                    'action'       => 'remove',
                    'dependent_id' => $original['id'] ?? null,
                    'before'       => $original,
                    'after'        => null,
                ];
                continue;
            }

            if (str_starts_with($key, 'temp:') && empty($row['id'])) {
                $diffs[] = [
                    'action'       => 'add',
                    'dependent_id' => null,
                    'before'       => null,
                    'after'        => $row,
                ];
                continue;
            }

            $original = $beforeMap[$key] ?? null;
            if ($original === null) {
                $diffs[] = [
                    'action'       => 'add',
                    'dependent_id' => $row['id'] ?? null,
                    'before'       => null,
                    'after'        => $row,
                ];
                continue;
            }

            if ($this->dependentRowsEqual($original, $row)) {
                continue;
            }

            $diffs[] = [
                'action'       => 'update',
                'dependent_id' => $row['id'] ?? $original['id'] ?? null,
                'before'       => $original,
                'after'        => $row,
            ];
        }

        return $diffs;
    }

    private function buildDependentItems(int $requestId, array $diff, string $timestamp, int $position): array
    {
        $items      = [];
        $action     = $diff['action'] ?? 'update';
        $before     = $diff['before'] ?? null;
        $after      = $diff['after'] ?? null;
        $identifier = $this->dependentIdentifier($diff, $position);
        $labelBase  = $this->describeDependent($after ?? $before ?? []);

        if ($action === 'add') {
            $items[] = [
                'change_request_id' => $requestId,
                'entity_type'       => 'dependent',
                'entity_identifier' => $identifier,
                'field_key'         => 'dependent:add',
                'field_label'       => 'Add dependent – ' . $labelBase,
                'old_value'         => null,
                'new_value'         => $this->serializeValue($after),
                'status'            => 'pending',
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
            ];

            return $items;
        }

        if ($action === 'remove') {
            $items[] = [
                'change_request_id' => $requestId,
                'entity_type'       => 'dependent',
                'entity_identifier' => $identifier,
                'field_key'         => 'dependent:remove',
                'field_label'       => 'Remove dependent – ' . $labelBase,
                'old_value'         => $this->serializeValue($before),
                'new_value'         => null,
                'status'            => 'pending',
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
            ];

            return $items;
        }

        $changes = $diff['changes'] ?? $this->diffFlatArray($before ?? [], $after ?? []);
        foreach ($changes as $field => $change) {
            $items[] = [
                'change_request_id' => $requestId,
                'entity_type'       => 'dependent',
                'entity_identifier' => $identifier,
                'field_key'         => sprintf('dependent:%s:%s', $identifier, $field),
                'field_label'       => sprintf('%s – %s', $labelBase, $this->formatFieldLabel($field)),
                'old_value'         => $this->serializeValue($change['before'] ?? null),
                'new_value'         => $this->serializeValue($change['after'] ?? null),
                'status'            => 'pending',
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
            ];
        }

        return $items;
    }

    private function dependentIdentifier(array $diff, int $position): string
    {
        $before = $diff['before'] ?? [];
        $after  = $diff['after'] ?? [];

        if (! empty($diff['dependent_id'])) {
            return (string) $diff['dependent_id'];
        }

        if (! empty($before['id'])) {
            return (string) $before['id'];
        }

        if (! empty($after['id'])) {
            return (string) $after['id'];
        }

        if (! empty($after['temp_id'])) {
            return (string) $after['temp_id'];
        }

        return 'dep_' . $position;
    }

    private function describeDependent(array $row): string
    {
        $parts = [];
        if (! empty($row['relationship'])) {
            $parts[] = ucwords(str_replace('_', ' ', strtolower((string) $row['relationship'])));
        }
        if (! empty($row['first_name'])) {
            $parts[] = $row['first_name'];
        }
        if (! empty($row['last_name'])) {
            $parts[] = $row['last_name'];
        }

        $label = trim(implode(' ', $parts));

        return $label !== '' ? $label : 'Dependent';
    }

    private function formatFieldLabel(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    private function serializeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    private function diffFlatArray(array $before, array $after): array
    {
        $diff = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($keys as $key) {
            $prev = $before[$key] ?? null;
            $next = $after[$key] ?? null;
            if ($prev !== $next) {
                $diff[$key] = [
                    'before' => $prev,
                    'after'  => $next,
                ];
            }
        }

        return $diff;
    }

    private function mapDependentsForDiff(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = ! empty($row['id'])
                ? 'id:' . $row['id']
                : ('temp:' . ($row['temp_id'] ?? spl_object_id((object) $row)));
            $map[$key] = $row;
        }

        return $map;
    }

    private function requireItem(int $requestId, int $itemId): array
    {
        $item = $this->changeItems
            ->where('change_request_id', $requestId)
            ->find($itemId);

        if (! $item) {
            throw new RuntimeException('Change item not found.');
        }

        return $item;
    }

    private function dependentRowsEqual(array $a, array $b): bool
    {
        return $this->normalizeDependentForComparison($a) === $this->normalizeDependentForComparison($b);
    }

    private function normalizeDependentForComparison(array $row): array
    {
        $normalized = $row;
        unset($normalized['temp_id'], $normalized['is_deleted']);

        if (isset($normalized['date_of_birth']) && $normalized['date_of_birth'] === '') {
            $normalized['date_of_birth'] = null;
        }

        if (isset($normalized['blood_group_id']) && $normalized['blood_group_id'] === '') {
            $normalized['blood_group_id'] = null;
        }

        return $normalized;
    }

    private function countRequests(int $beneficiaryId): int
    {
        return $this->requests
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->countAllResults();
    }

    private function listCacheKey(int $beneficiaryId): string
    {
        // Cache handlers reject reserved characters like ":" so use a safe delimiter
        return 'beneficiary_change_requests_list_' . $beneficiaryId;
    }

    private function clearListCache(int $beneficiaryId): void
    {
        cache()->delete($this->listCacheKey($beneficiaryId));
    }

    private function dispatchEvent(string $type, array $requestRow, ?int $actorId = null, ?array $context = null): void
    {
        if (empty($requestRow['id'])) {
            return;
        }

        Events::trigger('change-request', new ChangeRequestEvent(
            $type,
            (int) $requestRow['id'],
            (int) ($requestRow['beneficiary_v2_id'] ?? 0),
            $actorId,
            $context
        ));
    }

    private function countRequestStatuses(int $beneficiaryId): array
    {
        $builder = $this->requests
            ->select('status, COUNT(*) as total')
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->groupBy('status')
            ->findAll();

        $totals = ['total' => 0, 'approved' => 0];

        foreach ($builder as $row) {
            $totals['total'] += (int) $row['total'];
            if ($row['status'] === 'approved') {
                $totals['approved'] += (int) $row['total'];
            }
        }

        return $totals;
    }

    private function nextSubmissionNumber(int $beneficiaryId): int
    {
        $last = $this->requests
            ->selectMax('submission_no')
            ->where('beneficiary_v2_id', $beneficiaryId)
            ->get()
            ->getRowArray();

        return (int) ($last['submission_no'] ?? 0) + 1;
    }

    private function decodePayload(?string $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    private function requireRequest(int $requestId): array
    {
        $request = $this->requests->find($requestId);
        if (! $request) {
            throw new RuntimeException('Change request not found.');
        }

        return $request;
    }

    private function maskValue(string $value, string $mode): string
    {
        return $mode === 'alnum'
            ? $this->maskAlphaNumeric($value)
            : $this->maskDigits($value);
    }

    private function maskDigits(?string $value, int $visible = 4): ?string
    {
        if ($value === null) {
            return null;
        }

        $value  = preg_replace('/\s+/', '', (string) $value);
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }

        return str_repeat('X', max(0, $length - $visible)) . substr($value, -$visible);
    }

    private function maskAlphaNumeric(?string $value, int $visible = 4): ?string
    {
        if ($value === null) {
            return null;
        }

        $value  = preg_replace('/\s+/', '', (string) $value);
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }

        return str_repeat('X', max(0, $length - $visible)) . substr($value, -$visible);
    }
}
