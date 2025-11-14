<?php

namespace Tests\Unit\Services;

use App\Services\Claims\ClaimsIngestionService;
use Config\Claims;
use Config\Database;
use Tests\Support\EnrollmentTestCase;

class ClaimsIngestionServiceTest extends EnrollmentTestCase
{
    private ClaimsIngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ClaimsIngestionService(
            Database::connect($this->DBGroup),
            config(Claims::class)
        );
    }

    public function testIngestCreatesClaimWithEventsAndDocuments(): void
    {
        $beneficiary = $this->createBeneficiary();

        $payload = [
            'batch_reference' => 'TEST-BATCH-001',
            'claims' => [
                [
                    'reference'             => 'CLM-TEST-0001',
                    'beneficiary_reference' => $beneficiary['reference_number'],
                    'claim_type'            => 'cashless',
                    'company_code'          => 'MPPGCL',
                    'status_code'           => 'approved',
                    'amounts'               => [
                        'claimed'  => 12500,
                        'approved' => 10000,
                        'cashless' => 9500,
                        'copay'    => 500,
                    ],
                    'dates' => [
                        'claim'     => '2025-01-15',
                        'admission' => '2025-01-10',
                        'discharge' => '2025-01-14',
                    ],
                    'hospital' => [
                        'name'  => 'Unit Test Hospital',
                        'city'  => 'Bhopal',
                        'state' => 'Madhya Pradesh',
                    ],
                    'documents' => [
                        [
                            'title'         => 'Discharge Summary',
                            'type_code'     => 'discharge_summary',
                            'storage_disk'  => 'local',
                            'storage_path'  => 'samples/unittest.pdf',
                            'checksum'      => 'ABC123',
                            'mime_type'     => 'application/pdf',
                        ],
                    ],
                    'events' => [
                        [
                            'status_code'  => 'registered',
                            'event_code'   => 'registered',
                            'event_label'  => 'Claim Registered',
                            'event_time'   => '2025-01-15 09:00:00',
                            'description'  => 'Received from TPA',
                        ],
                        [
                            'status_code'  => 'approved',
                            'event_code'   => 'approved',
                            'event_label'  => 'Claim Approved',
                            'event_time'   => '2025-01-16 14:30:00',
                        ],
                    ],
                ],
            ],
        ];

        $context = ['source_ip' => '127.0.0.1', 'user_agent' => 'PHPUnit'];
        $result = $this->service->ingestBatch($payload, $context);
        $this->assertSame('success', $result['claims'][0]['status'] ?? 'failed', print_r($result, true));

        $this->assertSame(1, $result['success']);
        $claimResult = $result['claims'][0];
        $this->assertSame('success', $claimResult['status']);
        $this->assertTrue($claimResult['created']);
        $this->assertSame(1, $claimResult['documents_expected']);
        $this->assertSame(1, $claimResult['documents_ingested']);
        $this->assertSame(1, $claimResult['document_stats']['attempted'] ?? 0);
        $this->assertSame(1, $claimResult['document_stats']['inserted'] ?? 0);
        $this->assertSame(0, $claimResult['document_stats']['failed'] ?? 0);
        $db = Database::connect($this->DBGroup);

        $claimRow = $db->table('claims')->where('claim_reference', 'CLM-TEST-0001')->get()->getRowArray();
        $this->assertNotNull($claimRow);
        $this->assertSame('Unit Test Hospital', $claimRow['hospital_name']);
        $this->assertSame('9500.00', $claimRow['cashless_amount']);

        $events = $db->table('claim_events')->where('claim_id', $claimRow['id'])->orderBy('event_time', 'ASC')->get()->getResultArray();
        $this->assertCount(2, $events);
        $this->assertSame('Claim Registered', $events[0]['event_label']);
        $this->assertSame('Claim Approved', $events[1]['event_label']);

        $documents = $db->table('claim_documents')->where('claim_id', $claimRow['id'])->get()->getResultArray();
        $this->assertCount(1, $documents);
        $this->assertSame('samples/unittest.pdf', $documents[0]['storage_path']);

        $batch = $db->table('claim_ingest_batches')->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertNotNull($batch);
        $this->assertSame(1, (int) $batch['claims_success']);
        $this->assertSame(0, (int) $batch['claims_failed']);
        $metadata = json_decode($batch['metadata'], true);
        $this->assertSame(1, $metadata['summary']['documents']['totals']['attempted'] ?? 0);
        $this->assertSame(1, $metadata['summary']['documents']['matrix']['record_ok_doc_ok'] ?? 0);
    }

    public function testIngestFailsWhenBeneficiaryMissing(): void
    {
        $payload = [
            'batch_reference' => 'TEST-BATCH-002',
            'claims' => [
                [
                    'reference'             => 'CLM-TEST-0002',
                    'beneficiary_reference' => 'NONEXISTENT',
                    'claim_type'            => 'cashless',
                    'company_code'          => 'MPPGCL',
                    'status_code'           => 'approved',
                ],
            ],
        ];

        $context = ['source_ip' => '127.0.0.1', 'user_agent' => 'PHPUnit'];
        $result = $this->service->ingestBatch($payload, $context);

        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('Beneficiary could not be resolved', $result['claims'][0]['message'] ?? '');
        $this->assertSame(0, $result['claims'][0]['documents_expected']);
        $this->assertSame(0, $result['claims'][0]['document_stats']['attempted'] ?? 0);
        $this->assertSame(0, $result['claims'][0]['document_stats']['failed'] ?? 0);

        $db = Database::connect($this->DBGroup);

        $batch = $db->table('claim_ingest_batches')->orderBy('id', 'DESC')->get(1)->getRowArray();
        $this->assertNotNull($batch);
        $this->assertSame(0, (int) $batch['claims_success']);
        $this->assertSame(1, (int) $batch['claims_failed']);

        $this->assertSame(0, (int) $db->table('claims')->countAllResults());
    }

    public function testIngestUpdatesExistingClaim(): void
    {
        $beneficiary = $this->createBeneficiary();

        $initialPayload = [
            'batch_reference' => 'TEST-BATCH-003',
            'claims' => [
                [
                    'reference'             => 'CLM-TEST-0003',
                    'beneficiary_reference' => $beneficiary['reference_number'],
                    'claim_type'            => 'cashless',
                    'company_code'          => 'MPPGCL',
                    'status_code'           => 'registered',
                ],
            ],
        ];
        $context = ['source_ip' => '127.0.0.1', 'user_agent' => 'PHPUnit'];
        $this->service->ingestBatch($initialPayload, $context);

        $updatePayload = [
            'batch_reference' => 'TEST-BATCH-004',
            'claims' => [
                [
                    'reference'             => 'CLM-TEST-0003',
                    'beneficiary_reference' => $beneficiary['reference_number'],
                    'claim_type'            => 'cashless',
                    'company_code'          => 'MPPGCL',
                    'status_code'           => 'approved',
                    'amounts'               => ['approved' => 20000],
                    'documents'             => [
                        [
                            'title'        => 'Bill',
                            'type_code'    => 'discharge_summary',
                            'storage_disk' => 'local',
                            'storage_path' => 'samples/update.pdf',
                            'checksum'     => 'XYZ987',
                        ],
                    ],
                ],
            ],
        ];

        $db = Database::connect($this->DBGroup);
        $initialClaim = $db->table('claims')->where('claim_reference', 'CLM-TEST-0003')->get()->getRowArray();
        $this->assertNotNull($initialClaim);

        $updateResult = $this->service->ingestBatch($updatePayload, $context);
        $claimResult = $updateResult['claims'][0];
        $this->assertSame('success', $claimResult['status'] ?? 'failed', print_r($updateResult, true));
        $this->assertSame(1, $claimResult['documents_expected']);
        $this->assertSame(1, $claimResult['documents_ingested']);
        $this->assertSame(1, $claimResult['document_stats']['inserted'] ?? 0);
        $this->assertSame(0, $claimResult['document_stats']['failed'] ?? 0);

        $claimRow = $db->table('claims')->where('claim_reference', 'CLM-TEST-0003')->get()->getRowArray();
        $this->assertSame('20000.00', $claimRow['approved_amount']);
        $this->assertSame($initialClaim['id'], $claimRow['id']);

        $documents = $db->table('claim_documents')->where('claim_id', $claimRow['id'])->get()->getResultArray();
        $this->assertCount(1, $documents);
        $this->assertSame('samples/update.pdf', $documents[0]['storage_path']);
        $this->assertSame('XYZ987', $documents[0]['checksum']);

        $batches = $db->table('claim_ingest_batches')->orderBy('id', 'ASC')->get()->getResultArray();
        $this->assertCount(2, $batches);
        $this->assertSame(1, (int) $batches[0]['claims_success']);
        $this->assertSame(1, (int) $batches[1]['claims_success']);
        $latestMetadata = json_decode($batches[1]['metadata'], true);
        $this->assertSame(1, $latestMetadata['summary']['documents']['totals']['attempted'] ?? 0);
        $this->assertSame(1, $latestMetadata['summary']['documents']['matrix']['record_ok_doc_ok'] ?? 0);
    }
}
