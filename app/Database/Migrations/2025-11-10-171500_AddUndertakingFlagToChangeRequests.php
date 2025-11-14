<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUndertakingFlagToChangeRequests extends Migration
{
    public function up(): void
    {
        $fields = [
            'undertaking_confirmed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
                'after'      => 'summary_diff',
            ],
        ];

        $this->forge->addColumn('beneficiary_change_requests', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('beneficiary_change_requests', 'undertaking_confirmed');
    }
}
