<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnrollmentTestTables extends Migration
{
    public function up(): void
    {
        $forge = $this->forge;

        // plan_options
        $forge->addField([
            'id'   => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'=> ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('plan_options', true);

        // beneficiary_categories
        $forge->addField([
            'id'   => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'label'=> ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('beneficiary_categories', true);

        // banks_ref
        $forge->addField([
            'id'   => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey('code');
        $forge->createTable('banks_ref', true);

        // blood_groups_ref
        $forge->addField([
            'id'    => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'label' => ['type' => 'VARCHAR', 'constraint' => 10],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('blood_groups_ref', true);

        // Additional lookup tables required by snapshot service
        $forge->addField([
            'id'   => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('regional_account_offices', true);

        $forge->addField([
            'id'   => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('retirement_offices', true);

        $forge->addField([
            'id'    => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('designations_ref', true);

        // states
        $forge->addField([
            'state_id'   => ['type' => 'INT', 'auto_increment' => true],
            'state_name' => ['type' => 'VARCHAR', 'constraint' => 80],
        ]);
        $forge->addKey('state_id', true);
        $forge->createTable('states', true);

        // beneficiaries_v2
        $forge->addField([
            'id'                      => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'legacy_beneficiary_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reference_number'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'legacy_reference'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'plan_option_id'          => ['type' => 'SMALLINT', 'unsigned' => true],
            'category_id'             => ['type' => 'SMALLINT', 'unsigned' => true],
            'first_name'              => ['type' => 'VARCHAR', 'constraint' => 120],
            'middle_name'             => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'last_name'               => ['type' => 'VARCHAR', 'constraint' => 120],
            'gender'                  => ['type' => "ENUM('male','female','transgender')"],
            'date_of_birth'           => ['type' => 'DATE'],
            'retirement_or_death_date'=> ['type' => 'DATE'],
            'deceased_employee_name'  => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'rao_id'                  => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'rao_other'               => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'retirement_office_id'    => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'retirement_office_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'designation_id'          => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'designation_other'       => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'correspondence_address'  => ['type' => 'TEXT'],
            'city'                    => ['type' => 'VARCHAR', 'constraint' => 120],
            'state_id'                => ['type' => 'INT'],
            'postal_code'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'bank_source_id'          => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'bank_source_other'       => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'bank_servicing_id'       => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'bank_servicing_other'    => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'ppo_number_enc'          => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'ppo_number_masked'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'pran_number_enc'         => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'pran_number_masked'      => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'gpf_number_enc'          => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'gpf_number_masked'       => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'bank_account_enc'        => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'bank_account_masked'     => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'aadhaar_enc'             => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'aadhaar_masked'          => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'pan_enc'                 => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'pan_masked'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'samagra_enc'             => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'samagra_masked'          => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'terms_accepted_at'       => ['type' => 'DATETIME', 'null' => true],
            'otp_verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'otp_reference'           => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'submission_source'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'primary_mobile_enc'      => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'primary_mobile_masked'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alternate_mobile_enc'    => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'alternate_mobile_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'                   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'blood_group_id'          => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'version'                 => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'pending_review'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'pending_change_request'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'change_requests_submitted'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'change_requests_approved'=> ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'last_change_request_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'last_request_submitted_at'=> ['type' => 'DATETIME', 'null' => true],
            'last_request_status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'last_request_reviewed_at'=> ['type' => 'DATETIME', 'null' => true],
            'last_request_reviewer_id'=> ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'last_request_comment'    => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('beneficiaries_v2', true);

        // beneficiary_dependents_v2
        $forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'beneficiary_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'relationship'        => ['type' => "ENUM('spouse','child','father','mother','other')"],
            'dependant_order'     => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'twin_group'          => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'is_alive'            => ['type' => "ENUM('alive','not_alive','not_applicable')", 'default' => 'alive'],
            'is_health_dependant' => ['type' => "ENUM('yes','no','not_applicable')", 'default' => 'not_applicable'],
            'first_name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'gender'              => ['type' => "ENUM('male','female','transgender')"],
            'blood_group_id'      => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'date_of_birth'       => ['type' => 'DATE', 'null' => true],
            'aadhaar_enc'         => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'aadhaar_masked'      => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'created_by'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_by'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
            'deleted_by'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'restored_at'         => ['type' => 'DATETIME', 'null' => true],
            'restored_by'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('beneficiary_dependents_v2', true);

        // beneficiary_change_audit (minimal)
        $forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'change_request_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'action'            => ['type' => 'VARCHAR', 'constraint' => 30],
            'actor_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'notes'             => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => false],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('beneficiary_change_audit', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('beneficiary_change_audit', true);
        $this->forge->dropTable('beneficiary_dependents_v2', true);
        $this->forge->dropTable('beneficiaries_v2', true);
        $this->forge->dropTable('states', true);
        $this->forge->dropTable('designations_ref', true);
        $this->forge->dropTable('retirement_offices', true);
        $this->forge->dropTable('regional_account_offices', true);
        $this->forge->dropTable('blood_groups_ref', true);
        $this->forge->dropTable('banks_ref', true);
        $this->forge->dropTable('beneficiary_categories', true);
        $this->forge->dropTable('plan_options', true);
    }
}

