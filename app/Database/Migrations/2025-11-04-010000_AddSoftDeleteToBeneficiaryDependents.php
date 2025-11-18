<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeleteToBeneficiaryDependents extends Migration
{
    public function up(): void
    {
        $fields = [
            'is_active'   => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'created_at',
            ],
            'deleted_at'  => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_active',
            ],
            'deleted_by'  => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'deleted_at',
            ],
            'restored_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'deleted_by',
            ],
            'restored_by' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'restored_at',
            ],
        ];

        $this->forge->addColumn('beneficiary_dependents_v2', $fields);

        $db = $this->db;
        $db->table('beneficiary_dependents_v2')->set('is_active', 1)->update();

        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted_at');
        $this->forge->addKey('restored_at');
        $this->forge->processIndexes('beneficiary_dependents_v2');
    }

    public function down(): void
    {
        $this->forge->dropKey('beneficiary_dependents_v2', 'is_active');
        $this->forge->dropKey('beneficiary_dependents_v2', 'deleted_at');
        $this->forge->dropKey('beneficiary_dependents_v2', 'restored_at');

        $this->forge->dropColumn('beneficiary_dependents_v2', [
            'is_active',
            'deleted_at',
            'deleted_by',
            'restored_at',
            'restored_by',
        ]);
    }
}

