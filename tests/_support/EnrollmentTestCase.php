<?php

namespace Tests\Support;

use App\Services\SensitiveDataService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

class EnrollmentTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'Tests\Support';
    protected $DBGroup = 'tests';

    protected SensitiveDataService $crypto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crypto = new SensitiveDataService();
        $this->seedLookups();
        $this->ensureClaimTables();
        $this->seedClaimLookups();
    }

    protected function seedLookups(): void
    {
        $db = Database::connect($this->DBGroup);

        if ((int) $db->table('plan_options')->countAllResults() === 0) {
            $db->table('plan_options')->insert([
                'id'    => 1,
                'code'  => 'PLAN_A',
                'label' => 'Plan A',
            ]);
        }

        if ((int) $db->table('beneficiary_categories')->countAllResults() === 0) {
            $db->table('beneficiary_categories')->insert([
                'id'    => 1,
                'code'  => 'CAT_A',
                'label' => 'Category A',
            ]);
        }

        if ((int) $db->table('states')->where('state_id', 1)->countAllResults() === 0) {
            $db->table('states')->insert([
                'state_id'   => 1,
                'state_name' => 'MADHYA PRADESH',
            ]);
        }

        if ((int) $db->table('banks_ref')->countAllResults() === 0) {
            $db->table('banks_ref')->insert([
                'id'   => 1,
                'code' => 'BANK_A',
                'name' => 'Bank A',
            ]);
        }

        if ((int) $db->table('blood_groups_ref')->countAllResults() === 0) {
            $db->table('blood_groups_ref')->insert([
                'id'    => 1,
                'label' => 'O+',
            ]);
        }
    }

    protected function seedClaimLookups(): void
    {
        $db = Database::connect($this->DBGroup);

        if (! $db->tableExists('claim_statuses')) {
            return;
        }

        if ((int) $db->table('claim_statuses')->countAllResults() === 0) {
            $db->table('claim_statuses')->insertBatch([
                ['id' => 1, 'code' => 'registered', 'label' => 'Registered', 'is_terminal' => 0],
                ['id' => 2, 'code' => 'approved', 'label' => 'Approved', 'is_terminal' => 0],
            ]);
        }

        if ((int) $db->table('claim_types')->countAllResults() === 0) {
            $db->table('claim_types')->insert([
                'id'    => 1,
                'code'  => 'cashless',
                'label' => 'Cashless',
            ]);
        }

        if ((int) $db->table('claim_document_types')->countAllResults() === 0) {
            $db->table('claim_document_types')->insert([
                'id'    => 1,
                'code'  => 'discharge_summary',
                'label' => 'Discharge Summary',
            ]);
        }

        if ($db->tableExists('companies') && (int) $db->table('companies')->countAllResults() === 0) {
            $db->table('companies')->insert([
                'id'       => 1,
                'code'     => 'MPPGCL',
                'name'     => 'M.P. Power Generating Co. Ltd.',
                'is_nodal' => 1,
            ]);
        }
    }

    protected function ensureClaimTables(): void
    {
        $db = Database::connect($this->DBGroup);
        $forge = \Config\Database::forge($this->DBGroup);

        // Always drop in dependency order to avoid stale schemas.
        foreach (['claim_document_access_log', 'claim_ingest_batches', 'claim_documents', 'claim_events', 'claims', 'beneficiary_policy_cards', 'claim_document_types', 'claim_types', 'claim_statuses', 'companies', 'app_users'] as $table) {
            if ($db->tableExists($table)) {
                $forge->dropTable($table, true);
            }
        }

        $forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'label' => ['type' => 'VARCHAR', 'constraint' => 120],
            'is_terminal' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('claim_statuses', true);

        $forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'label' => ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('claim_types', true);

        $forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'label' => ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('claim_document_types', true);

        $forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'is_nodal' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('companies', true);

        $forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('app_users', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'beneficiary_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'policy_number' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'card_number' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'policy_program' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'policy_provider' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => "ENUM('active','inactive','expired')", 'default' => 'active'],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $forge->createTable('beneficiary_policy_cards', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'beneficiary_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'dependent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'policy_card_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'claim_reference' => ['type' => 'VARCHAR', 'constraint' => 120],
            'external_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'claim_type_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'status_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'claim_category' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'claim_sub_status' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'claim_date' => ['type' => 'DATE', 'null' => true],
            'admission_date' => ['type' => 'DATE', 'null' => true],
            'discharge_date' => ['type' => 'DATE', 'null' => true],
            'claimed_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'approved_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'cashless_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'copay_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'non_payable_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'reimbursed_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'hospital_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'hospital_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'hospital_city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'hospital_state' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'diagnosis' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'source_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'received_at' => ['type' => 'DATETIME', 'null' => true],
            'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
            'payload' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('claim_reference');
        $forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $forge->addForeignKey('dependent_id', 'beneficiary_dependents_v2', 'id', 'SET NULL', 'CASCADE');
        $forge->addForeignKey('policy_card_id', 'beneficiary_policy_cards', 'id', 'SET NULL', 'CASCADE');
        $forge->addForeignKey('claim_type_id', 'claim_types', 'id', 'SET NULL', 'CASCADE');
        $forge->addForeignKey('status_id', 'claim_statuses', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('claims', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'claim_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'event_code' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'event_label' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'event_time' => ['type' => 'DATETIME', 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'payload' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('claim_id');
        $forge->addForeignKey('claim_id', 'claims', 'id', 'CASCADE', 'CASCADE');
        $forge->addForeignKey('status_id', 'claim_statuses', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('claim_events', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'claim_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'document_type_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'storage_disk' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'ftp'],
            'storage_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'checksum' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'file_size' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'uploaded_at' => ['type' => 'DATETIME', 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('claim_id');
        $forge->addForeignKey('claim_id', 'claims', 'id', 'CASCADE', 'CASCADE');
        $forge->addForeignKey('document_type_id', 'claim_document_types', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('claim_documents', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'batch_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'claims_received' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'claims_success'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'claims_failed'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'company_ids'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'requested_ip'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'processed_at'    => ['type' => 'DATETIME', 'null' => false],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'metadata'        => ['type' => 'TEXT', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('claim_ingest_batches', true);

        $forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'claim_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'document_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'user_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'access_channel' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'client_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'downloaded_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('claim_id', 'claims', 'id', 'SET NULL', 'CASCADE');
        $forge->addForeignKey('document_id', 'claim_documents', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('claim_document_access_log', true);
    }

    protected function createBeneficiary(array $overrides = []): array
    {
        $db  = Database::connect($this->DBGroup);
        $now = Time::now('UTC')->toDateTimeString();

        $defaults = [
            'reference_number'        => 'BEN' . uniqid(),
            'plan_option_id'          => 1,
            'category_id'             => 1,
            'first_name'              => 'Test',
            'middle_name'             => null,
            'last_name'               => 'Beneficiary',
            'gender'                  => 'male',
            'date_of_birth'           => '1970-01-01',
            'retirement_or_death_date'=> '2020-01-01',
            'deceased_employee_name'  => null,
            'rao_id'                  => null,
            'rao_other'               => null,
            'retirement_office_id'    => null,
            'retirement_office_other' => null,
            'designation_id'          => null,
            'designation_other'       => null,
            'correspondence_address'  => '123 Sample Street',
            'city'                    => 'Bhopal',
            'state_id'                => 1,
            'postal_code'             => '462001',
            'bank_source_id'          => 1,
            'bank_servicing_id'       => 1,
            'bank_account_enc'        => $this->crypto->encrypt('123456789012'),
            'bank_account_masked'     => 'XXXXXXXX9012',
            'aadhaar_enc'             => $this->crypto->encrypt('111122223333'),
            'aadhaar_masked'          => 'XXXXXXXX3333',
            'pan_enc'                 => $this->crypto->encrypt('ABCDE1234F'),
            'pan_masked'              => 'XXXXX1234F',
            'primary_mobile_enc'      => $this->crypto->encrypt('9999999999'),
            'primary_mobile_masked'   => 'XXXXXX9999',
            'alternate_mobile_enc'    => null,
            'alternate_mobile_masked' => null,
            'email'                   => 'test@example.com',
            'blood_group_id'          => 1,
            'samagra_enc'             => null,
            'samagra_masked'          => null,
            'version'                 => 1,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $data = array_merge($defaults, $overrides);

        $db->table('beneficiaries_v2')->insert($data);

        $data['id'] = (int) $db->insertID();

        return $data;
    }

    protected function createDependent(int $beneficiaryId, array $overrides = []): array
    {
        $db  = Database::connect($this->DBGroup);
        $now = Time::now('UTC')->toDateTimeString();

        $defaults = [
            'beneficiary_id'      => $beneficiaryId,
            'relationship'        => 'spouse',
            'dependant_order'     => 1,
            'twin_group'          => null,
            'is_alive'            => 'alive',
            'is_health_dependant' => 'yes',
            'first_name'          => 'Sample Dependent',
            'gender'              => 'female',
            'blood_group_id'      => 1,
            'date_of_birth'       => '1975-02-02',
            'aadhaar_enc'         => $this->crypto->encrypt('444455556666'),
            'aadhaar_masked'      => 'XXXXXXXX6666',
            'created_at'          => $now,
            'created_by'          => 1,
            'updated_at'          => $now,
            'updated_by'          => 1,
            'is_active'           => 1,
            'deleted_at'          => null,
            'deleted_by'          => null,
            'restored_at'         => null,
            'restored_by'         => null,
        ];

        $data = array_merge($defaults, $overrides);

        $db->table('beneficiary_dependents_v2')->insert($data);

        $data['id'] = (int) $db->insertID();

        return $data;
    }

    protected function createPendingChangeRequest(int $beneficiaryId, array $payloadAfter, array $payloadBefore = []): int
    {
        $model = new \App\Models\BeneficiaryChangeRequestModel(Database::connect($this->DBGroup));
        $now   = Time::now('UTC')->toDateTimeString();

        $payloadBefore = $payloadBefore ?: [
            'beneficiary' => [],
            'dependents'  => [],
        ];

        $model->insert([
            'beneficiary_v2_id' => $beneficiaryId,
            'user_id'           => 1,
            'status'            => 'pending',
            'requested_at'      => $now,
            'payload_before'    => json_encode($payloadBefore, JSON_THROW_ON_ERROR),
            'payload_after'     => json_encode($payloadAfter, JSON_THROW_ON_ERROR),
        ]);

        return (int) $model->getInsertID();
    }
}
