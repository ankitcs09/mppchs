<?php

namespace Tests\Support;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

abstract class FeatureTestCase extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected static bool $tablesReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        $dbConfig = config(Database::class);
        $dbConfig->defaultGroup = 'tests';

        if (! self::$tablesReady) {
            $this->createTestTables();
            self::$tablesReady = true;
        }

        $this->resetTestTables();
    }

    protected function adminSession(array $overrides = []): array
    {
        $context = [
            'user_id'        => 1,
            'roles'          => [['slug' => 'admin']],
            'role_slugs'     => ['admin'],
            'permissions'    => ['review_profile_update', 'approve_profile_update'],
            'permission_set' => [
                'review_profile_update'  => true,
                'approve_profile_update' => true,
            ],
            'company_map'    => [],
            'company_ids'    => [],
            'active_company_id' => null,
            'has_global_scope'  => true,
        ];

        $session = [
            'isLoggedIn'      => true,
            'id'              => 1,
            'authUserTable'   => 'app_users',
            'rbac.context'    => $context,
            'rbac.active_company_id' => null,
            'username'        => 'admin',
        ];

        return array_merge($session, $overrides);
    }

    protected function seedChangeRequest(): void
    {
        $db = Database::connect('tests');

        $db->table('app_users')->insert([
            'id'              => 1,
            'username'        => 'admin',
            'display_name'    => 'Admin User',
            'status'          => 'active',
            'session_version' => 1,
        ]);

        $db->table('beneficiaries_v2')->insert([
            'id'                        => 1,
            'first_name'                => '????',
            'middle_name'               => '',
            'last_name'                 => '?????',
            'gender'                    => 'F',
            'date_of_birth'             => null,
            'retirement_or_death_date'  => null,
            'deceased_employee_name'    => null,
            'rao_id'                    => null,
            'rao_other'                 => null,
            'retirement_office_id'      => null,
            'retirement_office_other'   => null,
            'designation_id'            => null,
            'designation_other'         => null,
            'correspondence_address'    => null,
            'city'                      => null,
            'state_id'                  => null,
            'postal_code'               => null,
            'ppo_number_masked'         => null,
            'pran_number_masked'        => null,
            'gpf_number_masked'         => null,
            'bank_source_id'            => null,
            'bank_source_other'         => null,
            'bank_servicing_id'         => null,
            'bank_servicing_other'      => null,
            'bank_account_masked'       => null,
            'aadhaar_masked'            => null,
            'pan_masked'                => null,
            'primary_mobile_masked'     => null,
            'alternate_mobile_masked'   => null,
            'email'                     => null,
            'blood_group_id'            => null,
            'samagra_masked'            => null,
            'updated_at'                => Time::now()->toDateTimeString(),
        ]);

        $payloadBefore = json_encode(['beneficiary' => ['first_name' => '????']], JSON_UNESCAPED_UNICODE);
        $payloadAfter  = json_encode(['beneficiary' => ['first_name' => '???? (?????)']], JSON_UNESCAPED_UNICODE);

        $db->table('beneficiary_change_requests')->insert([
            'id'                => 1,
            'beneficiary_v2_id' => 1,
            'user_id'           => 1,
            'reference_number'  => 'CR-1',
            'status'            => 'pending',
            'requested_at'      => Time::now()->toDateTimeString(),
            'created_at'        => Time::now()->toDateTimeString(),
            'updated_at'        => Time::now()->toDateTimeString(),
            'payload_before'    => $payloadBefore,
            'payload_after'     => $payloadAfter,
            'summary_diff'      => json_encode(['beneficiary_changes' => 1]),
        ]);

        $db->table('beneficiary_change_items')->insert([
            'id'                => 1,
            'change_request_id' => 1,
            'entity_type'       => 'beneficiary',
            'entity_identifier' => null,
            'field_key'         => 'beneficiary:first_name',
            'field_label'       => 'First Name',
            'old_value'         => '????',
            'new_value'         => '???? (?????)',
            'status'            => 'pending',
            'created_at'        => Time::now()->toDateTimeString(),
            'updated_at'        => Time::now()->toDateTimeString(),
        ]);
    }

    private function createTestTables(): void
    {
        $db = Database::connect('tests');

        $db->simpleQuery('DROP TABLE IF EXISTS beneficiary_change_audit');
        $db->simpleQuery('DROP TABLE IF EXISTS beneficiary_change_items');
        $db->simpleQuery('DROP TABLE IF EXISTS beneficiary_change_requests');
        $db->simpleQuery('DROP TABLE IF EXISTS beneficiaries_v2');
        $db->simpleQuery('DROP TABLE IF EXISTS app_users');
        $db->simpleQuery('DROP TABLE IF EXISTS user_sessions');

        $db->simpleQuery(<<<'SQL'
CREATE TABLE app_users (
    id INTEGER PRIMARY KEY,
    username VARCHAR(255),
    display_name VARCHAR(255),
    status VARCHAR(50),
    session_version INTEGER DEFAULT 1
)
SQL);

        $db->simpleQuery(<<<'SQL'
CREATE TABLE beneficiary_change_requests (
    id INTEGER PRIMARY KEY,
    beneficiary_v2_id INTEGER,
    user_id INTEGER,
    reference_number VARCHAR(100),
    status VARCHAR(50),
    requested_at TEXT,
    reviewed_at TEXT,
    review_comment TEXT,
    summary_diff TEXT,
    payload_before TEXT,
    payload_after TEXT,
    created_at TEXT,
    updated_at TEXT
)
SQL);

        $db->simpleQuery(<<<'SQL'
CREATE TABLE beneficiary_change_items (
    id INTEGER PRIMARY KEY,
    change_request_id INTEGER,
    entity_type VARCHAR(32),
    entity_identifier VARCHAR(64),
    field_key VARCHAR(120),
    field_label VARCHAR(255),
    old_value TEXT,
    new_value TEXT,
    status VARCHAR(20),
    review_note TEXT,
    reviewed_by INTEGER,
    reviewed_at TEXT,
    created_at TEXT,
    updated_at TEXT
)
SQL);

        $db->simpleQuery(<<<'SQL'
CREATE TABLE beneficiary_change_audit (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    change_request_id INTEGER,
    action VARCHAR(50),
    actor_id INTEGER,
    notes TEXT,
    created_at TEXT
)
SQL);

        $db->simpleQuery(<<<'SQL'
CREATE TABLE beneficiaries_v2 (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    middle_name TEXT,
    last_name TEXT,
    gender TEXT,
    date_of_birth TEXT,
    retirement_or_death_date TEXT,
    deceased_employee_name TEXT,
    rao_id TEXT,
    rao_other TEXT,
    retirement_office_id TEXT,
    retirement_office_other TEXT,
    designation_id TEXT,
    designation_other TEXT,
    correspondence_address TEXT,
    city TEXT,
    state_id TEXT,
    postal_code TEXT,
    ppo_number_masked TEXT,
    pran_number_masked TEXT,
    gpf_number_masked TEXT,
    bank_source_id TEXT,
    bank_source_other TEXT,
    bank_servicing_id TEXT,
    bank_servicing_other TEXT,
    bank_account_masked TEXT,
    aadhaar_masked TEXT,
    pan_masked TEXT,
    primary_mobile_masked TEXT,
    alternate_mobile_masked TEXT,
    email TEXT,
    blood_group_id TEXT,
    samagra_masked TEXT,
    updated_at TEXT
)
SQL);

        $db->simpleQuery(<<<'SQL'
CREATE TABLE user_sessions (
    user_id INTEGER PRIMARY KEY,
    session_id VARCHAR(255),
    last_seen_at TEXT
)
SQL);
    }

    private function resetTestTables(): void
    {
        $db = Database::connect('tests');
        $db->simpleQuery('DELETE FROM beneficiary_change_audit');
        $db->simpleQuery('DELETE FROM beneficiary_change_items');
        $db->simpleQuery('DELETE FROM beneficiary_change_requests');
        $db->simpleQuery('DELETE FROM beneficiaries_v2');
        $db->simpleQuery('DELETE FROM app_users');
        $db->simpleQuery('DELETE FROM user_sessions');
    }
}
