<?php

namespace Tests\Unit\Services;

use App\Services\BeneficiaryV2SnapshotService;
use CodeIgniter\I18n\Time;
use Tests\Support\EnrollmentTestCase;

class BeneficiaryV2SnapshotServiceTest extends EnrollmentTestCase
{
    public function testSnapshotOmitsInactiveDependents(): void
    {
        $beneficiary = $this->createBeneficiary();
        $active      = $this->createDependent($beneficiary['id'], [
            'dependant_order' => 1,
            'first_name'      => 'Active Dependent',
        ]);

        $inactive = $this->createDependent($beneficiary['id'], [
            'dependant_order' => 2,
            'first_name'      => 'Inactive Dependent',
        ]);

        db_connect()
            ->table('beneficiary_dependents_v2')
            ->where('id', $inactive['id'])
            ->update([
                'is_active'  => 0,
                'deleted_at' => Time::now('UTC')->toDateTimeString(),
                'deleted_by' => 42,
            ]);

        $service = new BeneficiaryV2SnapshotService();
        $snapshot = $service->findByBeneficiaryId($beneficiary['id']);

        $this->assertNotNull($snapshot);
        $this->assertArrayHasKey('dependents', $snapshot);
        $this->assertCount(1, $snapshot['dependents']);
        $this->assertSame((string) $active['id'], (string) $snapshot['dependents'][0]['id']);
        $this->assertSame('Active Dependent', $snapshot['dependents'][0]['first_name'] ?? null);
    }
}
