<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Exception;

class SampleClaimsSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        $filePath     = WRITEPATH . 'claims/samples/discharge-summary.pdf';
        $fileChecksum = is_file($filePath) ? strtoupper(hash_file('sha256', $filePath)) : null;
        $fileSize     = is_file($filePath) ? filesize($filePath) : null;

        $documentTable = $db->table('claim_documents');
        $existingDoc   = $documentTable
            ->where('storage_path', 'samples/discharge-summary.pdf')
            ->get()
            ->getRowArray();

        if ($existingDoc) {
            $documentTable
                ->where('id', $existingDoc['id'])
                ->update([
                    'checksum'   => $fileChecksum,
                    'file_size'  => $fileSize,
                    'updated_at' => utc_now(),
                ]);

            $claim = $db->table('claims')
                ->select('claim_reference')
                ->where('id', $existingDoc['claim_id'])
                ->get()
                ->getRowArray();

            $reference = $claim['claim_reference'] ?? 'TEST-CLM-SAMPLE';
            echo "Sample claim document refreshed. Claim reference: {$reference}\n";
            return;
        }

        $beneficiary = $db->table('beneficiaries_v2')
            ->select('id')
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRowArray();

        if (! $beneficiary) {
            throw new Exception('No beneficiaries found. Seed enrollment data first.');
        }

        $status = $db->table('claim_statuses')
            ->where('code', 'registered')
            ->get()
            ->getRowArray();

        $type = $db->table('claim_types')
            ->where('code', 'cashless')
            ->get()
            ->getRowArray();

        $docType = $db->table('claim_document_types')
            ->where('code', 'discharge_summary')
            ->get()
            ->getRowArray();

        if (! $status || ! $type || ! $docType) {
            throw new Exception('Claim reference data not available. Run the claims migration first.');
        }

        $now        = utc_now();
        $claimDate  = Time::now('UTC')->toDateString();
        $reference  = 'TEST-CLM-' . strtoupper(bin2hex(random_bytes(4)));

        $policyData = [
            'beneficiary_id' => $beneficiary['id'],
            'policy_number'  => 'POL-' . substr($reference, -6),
            'card_number'    => 'CARD-' . substr($reference, -6),
            'policy_program' => 'MPPCHS Pilot Program',
            'policy_provider'=> 'Test ISA',
            'tpa_name'       => 'Sample TPA',
            'effective_from' => $claimDate,
            'status'         => 'active',
            'metadata'       => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $db->table('beneficiary_policy_cards')->insert($policyData);
        $policyCardId = (int) $db->insertID();

        $claimData = [
            'beneficiary_id'   => $beneficiary['id'],
            'policy_card_id'   => $policyCardId,
            'claim_reference'  => $reference,
            'claim_type_id'    => $type['id'],
            'status_id'        => $status['id'],
            'claim_date'       => $claimDate,
            'claimed_amount'   => 12500.00,
            'approved_amount'  => 10000.00,
            'cashless_amount'  => 9500.00,
            'copay_amount'     => 500.00,
            'non_payable_amount' => 2000.00,
            'hospital_name'    => 'Sample Empanelled Hospital',
            'hospital_city'    => 'Bhopal',
            'hospital_state'   => 'Madhya Pradesh',
            'diagnosis'        => 'Test Diagnosis',
            'remarks'          => 'Seeded claim for document streaming checks.',
            'source'           => 'seed',
            'received_at'      => $now,
            'last_synced_at'   => $now,
            'payload'          => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $db->table('claims')->insert($claimData);
        $claimId = (int) $db->insertID();

        $eventData = [
            'claim_id'   => $claimId,
            'status_id'  => $status['id'],
            'event_code' => 'registered',
            'event_label'=> 'Claim Registered',
            'description'=> 'Claim seeded for testing downloads.',
            'event_time' => $now,
            'source'     => 'seed',
            'payload'    => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('claim_events')->insert($eventData);

        $documentData = [
            'claim_id'         => $claimId,
            'document_type_id' => $docType['id'],
            'title'            => 'Discharge Summary (Sample)',
            'storage_disk'     => 'local',
            'storage_path'     => 'samples/discharge-summary.pdf',
            'checksum'         => $fileChecksum,
            'mime_type'        => 'application/pdf',
            'file_size'        => $fileSize,
            'source'           => 'seed',
            'uploaded_at'      => $now,
            'metadata'         => json_encode(['seeded' => true], JSON_THROW_ON_ERROR),
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $db->table('claim_documents')->insert($documentData);

        echo "Sample claim and document seeded. Claim reference: {$reference}\n";
    }
}
