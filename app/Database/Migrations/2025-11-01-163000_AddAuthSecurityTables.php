<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthSecurityTables extends Migration
{
    public function up(): void
    {
        // Track recent password hashes to prevent reuse.
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'changed_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'changed_at']);
        $this->forge->addForeignKey('user_id', 'tmusers_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_password_history', true);

        // Token-based password reset requests (email/SMS agnostic).
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'selector' => [
                'type'       => 'CHAR',
                'constraint' => 16,
                'null'       => false,
            ],
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'attempts' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
            ],
            'requested_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'default'    => null,
            ],
            'requested_user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('selector', false, true);
        $this->forge->addKey('expires_at');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'tmusers_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('password_resets', true);

        // Optional table to log authentication attempts for throttling/auditing.
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'comment'    => 'password|otp|reset',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => false,
                'comment'    => 'success|failure|locked',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'default'    => null,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'metadata' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'comment'    => 'JSON payload for additional context',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('username');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('auth_attempts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_attempts', true);
        $this->forge->dropTable('password_resets', true);
        $this->forge->dropTable('user_password_history', true);
    }
}
