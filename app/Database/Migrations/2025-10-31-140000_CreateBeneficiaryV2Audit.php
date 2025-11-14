<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBeneficiaryV2Audit extends Migration
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
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'user_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'app_users',
            ],
            'login_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'changes_before' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'changes_after' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'highlight_fields' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('beneficiary_id');
        $this->forge->addKey('user_id');

        $this->forge->addForeignKey(
            'beneficiary_id',
            'beneficiaries_v2',
            'id',
            'CASCADE',
            'CASCADE',
            'fk_beneficiary_v2_audit_beneficiary'
        );

        $this->forge->createTable('beneficiary_v2_audit', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Audit log for beneficiary v2 profile edits',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('beneficiary_v2_audit', true);
    }
}
