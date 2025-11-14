<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClaimsModule extends Migration
{
    public function up(): void
    {
        $this->createClaimStatusesTable();
        $this->createClaimTypesTable();
        $this->createClaimDocumentTypesTable();
        $this->createPolicyCardsTable();
        $this->createClaimsTable();
        $this->createClaimEventsTable();
        $this->createClaimDocumentsTable();
        $this->seedReferenceData();
    }

    public function down(): void
    {
        $this->forge->dropTable('claim_documents', true);
        $this->forge->dropTable('claim_events', true);
        $this->forge->dropTable('claims', true);
        $this->forge->dropTable('beneficiary_policy_cards', true);
        $this->forge->dropTable('claim_document_types', true);
        $this->forge->dropTable('claim_types', true);
        $this->forge->dropTable('claim_statuses', true);
    }

    private function createClaimStatusesTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SMALLINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => false,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_terminal' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'display_order' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('claim_statuses', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createClaimTypesTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SMALLINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => false,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'display_order' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('claim_types', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createClaimDocumentTypesTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SMALLINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('claim_document_types', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createPolicyCardsTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'beneficiary_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'policy_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'card_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'policy_program' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'policy_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'tpa_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'tpa_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'effective_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'effective_to' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => "ENUM('active','inactive','expired')",
                'default'    => 'active',
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey('company_id');
        $this->forge->addUniqueKey(['beneficiary_id', 'card_number']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'app_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('updated_by', 'app_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('beneficiary_policy_cards', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createClaimsTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'beneficiary_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'dependent_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'policy_card_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'claim_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'external_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'claim_type_id' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status_id' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'claim_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'claim_sub_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'claim_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'admission_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'discharge_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'claimed_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'approved_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'cashless_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'copay_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'non_payable_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'reimbursed_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'hospital_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'hospital_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'hospital_city' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'hospital_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'diagnosis' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'tpa',
            ],
            'source_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'received_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('claim_reference');
        $this->forge->addKey('company_id');
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey('dependent_id');
        $this->forge->addKey('claim_type_id');
        $this->forge->addKey('status_id');
        $this->forge->addKey('claim_date');
        $this->forge->addKey('received_at');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('dependent_id', 'beneficiary_dependents_v2', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('policy_card_id', 'beneficiary_policy_cards', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('claim_type_id', 'claim_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('status_id', 'claim_statuses', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('claims', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createClaimEventsTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'claim_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'status_id' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'event_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'event_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'event_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('claim_id');
        $this->forge->addKey('status_id');
        $this->forge->addKey('event_time');
        $this->forge->addForeignKey('claim_id', 'claims', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('status_id', 'claim_statuses', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('claim_events', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function createClaimDocumentsTable(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'claim_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'document_type_id' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => false,
            ],
            'storage_disk' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'ftp',
            ],
            'storage_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'checksum' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'file_size' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'uploaded_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'uploaded_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('claim_id');
        $this->forge->addKey('document_type_id');
        $this->forge->addKey('storage_disk');
        $this->forge->addForeignKey('claim_id', 'claims', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('document_type_id', 'claim_document_types', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('uploaded_by', 'app_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('claim_documents', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    private function seedReferenceData(): void
    {
        $now = utc_now();

        $statuses = [
            ['code' => 'registered',        'label' => 'Registered',              'description' => 'Claim registered by TPA.', 'is_terminal' => 0, 'display_order' => 10],
            ['code' => 'preauth_pending',   'label' => 'Pre-Authorisation Pending', 'description' => 'Awaiting pre-authorisation decision.', 'is_terminal' => 0, 'display_order' => 20],
            ['code' => 'preauth_approved',  'label' => 'Pre-Authorised',          'description' => 'Pre-authorisation approved.', 'is_terminal' => 0, 'display_order' => 30],
            ['code' => 'query_raised',      'label' => 'Query Raised',            'description' => 'Additional information requested.', 'is_terminal' => 0, 'display_order' => 40],
            ['code' => 'processing',        'label' => 'Processing',              'description' => 'Claim processing in progress.', 'is_terminal' => 0, 'display_order' => 50],
            ['code' => 'approved',          'label' => 'Approved',                'description' => 'Claim approved for payment.', 'is_terminal' => 0, 'display_order' => 60],
            ['code' => 'partially_approved','label' => 'Partially Approved',      'description' => 'Claim approved with deductions.', 'is_terminal' => 0, 'display_order' => 70],
            ['code' => 'rejected',          'label' => 'Rejected',                'description' => 'Claim rejected.', 'is_terminal' => 1, 'display_order' => 80],
            ['code' => 'settled',           'label' => 'Settled',                 'description' => 'Claim settled and payment issued.', 'is_terminal' => 1, 'display_order' => 90],
            ['code' => 'closed',            'label' => 'Closed',                  'description' => 'Claim closed by TPA.', 'is_terminal' => 1, 'display_order' => 100],
        ];

        $statusRows = array_map(static function (array $row) use ($now) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            return $row;
        }, $statuses);

        $this->db->table('claim_statuses')->ignore(true)->insertBatch($statusRows);

        $types = [
            ['code' => 'cashless',        'label' => 'Cashless',        'description' => 'Cashless hospitalisation claim.', 'display_order' => 10],
            ['code' => 'reimbursement',   'label' => 'Reimbursement',   'description' => 'Post-discharge reimbursement claim.', 'display_order' => 20],
            ['code' => 'topup',           'label' => 'Top-up / Buffer', 'description' => 'Buffer or top-up claim.', 'display_order' => 30],
            ['code' => 'death',           'label' => 'Death Claim',     'description' => 'Death related claim.', 'display_order' => 40],
        ];

        $typeRows = array_map(static function (array $row) use ($now) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            return $row;
        }, $types);

        $this->db->table('claim_types')->ignore(true)->insertBatch($typeRows);

        $documentTypes = [
            ['code' => 'claim_form',        'label' => 'Claim Form',            'description' => 'Signed claim form copy.'],
            ['code' => 'discharge_summary', 'label' => 'Discharge Summary',     'description' => 'Hospital discharge summary.'],
            ['code' => 'hospital_bill',     'label' => 'Hospital Bill',         'description' => 'Itemised hospital bill.'],
            ['code' => 'investigation',     'label' => 'Investigation Report',  'description' => 'Diagnostic / investigation reports.'],
            ['code' => 'prescription',      'label' => 'Prescription',          'description' => 'Doctor prescription.'],
            ['code' => 'id_proof',          'label' => 'ID Proof',              'description' => 'Beneficiary identification proof.'],
            ['code' => 'others',            'label' => 'Other Document',        'description' => 'Miscellaneous supporting document.'],
        ];

        $documentRows = array_map(static function (array $row) use ($now) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            return $row;
        }, $documentTypes);

        $this->db->table('claim_document_types')->ignore(true)->insertBatch($documentRows);
    }
}
