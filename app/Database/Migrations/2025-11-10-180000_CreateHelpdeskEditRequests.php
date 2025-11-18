<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHelpdeskEditRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'beneficiary_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'helpdesk_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'attachments' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
            ],
            'admin_user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'resolution_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'resolved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey('helpdesk_user_id');
        $this->forge->createTable('helpdesk_edit_requests', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('helpdesk_edit_requests', true);
    }
}
