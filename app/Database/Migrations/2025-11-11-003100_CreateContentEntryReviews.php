<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContentEntryReviews extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'entry_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'reviewer_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('entry_id');
        $this->forge->addKey('reviewer_id');
        $this->forge->addForeignKey('entry_id', 'content_entries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('content_entry_reviews', true);

        $this->forge->addColumn('content_entries', [
            'review_note' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'reviewed_by',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('content_entry_reviews', true);
        $this->forge->dropColumn('content_entries', 'review_note');
    }
}
