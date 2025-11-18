<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrimaryMobileHashToBeneficiariesV2 extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('beneficiaries_v2', [
            'primary_mobile_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'SHA256 hash of canonical primary mobile number',
                'after'      => 'primary_mobile_masked',
            ],
        ]);

        $this->forge->addKey('primary_mobile_hash');
        $this->forge->processIndexes('beneficiaries_v2');
    }

    public function down(): void
    {
        $this->forge->dropKey('beneficiaries_v2', 'primary_mobile_hash');
        $this->forge->dropColumn('beneficiaries_v2', 'primary_mobile_hash');
    }
}

