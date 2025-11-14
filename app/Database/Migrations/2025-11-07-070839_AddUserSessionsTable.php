<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddUserSessionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'session_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'last_seen_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('user_id', true);
        $this->forge->addUniqueKey('session_id');
        $this->forge->createTable('user_sessions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('user_sessions', true);
    }
}
