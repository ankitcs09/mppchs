<?php

namespace Tests\Unit\Services;

use App\Services\Claims\DocumentStreamer;
use Config\Database;
use Tests\Support\EnrollmentTestCase;

class DocumentStreamerTest extends EnrollmentTestCase
{
    public function testStreamLogsDocumentAccess(): void
    {
        $db  = Database::connect($this->DBGroup);
        $now = utc_now();
        $beneficiary = $this->createBeneficiary();

        $claimId = $db->table('claims')->insert([
            'beneficiary_id'   => $beneficiary['id'],
            'dependent_id'     => null,
            'policy_card_id'   => null,
            'claim_reference'  => 'CLM-STREAM-1',
            'claim_type_id'    => null,
            'status_id'        => null,
            'received_at'      => $now,
            'last_synced_at'   => $now,
            'created_at'       => $now,
        ]) ? (int) $db->insertID() : null;

        $path = WRITEPATH . 'claims/samples';
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $file = $path . '/stream-test.pdf';
        file_put_contents($file, '%PDF-1.4 Test Document');

        $checksum = hash_file('sha256', $file);

        $db->table('claim_documents')->insert([
            'claim_id'         => $claimId,
            'document_type_id' => null,
            'title'            => 'Discharge Summary',
            'storage_disk'     => 'local',
            'storage_path'     => 'samples/stream-test.pdf',
            'checksum'         => $checksum,
            'uploaded_at'      => $now,
            'created_at'       => $now,
        ]);
        $documentId = (int) $db->insertID();

        $claim = [
            'id'            => $claimId,
            'beneficiary_id'=> $beneficiary['id'],
            'beneficiary'   => ['id' => $beneficiary['id']],
        ];
        $document = [
            'id'    => $documentId,
            'title' => 'Discharge Summary',
            'storage' => [
                'disk'     => 'local',
                'path'     => 'samples/stream-test.pdf',
                'checksum' => $checksum,
                'mime_type'=> 'application/pdf',
            ],
        ];

        $streamer = new DocumentStreamer(config('Claims'), Database::connect($this->DBGroup));
        $response = $streamer->stream($claim, $document, false, [
            'user_id'    => 42,
            'user_type'  => 'beneficiary',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'beneficiary_id' => $beneficiary['id'],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $logRow = $db->table('claim_document_access_log')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($logRow);
        $this->assertSame($claimId, (int) $logRow['claim_id']);
        $this->assertSame($documentId, (int) $logRow['document_id']);
        $this->assertSame(42, (int) $logRow['user_id']);
        $this->assertSame('beneficiary', $logRow['user_type']);
        $this->assertSame('beneficiary', $logRow['access_channel']);
    }
}
