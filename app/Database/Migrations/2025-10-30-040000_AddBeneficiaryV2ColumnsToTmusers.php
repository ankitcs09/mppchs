<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBeneficiaryV2ColumnsToTmusers extends Migration
{
    public function up(): void
    {
        $fields = [
            'beneficiary_v2_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'beneficiary_id',
            ],
            'force_password_reset' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'password',
            ],
            'password_changed_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'force_password_reset',
            ],
        ];

        $this->forge->addColumn('tmusers', $fields);
        $this->forge->addKey('beneficiary_v2_id');
        $this->forge->addKey('username', false, true);
        $this->forge->processIndexes('tmusers');
    }

    public function down(): void
    {
        $this->forge->dropKey('tmusers', 'beneficiary_v2_id');
        $this->forge->dropKey('tmusers', 'username');
        $this->forge->dropColumn('tmusers', ['beneficiary_v2_id', 'force_password_reset', 'password_changed_at']);
    }
}
