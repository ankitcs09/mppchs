<?php

namespace Tests\Unit\Services;

use App\Services\BeneficiaryExportService;
use Config\Database;
use Tests\Support\EnrollmentTestCase;

class BeneficiaryExportServiceTest extends EnrollmentTestCase
{
    private BeneficiaryExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BeneficiaryExportService(
            Database::connect($this->DBGroup),
            $this->crypto
        );
    }

    public function testExportReturnsBeneficiaryAndDependents(): void
    {
        $beneficiary = $this->createBeneficiary([
            'reference_number'      => 'BEN-EXPORT-001',
            'first_name'            => 'Naeem',
            'last_name'             => 'Khan',
            'primary_mobile_enc'    => $this->crypto->encrypt('919926783609'),
            'primary_mobile_masked' => 'XXXXXXXX3609',
            'alternate_mobile_enc'  => $this->crypto->encrypt('917748916447'),
            'alternate_mobile_masked' => 'XXXXXXXX6447',
        ]);

        $this->createDependent($beneficiary['id'], [
            'relationship'        => 'spouse',
            'first_name'          => 'Rasheda',
            'gender'              => 'female',
            'date_of_birth'       => '1963-01-01',
        ]);

        $result = $this->service->export([
            'company_code' => 'MPPGCL',
            'limit'        => 10,
        ]);

        $this->assertCount(2, $result['data']);

        $selfRow = $result['data'][0];
        $this->assertSame('Self', $selfRow['relation']);
        $this->assertSame('BEN-EXPORT-001', $selfRow['reference_number']);
        $this->assertSame('Naeem Khan', $selfRow['name']);
        $this->assertSame('919926783609', $selfRow['mobile']);

        $dependentRow = $result['data'][1];
        $this->assertSame('Spouse', $dependentRow['relation']);
        $this->assertSame('Rasheda', $dependentRow['name']);
        $this->assertSame('FEMALE', $dependentRow['gender']);
        $this->assertSame('01-Jan-1963', $dependentRow['date_of_birth']);
        $this->assertSame('919926783609', $dependentRow['mobile']);
    }

    public function testExportAppliesFilters(): void
    {
        $first = $this->createBeneficiary(['reference_number' => 'BEN-EXPORT-A']);
        $this->createDependent($first['id'], ['relationship' => 'spouse']);

        $second = $this->createBeneficiary(['reference_number' => 'BEN-EXPORT-B']);
        $this->createDependent($second['id'], ['relationship' => 'child']);

        $result = $this->service->export([
            'reference' => ['BEN-EXPORT-B'],
            'limit'     => 50,
        ]);

        $this->assertCount(2, $result['data']); // self + dependent
        $this->assertSame('BEN-EXPORT-B', $result['data'][0]['reference_number']);
        $this->assertSame('BEN-EXPORT-B', $result['data'][1]['reference_number']);

        $pagination = $result['pagination'];
        $this->assertSame(1, $pagination['page']);
        $this->assertSame(50, $pagination['per_page']);
        $this->assertSame(1, $pagination['total']); // single beneficiary
    }
}
