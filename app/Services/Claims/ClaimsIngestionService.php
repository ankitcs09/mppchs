<?php

namespace App\Services\Claims;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Claims as ClaimsConfig;
use RuntimeException;

class ClaimsIngestionService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ClaimsConfig $config
    ) {
    }

    /**
     * Ingests a batch of claims sent by the TPA/ISA.
     *
     * @return array Structured response summarising the outcome per claim.
     */
    public function ingestBatch(array $payload, array $context = []): array
    {
        $claims = $payload['claims'] ?? [];
        if (! is_array($claims) || $claims === []) {
            throw new RuntimeException('Payload does not contain any claims.');
        }

        $batchReference = $payload['batch_reference'] ?? null;
        $results = [
            'batch_reference' => $batchReference,
            'received'        => count($claims),
            'success'         => 0,
            'failed'          => 0,
            'claims'          => [],
        ];

        foreach ($claims as $index => $claimPayload) {
            $documentsExpected = is_array($claimPayload['documents'] ?? null)
                ? count(array_filter($claimPayload['documents'], static fn ($doc) => is_array($doc)))
                : 0;

            $claimResult = [
                'index'           => $index,
                'reference'       => $claimPayload['reference'] ?? null,
                'status'          => 'failed',
                'message'         => null,
                'claim_id'        => null,
                'created'         => false,
                'updated'         => false,
                'events_ingested' => 0,
                'documents_ingested' => 0,
                'documents_expected' => $documentsExpected,
                'document_stats'     => [
                    'attempted' => $documentsExpected,
                    'inserted'  => 0,
                    'updated'   => 0,
                    'failed'    => 0,
                    'messages'  => [],
                ],
            ];

            try {
                $outcome = $this->processSingleClaim($claimPayload, $batchReference);
                $claimResult = array_merge($claimResult, $outcome, ['status' => 'success']);
                $results['success']++;
            } catch (RuntimeException $exception) {
                $claimResult['message'] = $exception->getMessage();
                if ($documentsExpected > 0) {
                    $claimResult['document_stats']['failed'] = $documentsExpected;
                    $claimResult['document_stats']['messages'][] = 'Documents supplied but claim failed before ingest.';
                }
                $results['failed']++;
            } catch (DatabaseException $exception) {
                $claimResult['message'] = 'Database error: ' . $exception->getMessage();
                if ($documentsExpected > 0) {
                    $claimResult['document_stats']['failed'] = $documentsExpected;
                    $claimResult['document_stats']['messages'][] = 'Documents supplied but database error prevented ingest.';
                }
                $results['failed']++;
            } catch (\Throwable $exception) {
                $claimResult['message'] = 'Unexpected error: ' . $exception->getMessage();
                if ($documentsExpected > 0) {
                    $claimResult['document_stats']['failed'] = $documentsExpected;
                    $claimResult['document_stats']['messages'][] = 'Documents supplied but unexpected error prevented ingest.';
                }
                $results['failed']++;
            }

            $results['claims'][] = $claimResult;
        }

        $this->logBatchOutcome($payload, $context, $results);

        return $results;
    }

    /**
     * Processes a single claim record. Returns details including whether the claim was created or updated.
     */
    private function processSingleClaim(array $payload, ?string $batchReference = null): array
    {
        $reference = $this->sanitizeString($payload['reference'] ?? '');
        if ($reference === '') {
            throw new RuntimeException('Claim reference is required.');
        }

        $company = $this->lookupCompany($payload);
        $beneficiary = $this->lookupBeneficiary($payload);
        if (! $beneficiary) {
            throw new RuntimeException('Beneficiary could not be resolved.');
        }

        $claimType = $this->lookupClaimType($payload);
        $status    = $this->lookupStatus($payload);

        $dependentId = $this->lookupDependent($payload, $beneficiary['id']);
        $policyCardId = $this->resolvePolicyCard($payload, $beneficiary['id'], $company['id'] ?? null);

        $claimBuilder = $this->db->table('claims');
        $existingClaim = $claimBuilder->where('claim_reference', $reference)->get()->getRowArray();

        $now = utc_now();
        $receivedAt = $this->parseDateTime($payload['dates']['received'] ?? $payload['received_at'] ?? null) ?? $now;
        $claimDate  = $this->parseDate($payload['dates']['claim'] ?? $payload['claim_date'] ?? null);
        $admission  = $this->parseDate($payload['dates']['admission'] ?? $payload['admission_date'] ?? null);
        $discharge  = $this->parseDate($payload['dates']['discharge'] ?? $payload['discharge_date'] ?? null);

        $claimData = [
            'company_id'         => $company['id'] ?? null,
            'beneficiary_id'     => $beneficiary['id'],
            'dependent_id'       => $dependentId,
            'policy_card_id'     => $policyCardId,
            'claim_type_id'      => $claimType['id'] ?? null,
            'status_id'          => $status['id'] ?? null,
            'claim_reference'    => $reference,
            'external_reference' => $this->sanitizeString($payload['external_reference'] ?? $payload['tpa_reference'] ?? null),
            'claim_category'     => $this->sanitizeString($payload['category'] ?? null),
            'claim_sub_status'   => $this->sanitizeString($payload['sub_status'] ?? null),
            'claim_date'         => $claimDate,
            'admission_date'     => $admission,
            'discharge_date'     => $discharge,
            'claimed_amount'     => $this->parseAmount($payload['amounts']['claimed'] ?? $payload['claimed_amount'] ?? null),
            'approved_amount'    => $this->parseAmount($payload['amounts']['approved'] ?? $payload['approved_amount'] ?? null),
            'cashless_amount'    => $this->parseAmount($payload['amounts']['cashless'] ?? $payload['cashless_amount'] ?? null),
            'copay_amount'       => $this->parseAmount($payload['amounts']['copay'] ?? $payload['copay_amount'] ?? null),
            'non_payable_amount' => $this->parseAmount($payload['amounts']['non_payable'] ?? $payload['non_payable_amount'] ?? null),
            'reimbursed_amount'  => $this->parseAmount($payload['amounts']['reimbursed'] ?? $payload['reimbursed_amount'] ?? null),
            'hospital_name'      => $this->sanitizeString($payload['hospital']['name'] ?? $payload['hospital_name'] ?? null),
            'hospital_code'      => $this->sanitizeString($payload['hospital']['code'] ?? $payload['hospital_code'] ?? null),
            'hospital_city'      => $this->sanitizeString($payload['hospital']['city'] ?? $payload['hospital_city'] ?? null),
            'hospital_state'     => $this->sanitizeString($payload['hospital']['state'] ?? $payload['hospital_state'] ?? null),
            'diagnosis'          => $this->sanitizeString($payload['diagnosis'] ?? null),
            'remarks'            => $this->sanitizeString($payload['remarks'] ?? null),
            'source'             => $this->sanitizeString($payload['source'] ?? 'tpa') ?: 'tpa',
            'source_reference'   => $this->sanitizeString($payload['source_reference'] ?? $batchReference ?? null),
            'received_at'        => $receivedAt,
            'last_synced_at'     => $now,
            'payload'            => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ];

        $claimId = null;
        $created = false;
        $updated = false;

        $this->db->transException(true)->transStart();

        if ($existingClaim) {
            $claimId = (int) $existingClaim['id'];
            $claimData['updated_at'] = $now;
            $claimBuilder->where('id', $claimId)->update($claimData);
            $updated = true;
        } else {
            $claimData['created_at'] = $now;
            $claimBuilder->insert($claimData);
            $claimId = (int) $this->db->insertID();
            $created = true;
        }

        $eventsIngested = $this->ingestEvents($claimId, $payload['events'] ?? [], $status);
        $documentStats  = $this->ingestDocuments($claimId, $payload['documents'] ?? []);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Failed to persist claim transaction.');
        }

        return [
            'message'            => $created ? 'Claim created.' : 'Claim updated.',
            'claim_id'           => $claimId,
            'created'            => $created,
            'updated'            => $updated,
            'events_ingested'    => $eventsIngested,
            'documents_ingested' => ($documentStats['inserted'] ?? 0) + ($documentStats['updated'] ?? 0),
            'documents_expected' => $documentStats['attempted'] ?? 0,
            'document_stats'     => $documentStats,
            'company_id'         => $company['id'] ?? null,
            'company_code'       => $company['code'] ?? null,
            'beneficiary_id'     => $beneficiary['id'],
        ];
    }

    private function ingestEvents(int $claimId, mixed $events, ?array $status): int
    {
        if (! is_array($events) || $events === []) {
            if ($status !== null) {
                $events = [[
                    'status_code'  => $status['code'],
                    'event_code'   => 'status:' . $status['code'],
                    'event_label'  => $status['label'] ?? $status['code'],
                    'event_time'   => utc_now(),
                    'description'  => 'Auto-generated from claim payload.',
                    'source'       => 'ingest',
                ]];
            } else {
                return 0;
            }
        }

        $builder = $this->db->table('claim_events');
        $ingested = 0;

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $eventTime = $this->parseDateTime($event['event_time'] ?? null) ?? utc_now();
            $eventCode = $this->sanitizeString($event['event_code'] ?? null);

            // Skip duplicate events by claim + code + timestamp.
            $duplicate = $builder->select('id')
                ->where('claim_id', $claimId)
                ->where('event_time', $eventTime)
                ->where('event_code', $eventCode)
                ->get(1)
                ->getRowArray();

            if ($duplicate) {
                continue;
            }

            $statusRow = null;
            $statusCode = $event['status_code'] ?? $event['code'] ?? null;
            if ($statusCode) {
                $statusRow = $this->lookupStatus(['status_code' => $statusCode], true);
            }

            $insert = [
                'claim_id'   => $claimId,
                'status_id'  => $statusRow['id'] ?? null,
                'event_code' => $eventCode,
                'event_label'=> $this->sanitizeString($event['event_label'] ?? $event['name'] ?? null),
                'description'=> $this->sanitizeString($event['description'] ?? null),
                'event_time' => $eventTime,
                'source'     => $this->sanitizeString($event['source'] ?? 'ingest'),
                'payload'    => json_encode($event, JSON_UNESCAPED_UNICODE),
                'created_at' => utc_now(),
                'updated_at' => utc_now(),
            ];

            $builder->insert($insert);
            $ingested++;
        }

        return $ingested;
    }

    private function ingestDocuments(int $claimId, mixed $documents): array
    {
        $stats = [
            'attempted' => 0,
            'inserted'  => 0,
            'updated'   => 0,
            'failed'    => 0,
            'messages'  => [],
        ];

        if (! is_array($documents) || $documents === []) {
            return $stats;
        }

        $builder = $this->db->table('claim_documents');

        foreach ($documents as $index => $document) {
            if (! is_array($document)) {
                continue;
            }

            $stats['attempted']++;

            $storagePath = $this->sanitizeString($document['storage_path'] ?? null);
            if ($storagePath === null) {
                $stats['failed']++;
                $stats['messages'][] = sprintf('Document %d missing storage_path', $index);
                continue;
            }

            try {
                $existing = $builder->select('id')
                    ->where('claim_id', $claimId)
                    ->where('storage_path', $storagePath)
                    ->get(1)
                    ->getRowArray();

                $documentType = $this->lookupDocumentType($document);

                $data = [
                    'claim_id'         => $claimId,
                    'document_type_id' => $documentType['id'] ?? null,
                    'title'            => $this->sanitizeString($document['title'] ?? 'Supporting Document'),
                    'storage_disk'     => $this->sanitizeString($document['storage_disk'] ?? 'ftp') ?: 'ftp',
                    'storage_path'     => $storagePath,
                    'checksum'         => strtoupper($this->sanitizeString($document['checksum'] ?? '') ?? ''),
                    'mime_type'        => $this->sanitizeString($document['mime_type'] ?? null),
                    'file_size'        => $document['file_size'] ?? null,
                    'source'           => $this->sanitizeString($document['source'] ?? 'ingest'),
                    'uploaded_at'      => $this->parseDateTime($document['uploaded_at'] ?? null) ?? utc_now(),
                    'metadata'         => json_encode($document, JSON_UNESCAPED_UNICODE),
                    'updated_at'       => utc_now(),
                ];

                if ($existing) {
                    $builder->where('id', $existing['id'])->update($data);
                    $stats['updated']++;
                } else {
                    $data['created_at'] = utc_now();
                    $builder->insert($data);
                    $stats['inserted']++;
                }
            } catch (\Throwable $exception) {
                $stats['failed']++;
                $stats['messages'][] = sprintf(
                    'Document %d (%s) failed: %s',
                    $index,
                    $document['title'] ?? $storagePath,
                    $exception->getMessage()
                );
                log_message('error', '[ClaimsIngest] Failed to ingest document for claim #{claimId}: {message}', [
                    'claimId' => $claimId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    private function lookupCompany(array $payload): array
    {
        $code = $this->sanitizeString($payload['company_code'] ?? $payload['company'] ?? null);
        if ($code === null) {
            return ['id' => null];
        }

        $row = $this->db->table('companies')
            ->select('id, code, name')
            ->where('code', strtoupper($code))
            ->get(1)
            ->getRowArray();

        if (! $row) {
            throw new RuntimeException("Unknown company code: {$code}");
        }

        return $row;
    }

    private function lookupBeneficiary(array $payload): ?array
    {
        $beneficiaries = $this->db->table('beneficiaries_v2');

        if (! empty($payload['beneficiary_id'])) {
            $beneficiaries->where('id', (int) $payload['beneficiary_id']);
        } elseif (! empty($payload['beneficiary_reference'])) {
            $beneficiaries->where('reference_number', $payload['beneficiary_reference']);
        } else {
            return null;
        }

        return $beneficiaries->select('id, reference_number')->get(1)->getRowArray();
    }

    private function lookupDependent(array $payload, int $beneficiaryId): ?int
    {
        if (empty($payload['dependent_id'])) {
            return null;
        }

        $row = $this->db->table('beneficiary_dependents_v2')
            ->select('id')
            ->where('id', (int) $payload['dependent_id'])
            ->where('beneficiary_id', $beneficiaryId)
            ->get(1)
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    private function lookupClaimType(array $payload, bool $silent = false): ?array
    {
        $code = $this->sanitizeString($payload['claim_type'] ?? $payload['claim_type_code'] ?? $payload['type'] ?? null);
        if ($code === null) {
            if ($silent) {
                return null;
            }
            throw new RuntimeException('Claim type code missing.');
        }

        $row = $this->db->table('claim_types')
            ->select('id, code, label')
            ->where('code', strtolower($code))
            ->get(1)
            ->getRowArray();

        if (! $row) {
            if ($silent) {
                return null;
            }

            throw new RuntimeException("Unknown claim type code: {$code}");
        }

        return $row;
    }

    private function lookupStatus(array $payload, bool $silent = false): ?array
    {
        $code = $this->sanitizeString($payload['status_code'] ?? $payload['status'] ?? null);
        if ($code === null) {
            if ($silent) {
                return null;
            }
            throw new RuntimeException('Claim status code missing.');
        }

        $row = $this->db->table('claim_statuses')
            ->select('id, code, label')
            ->where('code', strtolower($code))
            ->get(1)
            ->getRowArray();

        if (! $row) {
            if ($silent) {
                return null;
            }

            throw new RuntimeException("Unknown claim status code: {$code}");
        }

        return $row;
    }

    private function lookupDocumentType(array $payload): ?array
    {
        $code = $this->sanitizeString($payload['type_code'] ?? $payload['document_type'] ?? null);
        if ($code === null) {
            return null;
        }

        return $this->db->table('claim_document_types')
            ->select('id, code, label')
            ->where('code', strtolower($code))
            ->get(1)
            ->getRowArray();
    }

    private function resolvePolicyCard(array $payload, int $beneficiaryId, ?int $companyId): ?int
    {
        $cardNumber = $this->sanitizeString($payload['policy']['card_number'] ?? $payload['policy_card_number'] ?? null);
        $policyNumber = $this->sanitizeString($payload['policy']['policy_number'] ?? $payload['policy_number'] ?? null);
        $program = $this->sanitizeString($payload['policy']['program'] ?? $payload['policy_program'] ?? null);
        $provider = $this->sanitizeString($payload['policy']['provider'] ?? $payload['policy_provider'] ?? null);

        if ($cardNumber === null && $policyNumber === null) {
            return null;
        }

        $builder = $this->db->table('beneficiary_policy_cards')
            ->select('id')
            ->where('beneficiary_id', $beneficiaryId);

        if ($cardNumber !== null) {
            $builder->where('card_number', $cardNumber);
        } elseif ($policyNumber !== null) {
            $builder->where('policy_number', $policyNumber);
        }

        $existing = $builder->get(1)->getRowArray();
        $now = utc_now();

        if ($existing) {
            $update = [
                'policy_number'  => $policyNumber,
                'card_number'    => $cardNumber,
                'policy_program' => $program,
                'policy_provider'=> $provider,
                'updated_at'     => $now,
            ];
            $this->db->table('beneficiary_policy_cards')
                ->where('id', $existing['id'])
                ->update($update);
            return (int) $existing['id'];
        }

        $insert = [
            'company_id'      => $companyId,
            'beneficiary_id'  => $beneficiaryId,
            'policy_number'   => $policyNumber,
            'card_number'     => $cardNumber,
            'policy_program'  => $program,
            'policy_provider' => $provider,
            'status'          => 'active',
            'metadata'        => json_encode($payload['policy'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at'      => $now,
            'updated_at'      => $now,
        ];

        $this->db->table('beneficiary_policy_cards')->insert($insert);
        return (int) $this->db->insertID();
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
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

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function logBatchOutcome(array $payload, array $context, array $results): void
    {
        if (! $this->db->tableExists('claim_ingest_batches')) {
            return;
        }

        $notes = [];
        $companyIds = [];
        $itemsMeta = [];
        $documentTotals = [
            'attempted' => 0,
            'ingested'  => 0,
            'updated'   => 0,
            'failed'    => 0,
            'missing'   => 0,
            'partial'   => 0,
        ];
        $documentMatrix = [
            'record_ok_doc_ok'        => 0,
            'record_ok_doc_partial'   => 0,
            'record_ok_doc_failed'    => 0,
            'record_ok_doc_missing'   => 0,
            'record_fail_doc_ok'      => 0,
            'record_fail_doc_partial' => 0,
            'record_fail_doc_failed'  => 0,
            'record_fail_doc_missing' => 0,
        ];

        foreach ($results['claims'] ?? [] as $claim) {
            if (isset($claim['company_id'])) {
                $companyIds[] = (int) $claim['company_id'];
            }

            $claimStatus   = $claim['status'] ?? 'failed';
            $recordSuccess = $claimStatus === 'success';

            if ($claimStatus !== 'success' && ! empty($claim['message'])) {
                $reference = $claim['reference'] ?? ($claim['claim_id'] ?? 'unknown');
                $notes[] = trim((string) $reference) . ': ' . $claim['message'];
            }

            $docStats = $claim['document_stats'] ?? [
                'attempted' => $claim['documents_expected'] ?? 0,
                'inserted'  => 0,
                'updated'   => 0,
                'failed'    => 0,
                'messages'  => [],
            ];
            $docAttempted = (int) ($docStats['attempted'] ?? 0);
            $docInserted  = (int) ($docStats['inserted'] ?? 0);
            $docUpdated   = (int) ($docStats['updated'] ?? 0);
            $docFailed    = (int) ($docStats['failed'] ?? 0);
            $docMessages  = $docStats['messages'] ?? [];
            $docSuccesses = $docInserted + $docUpdated;
            $documentState = 'missing';

            $documentTotals['attempted'] += $docAttempted;
            $documentTotals['ingested']  += $docInserted;
            $documentTotals['updated']   += $docUpdated;
            $documentTotals['failed']    += $docFailed;

            if ($docAttempted === 0) {
                $documentTotals['missing']++;
                if ($recordSuccess) {
                    $documentMatrix['record_ok_doc_missing']++;
                } else {
                    $documentMatrix['record_fail_doc_missing']++;
                }
            } elseif ($docFailed === 0 && $docSuccesses > 0) {
                $documentState = 'ok';
                if ($recordSuccess) {
                    $documentMatrix['record_ok_doc_ok']++;
                } else {
                    $documentMatrix['record_fail_doc_ok']++;
                }
            } elseif ($docSuccesses > 0 && $docFailed > 0) {
                $documentTotals['partial']++;
                $documentState = 'partial';
                if ($recordSuccess) {
                    $documentMatrix['record_ok_doc_partial']++;
                } else {
                    $documentMatrix['record_fail_doc_partial']++;
                }
            } else {
                $documentState = 'failed';
                if ($recordSuccess) {
                    $documentMatrix['record_ok_doc_failed']++;
                } else {
                    $documentMatrix['record_fail_doc_failed']++;
                }
            }

            if (in_array($documentState, ['failed', 'partial'], true)) {
                $reference = $claim['reference'] ?? ($claim['claim_id'] ?? 'unknown');
                $stateLabel = $documentState === 'failed' ? 'document ingest failed' : 'partial document ingest';
                $notes[] = trim((string) $reference) . ': ' . $stateLabel;
                foreach (array_slice($docMessages, 0, 2) as $message) {
                    $notes[] = trim((string) $message);
                }
            }

            $itemsMeta[] = [
                'reference'          => $claim['reference'] ?? null,
                'status'             => $claimStatus,
                'message'            => $claim['message'] ?? null,
                'claim_id'           => $claim['claim_id'] ?? null,
                'company_id'         => $claim['company_id'] ?? null,
                'company_code'       => $claim['company_code'] ?? null,
                'beneficiary_id'     => $claim['beneficiary_id'] ?? null,
                'created'            => $claim['created'] ?? null,
                'updated'            => $claim['updated'] ?? null,
                'events_ingested'    => $claim['events_ingested'] ?? 0,
                'documents_ingested' => $claim['documents_ingested'] ?? 0,
                'documents_expected' => $claim['documents_expected'] ?? $docAttempted,
                'documents'          => [
                    'attempted' => $docAttempted,
                    'inserted'  => $docInserted,
                    'updated'   => $docUpdated,
                    'failed'    => $docFailed,
                    'state'     => $documentState,
                    'messages'  => array_slice($docMessages, 0, 5),
                ],
            ];
        }

        $notes = array_slice($notes, 0, 10);

        $uniqueCompanyIds = array_values(array_unique(array_filter($companyIds, static fn ($id) => $id !== null)));

        $metadata = [
            'items'          => array_slice($itemsMeta, 0, 200),
            'summary'        => [
                'received' => $results['received'] ?? 0,
                'success'  => $results['success'] ?? 0,
                'failed'   => $results['failed'] ?? 0,
                'documents'=> [
                    'totals' => $documentTotals,
                    'matrix' => $documentMatrix,
                ],
            ],
            'batch_reference' => $results['batch_reference'] ?? ($payload['batch_reference'] ?? null),
        ];

        $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
        if ($metadataJson !== false && strlen($metadataJson) > 65535) {
            $metadataJson = substr($metadataJson, 0, 65535);
        }

        $data = [
            'batch_reference' => $results['batch_reference'] ?? ($payload['batch_reference'] ?? null),
            'claims_received' => $results['received'] ?? 0,
            'claims_success'  => $results['success'] ?? 0,
            'claims_failed'   => $results['failed'] ?? 0,
            'requested_ip'    => $context['source_ip'] ?? null,
            'user_agent'      => $context['user_agent'] ?? null,
            'notes'           => $notes ? substr(implode('; ', $notes), 0, 2000) : null,
            'company_ids'     => $uniqueCompanyIds ? implode(',', $uniqueCompanyIds) : null,
            'processed_at'    => utc_now(),
            'created_at'      => utc_now(),
            'metadata'        => $metadataJson ?: null,
        ];

        try {
            $this->db->table('claim_ingest_batches')->insert($data);
        } catch (\Throwable $exception) {
            log_message('warning', '[ClaimsIngest] Failed to log batch outcome: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
