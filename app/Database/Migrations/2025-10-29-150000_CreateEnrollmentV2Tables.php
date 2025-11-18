<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEnrollmentV2Tables extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();
        $this->createBeneficiariesTable();
        $this->createDependentsTable();
        $this->createChangeLogTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('beneficiary_change_logs', true);
        $this->forge->dropTable('beneficiary_dependents_v2', true);
        $this->forge->dropTable('beneficiaries_v2', true);
        $this->forge->dropTable('blood_groups_ref', true);
        $this->forge->dropTable('banks_ref', true);
        $this->forge->dropTable('designations_ref', true);
        $this->forge->dropTable('retirement_offices', true);
        $this->forge->dropTable('regional_account_offices', true);
        $this->forge->dropTable('beneficiary_categories', true);
        $this->forge->dropTable('plan_options', true);
    }

    private function createLookupTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'label' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'coverage_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'ward_limit' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('plan_options', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'label' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('beneficiary_categories', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('regional_account_offices', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('retirement_offices', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('designations_ref', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'branch' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('banks_ref', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->forge->addField([
            'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
            'label' => ['type' => 'VARCHAR', 'constraint' => 10],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('label');
        $this->forge->createTable('blood_groups_ref', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }

    private function createBeneficiariesTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'legacy_beneficiary_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reference_number' => ['type' => 'VARCHAR', 'constraint' => 30],
            'plan_option_id' => ['type' => 'SMALLINT', 'unsigned' => true],
            'category_id' => ['type' => 'SMALLINT', 'unsigned' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'middle_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'gender' => ['type' => "ENUM('male','female','transgender')", 'null' => false],
            'date_of_birth' => ['type' => 'DATE', 'null' => false],
            'retirement_or_death_date' => ['type' => 'DATE', 'null' => false],
            'deceased_employee_name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'rao_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'rao_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'retirement_office_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'retirement_office_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'designation_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'designation_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'correspondence_address' => ['type' => 'TEXT', 'null' => false],
            'city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'state_id' => ['type' => 'INT', 'null' => false],
            'postal_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'ppo_number_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'ppo_number_masked' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'pran_number_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'pran_number_masked' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'gpf_number_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'gpf_number_masked' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'bank_source_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'bank_source_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'bank_servicing_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'bank_servicing_other' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'bank_account_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => false],
            'bank_account_masked' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'aadhaar_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => false],
            'aadhaar_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'pan_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'pan_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'primary_mobile_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => false],
            'primary_mobile_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'alternate_mobile_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'alternate_mobile_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'blood_group_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'samagra_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'samagra_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'terms_accepted_at' => ['type' => 'DATETIME', 'null' => true],
            'otp_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'otp_reference' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'submission_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'pending_review' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('legacy_beneficiary_id');
        $this->forge->addKey('plan_option_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('state_id');
        $this->forge->addKey('primary_mobile_masked');
        $this->forge->addKey('aadhaar_masked');
        $this->forge->addUniqueKey('reference_number');
        $this->forge->addForeignKey('plan_option_id', 'plan_options', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'beneficiary_categories', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('rao_id', 'regional_account_offices', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('retirement_office_id', 'retirement_offices', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('designation_id', 'designations_ref', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('bank_source_id', 'banks_ref', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('bank_servicing_id', 'banks_ref', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('blood_group_id', 'blood_groups_ref', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('state_id', 'states', 'state_id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('beneficiaries_v2', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }

    private function createDependentsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'beneficiary_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'relationship' => ['type' => "ENUM('spouse','child','father','mother','other')"],
            'dependant_order' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'twin_group' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'is_alive' => ['type' => "ENUM('alive','not_alive','not_applicable')", 'default' => 'alive'],
            'is_health_dependant' => ['type' => "ENUM('yes','no','not_applicable')", 'default' => 'not_applicable'],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'gender' => ['type' => "ENUM('male','female','transgender')", 'null' => false],
            'blood_group_id' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'date_of_birth' => ['type' => 'DATE', 'null' => true],
            'aadhaar_enc' => ['type' => 'VARBINARY', 'constraint' => 255, 'null' => true],
            'aadhaar_masked' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey(['beneficiary_id', 'relationship']);
        $this->forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('blood_group_id', 'blood_groups_ref', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('beneficiary_dependents_v2', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }

    private function createChangeLogTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'beneficiary_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'change_reference' => ['type' => 'VARCHAR', 'constraint' => 30],
            'change_type' => ['type' => "ENUM('create','update')", 'default' => 'create'],
            'summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'diff_json' => ['type' => 'LONGTEXT', 'null' => true],
            'changed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'changed_at' => ['type' => 'DATETIME', 'null' => false],
            'previous_version' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'new_version' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'review_status' => ['type' => "ENUM('pending','approved','rejected')", 'default' => 'pending'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('change_reference');
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey('changed_at');
        $this->forge->addForeignKey('beneficiary_id', 'beneficiaries_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('beneficiary_change_logs', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }
}
