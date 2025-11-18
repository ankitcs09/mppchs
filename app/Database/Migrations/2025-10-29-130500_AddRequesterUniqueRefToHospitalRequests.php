<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRequesterUniqueRefToHospitalRequests extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('requester_unique_ref', 'hospital_requests')) {
            $this->forge->addColumn('hospital_requests', [
                'requester_unique_ref' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                    'after'      => 'requester_user_id',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('requester_unique_ref', 'hospital_requests')) {
            $this->forge->dropColumn('hospital_requests', 'requester_unique_ref');
        }
    }
}
