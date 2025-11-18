<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBeneficiaryChangeItems extends Migration
{
    public function up(): void
    {
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
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'beneficiary',
            ],
            'entity_identifier' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'field_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'old_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'new_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
            ],
            'review_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['change_request_id', 'status']);
        $this->forge->addKey('entity_type');
        $this->forge->createTable('beneficiary_change_items', true);

        $this->db->query('ALTER TABLE beneficiary_change_items ADD CONSTRAINT fk_change_items_request FOREIGN KEY (change_request_id) REFERENCES beneficiary_change_requests(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->forge->dropTable('beneficiary_change_items', true);
    }
}
