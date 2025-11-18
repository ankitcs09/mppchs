<?php

namespace Tests\Unit\Services;

use App\Services\Claims\ClaimAuditService;
use App\Services\Claims\ClaimsIngestionService;
use App\Services\Claims\DocumentStreamer;
use Config\Claims;
use Config\Database;
use Tests\Support\EnrollmentTestCase;

class ClaimAuditServiceTest extends EnrollmentTestCase
{
    private ClaimAuditService $audit;
    private ClaimsIngestionService $ingest;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = Database::connect($this->DBGroup);

        $this->audit  = new ClaimAuditService($connection);
        $this->ingest = new ClaimsIngestionService(
            $connection,
            config(Claims::class)
        );
    }

    public function testBatchListingAndDetail(): void
    {
        $beneficiary = $this->createBeneficiary();

        $payloadSuccess = [
            'batch_reference' => 'BATCH-A',
            'claims' => [
                [
                    'reference'             => 'CLM-A-1',
                    'beneficiary_reference' => $beneficiary['reference_number'],
                    'company_code'          => 'MPPGCL',
                    'claim_type'            => 'cashless',
                    'status_code'           => 'approved',
                    'documents'             => [
                        [
                            'title'        => 'Summary Sheet',
                            'storage_disk'=> 'local',
                            'storage_path'=> 'samples/audit-doc.pdf',
                            'checksum'    => 'CHK001',
                        ],
                    ],
                ],
            ],
        ];

        $payloadFailure = [
            'batch_reference' => 'BATCH-B',
            'claims' => [
                [
                    'reference'             => 'CLM-B-1',
                    'beneficiary_reference' => 'UNKNOWN',
                    'company_code'          => 'MPPGCL',
                ],
            ],
        ];

        $context = ['source_ip' => '127.0.0.1', 'user_agent' => 'PHPUnit'];
        $this->ingest->ingestBatch($payloadSuccess, $context);
        $this->ingest->ingestBatch($payloadFailure, $context);

        $listing = $this->audit->listIngestBatches([], null, 1, 10);
        $this->assertNotEmpty($listing['data']);
        $this->assertGreaterThanOrEqual(2, $listing['pagination']['total']);
        $this->assertArrayHasKey('documents', $listing['summary']);

        $successBatch = null;
        $failureBatch = null;
        foreach ($listing['data'] as $row) {
            if (($row['batch_reference'] ?? null) === 'BATCH-A') {
                $successBatch = $row;
            } elseif (($row['batch_reference'] ?? null) === 'BATCH-B') {
                $failureBatch = $row;
            }
        }

        $this->assertNotNull($successBatch);
        $this->assertNotNull($failureBatch);
        $this->assertSame(1, $successBatch['documents_summary']['totals']['attempted'] ?? 0);
        $this->assertSame(1, $successBatch['documents_summary']['matrix']['record_ok_doc_ok'] ?? 0);
        $this->assertSame(0, $failureBatch['documents_summary']['totals']['attempted'] ?? 0);

        $detail = $this->audit->getIngestBatch($successBatch['id']);
        $this->assertNotNull($detail);
        $this->assertArrayHasKey('claims', $detail);
        $this->assertArrayHasKey('items', $detail);
        $this->assertSame('ok', $detail['items'][0]['documents']['state'] ?? null);
    }

    public function testDocumentDownloadListing(): void
    {
        $connection = Database::connect($this->DBGroup);
        $beneficiary = $this->createBeneficiary();

        $payload = [
            'batch_reference' => 'BATCH-DOC',
            'claims' => [
                [
                    'reference'             => 'CLM-DOC-1',
                    'beneficiary_reference' => $beneficiary['reference_number'],
                    'company_code'          => 'MPPGCL',
                    'claim_type'            => 'cashless',
                    'status_code'           => 'approved',
                    'documents'             => [
                        [
                            'title'        => 'Summary Sheet',
                            'storage_disk'=> 'local',
                            'storage_path'=> 'samples/audit-doc.pdf',
                            'checksum'    => 'CHK001',
                        ],
                    ],
                ],
            ],
        ];

        $this->ingest->ingestBatch($payload, ['source_ip' => '127.0.0.1', 'user_agent' => 'PHPUnit']);

        $claimRow = $connection->table('claims')->where('claim_reference', 'CLM-DOC-1')->get()->getRowArray();
        $this->assertNotNull($claimRow);

        $path = WRITEPATH . 'claims/samples';
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $filePath = $path . '/audit-test.pdf';
        file_put_contents($filePath, '%PDF-1.4 Test Document');
        $checksum = hash_file('sha256', $filePath);

        $now = utc_now();

        $connection->table('claim_documents')->insert([
            'claim_id'         => $claimRow['id'],
            'document_type_id' => null,
            'title'            => 'Audit Doc',
            'storage_disk'     => 'local',
            'storage_path'     => 'samples/audit-test.pdf',
            'checksum'         => $checksum,
            'mime_type'        => 'application/pdf',
            'uploaded_at'      => $now,
            'created_at'       => $now,
        ]);
        $documentId = (int) $connection->insertID();

        $streamer = new DocumentStreamer(config('Claims'), $connection);
        $claim = [
            'id' => $claimRow['id'],
            'beneficiary' => ['id' => $beneficiary['id']],
        ];
        $document = [
            'id' => $documentId,
            'title' => 'Audit Doc',
            'storage' => [
                'disk'     => 'local',
                'path'     => 'samples/audit-test.pdf',
                'checksum' => $checksum,
            ],
        ];

        $streamer->stream($claim, $document, false, [
            'user_id'        => 999,
            'user_type'      => 'beneficiary',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'PHPUnit',
            'beneficiary_id' => $beneficiary['id'],
        ]);

        $listing = $this->audit->listDocumentDownloads([], null, 1, 10);
        $this->assertNotEmpty($listing['data']);
        $row = $listing['data'][0];
        $this->assertSame('beneficiary', $row['channel']);
        $this->assertSame('Audit Doc', $row['document']['title']);
    }
}



