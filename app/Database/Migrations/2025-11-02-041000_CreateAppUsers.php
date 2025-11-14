<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\I18n\Time;

class CreateAppUsers extends Migration
{
    public function up(): void
    {
        $forge = $this->forge;

        // ------------------------------------------------------------------
        // app_users table
        // ------------------------------------------------------------------
        $forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
                'default'    => null,
            ],
            'bname' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
                'default'    => null,
            ],
            'bname' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
                'default'    => null,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
                'default'    => null,
            ],
            'mobile' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'user_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'beneficiary',
            ],
            'company_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'beneficiary_v2_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'locked', 'disabled'],
                'default'    => 'active',
            ],
            'force_password_reset' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
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
        $forge->addKey('id', true);
        $forge->addUniqueKey('username');
        $forge->addKey('user_type');
        $forge->addKey('company_id');
        $forge->addKey('beneficiary_v2_id');
        $forge->addForeignKey('company_id', 'companies', 'id', 'SET NULL', 'SET NULL');
        $forge->addForeignKey('beneficiary_v2_id', 'beneficiaries_v2', 'id', 'SET NULL', 'SET NULL');
        $forge->createTable('app_users', true);

        // ------------------------------------------------------------------
        // backfill beneficiaries from tmusers_v2
        // ------------------------------------------------------------------
        $builder = $this->db->table('tmusers_v2');
        $existing = $builder->select('*')->get();

        if ($existing->getNumRows() > 0) {
            $now = Time::now('UTC')->toDateTimeString();
            $rows = [];

            foreach ($existing->getResultArray() as $row) {
                $rows[] = [
                    'id'                   => (int) $row['id'],
                    'username'             => $row['username'],
                    'display_name'         => $row['bname'] ?? $row['username'],
                    'bname'                => $row['bname'] ?? $row['username'],
                    'email'                => null,
                    'mobile'               => null,
                    'password'             => $row['password'],
                    'user_type'            => 'beneficiary',
                    'company_id'           => null,
                    'beneficiary_v2_id'    => $row['beneficiary_v2_id'] ?? null,
                    'status'               => 'active',
                    'force_password_reset' => (int) ($row['force_password_reset'] ?? 0),
                    'password_changed_at'  => $row['password_changed_at'] ?? null,
                    'last_login_at'        => $row['last_login_at'] ?? null,
                    'created_at'           => $row['created_at'] ?? $now,
                    'updated_at'           => $row['updated_at'] ?? $now,
                ];
            }

            $this->db->table('app_users')->ignore(true)->insertBatch($rows, 500);

            $maxId = $this->db->table('app_users')->selectMax('id')->get()->getRow();
            if ($maxId && isset($maxId->id)) {
                $next = (int) $maxId->id + 1;
                $this->db->query('ALTER TABLE app_users AUTO_INCREMENT = ' . $next);
            }
        }

        // ------------------------------------------------------------------
        // Update foreign key references to app_users
        // ------------------------------------------------------------------
        $this->remapForeignKey('user_password_history', 'user_password_history_user_id_foreign', 'user_id');
        $this->remapForeignKey('password_resets', 'password_resets_user_id_foreign', 'user_id');
        $this->remapForeignKey('auth_attempts', 'auth_attempts_user_id_foreign', 'user_id', 'SET NULL');
        $this->remapForeignKey('user_roles', 'user_roles_user_id_foreign', 'user_id');
        $this->remapForeignKey('user_roles', 'user_roles_assigned_by_foreign', 'assigned_by', 'SET NULL', 'SET NULL');
    }

    public function down(): void
    {
        // Drop foreign keys pointing to app_users and restore to tmusers_v2
        $this->remapForeignKey('user_password_history', 'user_password_history_user_id_foreign', 'user_id', 'CASCADE', 'CASCADE', 'tmusers_v2');
        $this->remapForeignKey('password_resets', 'password_resets_user_id_foreign', 'user_id', 'CASCADE', 'CASCADE', 'tmusers_v2');
        $this->remapForeignKey('auth_attempts', 'auth_attempts_user_id_foreign', 'user_id', 'SET NULL', 'SET NULL', 'tmusers_v2');
        $this->remapForeignKey('user_roles', 'user_roles_user_id_foreign', 'user_id', 'CASCADE', 'CASCADE', 'tmusers_v2');
        $this->remapForeignKey('user_roles', 'user_roles_assigned_by_foreign', 'assigned_by', 'SET NULL', 'SET NULL', 'tmusers_v2');

        $this->forge->dropTable('app_users', true);
    }

    /**
     * Drops a foreign key and recreates it referencing the desired table.
     */
    private function remapForeignKey(
        string $table,
        string $constraint,
        string $column,
        string $onDelete = 'CASCADE',
        string $onUpdate = 'CASCADE',
        string $referencedTable = 'app_users'
    ): void {
        try {
            $this->db->query(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint));
        } catch (\Throwable $th) {
            // Ignore — constraint may not exist yet
        }

        $sql = sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`id`) ON DELETE %s ON UPDATE %s',
            $table,
            $constraint,
            $column,
            $referencedTable,
            $onDelete,
            $onUpdate
        );

        $this->db->query($sql);
    }
}

