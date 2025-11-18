<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClaimAuditTables extends Migration
{
    public function up(): void
    {
        $forge = $this->forge;

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
        $forge->addKey('batch_reference');
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
        $forge->addKey('claim_id');
        $forge->addKey('document_id');
        $forge->addForeignKey('claim_id', 'claims', 'id', 'SET NULL', 'CASCADE');
        $forge->addForeignKey('document_id', 'claim_documents', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('claim_document_access_log', true);
    }

    public function down(): void
    {
        $forge = $this->forge;
        $forge->dropTable('claim_document_access_log', true);
        $forge->dropTable('claim_ingest_batches', true);
    }
}
