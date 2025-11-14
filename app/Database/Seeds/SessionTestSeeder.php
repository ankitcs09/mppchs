<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SessionTestSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('user_sessions')->insert([
            'user_id'     => 999,
            'session_id'  => 'testsession',
            'last_seen_at'=> utc_now(),
        ]);
    }
}
