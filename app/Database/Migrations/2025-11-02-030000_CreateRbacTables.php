<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRbacTables extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // companies
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'is_nodal' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('companies', true);

        // ------------------------------------------------------------------
        // regions (optional scoping below company level)
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'region_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'comment'    => 'zone|circle|office etc.',
            ],
            'is_global' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
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
        $this->forge->addKey(['company_id', 'code']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('regions', true);

        // ------------------------------------------------------------------
        // roles
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
            ],
            'is_global' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_assignable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'priority' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'default'    => 100,
            ],
            'default_redirect' => [
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
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('roles', true);

        // ------------------------------------------------------------------
        // permissions
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
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
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('permissions', true);

        // ------------------------------------------------------------------
        // role_permissions (pivot)
        // ------------------------------------------------------------------
        $this->forge->addField([
            'role_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'permission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'granted_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'granted_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
        ]);
        $this->forge->addKey(['role_id', 'permission_id'], true);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions', true);

        // ------------------------------------------------------------------
        // user_roles (assignments with scoping)
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'role_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'region_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'assigned_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'assigned_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'revoked_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'metadata' => [
                'type'       => 'JSON',
                'null'       => true,
                'default'    => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'role_id']);
        $this->forge->addForeignKey('user_id', 'tmusers_v2', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('assigned_by', 'tmusers_v2', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('user_roles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('regions', true);
        $this->forge->dropTable('companies', true);
    }
}
