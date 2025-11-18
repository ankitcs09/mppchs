<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTmusersV2 extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'beneficiary_v2_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'bname' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'force_password_reset' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'password_changed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('beneficiary_v2_id');
        $this->forge->addUniqueKey('username');

        $this->forge->createTable('tmusers_v2', true, [
            'ENGINE'  => 'InnoDB',
            'COMMENT' => 'Password accounts provisioned for beneficiaries_v2',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tmusers_v2', true);
    }
}