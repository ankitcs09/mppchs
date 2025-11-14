<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\FeatureTestCase;

class ChangeRequestFlowTest extends FeatureTestCase
{
    use FeatureTestTrait;

    public function testAdminListRequiresAuth(): void
    {
        $response = $this->get('admin/change-requests');
        $response->assertStatus(403);
    }

    public function testAdminListLoadsWithPermissions(): void
    {
        $this->seedChangeRequest();

        $response = $this->withSession($this->adminSession())->get('admin/change-requests');

        $response->assertStatus(200);
    }

    public function testAdminCanApproveChangeRequest(): void
    {
        $this->seedChangeRequest();
        $session = $this->adminSession();

        $this->withSession($session)->post('admin/change-requests/1/items/1', [
            'status' => 'approved',
            'note'   => 'Looks good',
        ])->assertRedirect();

        $this->withSession($session)->post('admin/change-requests/1/approve')->assertRedirect();

        $db  = Database::connect('tests');
        $row = $db->table('beneficiary_change_requests')->where('id', 1)->get()->getRowArray();

        $this->assertSame('approved', $row['status']);
    }
}
