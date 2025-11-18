<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRememberMeTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'auth_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'app_users',
            ],
            'selector' => [
                'type'       => 'VARCHAR',
                'constraint' => 24,
            ],
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'auth_table']);
        $this->forge->addUniqueKey('selector');
        $this->forge->createTable('auth_remember_tokens', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('auth_remember_tokens', true);
    }
}
