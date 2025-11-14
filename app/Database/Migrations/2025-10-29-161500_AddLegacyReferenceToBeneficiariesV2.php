<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacyReferenceToBeneficiariesV2 extends Migration
{
    public function up(): void
    {
        $fields = [
            'legacy_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'reference_number',
            ],
        ];

        $this->forge->addColumn('beneficiaries_v2', $fields);
        $this->forge->addUniqueKey('legacy_reference');
        $this->forge->processIndexes('beneficiaries_v2');
    }

    public function down(): void
    {
        $this->forge->dropKey('beneficiaries_v2', 'legacy_reference');
        $this->forge->dropColumn('beneficiaries_v2', 'legacy_reference');
    }
}
