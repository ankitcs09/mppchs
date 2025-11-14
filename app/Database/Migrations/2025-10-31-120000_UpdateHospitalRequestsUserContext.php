<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

class UpdateHospitalRequestsUserContext extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('hospital_requests', [
            'requester_user_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'tmusers',
                'after'      => 'requester_user_id',
            ],
        ]);

        $db = Database::connect();
        $db->table('hospital_requests')
            ->set('requester_user_table', 'tmusers')
            ->where('requester_user_table IS NULL', null, false)
            ->update();

        $this->forge->dropForeignKey('hospital_requests', 'fk_hospital_requests_user');
    }

    public function down(): void
    {
        $this->forge->dropColumn('hospital_requests', 'requester_user_table');

        $this->forge->addForeignKey(
            'requester_user_id',
            'tmusers',
            'id',
            'CASCADE',
            'CASCADE',
            'fk_hospital_requests_user'
        );
        $this->forge->processIndexes('hospital_requests');
    }
}
