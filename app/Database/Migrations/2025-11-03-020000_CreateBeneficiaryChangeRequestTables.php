<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBeneficiaryChangeRequestTables extends Migration
{
    public function up()
    {
        // beneficiary_change_requests
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'beneficiary_v2_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'legacy_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'submission_no' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'revision_no' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'draft',
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'review_comment' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'payload_before' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'payload_after' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'summary_diff' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addKey('beneficiary_v2_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('beneficiary_change_requests', true);

        // beneficiary_change_dependents
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'change_request_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'dependent_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'order_index' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'relationship_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'alive_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'health_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'payload_before' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'payload_after' => [
                'type' => 'LONGTEXT',
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
        $this->forge->addKey('change_request_id');
        $this->forge->addKey('dependent_id');
        $this->forge->createTable('beneficiary_change_dependents', true);

        // beneficiary_change_audit
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'change_request_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'actor_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('change_request_id');
        $this->forge->addKey('action');
        $this->forge->createTable('beneficiary_change_audit', true);

        // add foreign keys
        $this->db->query('ALTER TABLE beneficiary_change_requests ADD CONSTRAINT fk_change_requests_beneficiary FOREIGN KEY (beneficiary_v2_id) REFERENCES beneficiaries_v2(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE beneficiary_change_requests ADD CONSTRAINT fk_change_requests_user FOREIGN KEY (user_id) REFERENCES app_users(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE beneficiary_change_requests ADD CONSTRAINT fk_change_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES app_users(id) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE beneficiary_change_dependents ADD CONSTRAINT fk_change_dependents_request FOREIGN KEY (change_request_id) REFERENCES beneficiary_change_requests(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE beneficiary_change_dependents ADD CONSTRAINT fk_change_dependents_original FOREIGN KEY (dependent_id) REFERENCES beneficiary_dependents_v2(id) ON DELETE SET NULL ON UPDATE CASCADE');

        $this->db->query('ALTER TABLE beneficiary_change_audit ADD CONSTRAINT fk_change_audit_request FOREIGN KEY (change_request_id) REFERENCES beneficiary_change_requests(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE beneficiary_change_audit ADD CONSTRAINT fk_change_audit_actor FOREIGN KEY (actor_id) REFERENCES app_users(id) ON DELETE SET NULL ON UPDATE CASCADE');

        // alter beneficiaries_v2 with tracking columns
        $this->forge->addColumn('beneficiaries_v2', [
            'last_change_request_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'pending_review',
            ],
            'pending_change_request' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'last_change_request_id',
            ],
            'change_requests_submitted' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'pending_change_request',
            ],
            'change_requests_approved' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'change_requests_submitted',
            ],
            'last_request_submitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'change_requests_approved',
            ],
            'last_request_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'last_request_submitted_at',
            ],
            'last_request_reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'last_request_status',
            ],
            'last_request_reviewer_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'last_request_reviewed_at',
            ],
            'last_request_comment' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'last_request_reviewer_id',
            ],
        ]);

        $this->db->query('ALTER TABLE beneficiaries_v2 ADD CONSTRAINT fk_beneficiaries_last_change_request FOREIGN KEY (last_change_request_id) REFERENCES beneficiary_change_requests(id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE beneficiaries_v2 ADD CONSTRAINT fk_beneficiaries_last_reviewer FOREIGN KEY (last_request_reviewer_id) REFERENCES app_users(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // drop foreign keys on beneficiaries_v2
        $this->db->query('ALTER TABLE beneficiaries_v2 DROP FOREIGN KEY fk_beneficiaries_last_change_request');
        $this->db->query('ALTER TABLE beneficiaries_v2 DROP FOREIGN KEY fk_beneficiaries_last_reviewer');

        $this->forge->dropColumn('beneficiaries_v2', [
            'last_change_request_id',
            'pending_change_request',
            'change_requests_submitted',
            'change_requests_approved',
            'last_request_submitted_at',
            'last_request_status',
            'last_request_reviewed_at',
            'last_request_reviewer_id',
            'last_request_comment',
        ]);

        $this->db->query('ALTER TABLE beneficiary_change_audit DROP FOREIGN KEY fk_change_audit_actor');
        $this->db->query('ALTER TABLE beneficiary_change_audit DROP FOREIGN KEY fk_change_audit_request');
        $this->db->query('ALTER TABLE beneficiary_change_dependents DROP FOREIGN KEY fk_change_dependents_request');
        $this->db->query('ALTER TABLE beneficiary_change_dependents DROP FOREIGN KEY fk_change_dependents_original');
        $this->db->query('ALTER TABLE beneficiary_change_requests DROP FOREIGN KEY fk_change_requests_beneficiary');
        $this->db->query('ALTER TABLE beneficiary_change_requests DROP FOREIGN KEY fk_change_requests_user');
        $this->db->query('ALTER TABLE beneficiary_change_requests DROP FOREIGN KEY fk_change_requests_reviewer');

        $this->forge->dropTable('beneficiary_change_audit', true);
        $this->forge->dropTable('beneficiary_change_dependents', true);
        $this->forge->dropTable('beneficiary_change_requests', true);
    }
}
