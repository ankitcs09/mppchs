<?php

namespace Tests\Unit\Services;

use App\Models\BeneficiaryChangeAuditModel;
use App\Models\BeneficiaryChangeDependentModel;
use App\Models\BeneficiaryChangeRequestModel;
use App\Models\BeneficiaryDependentV2Model;
use App\Models\BeneficiaryV2Model;
use App\Services\BeneficiaryChangeRequestService;
use Config\Database;
use ReflectionClass;
use Tests\Support\EnrollmentTestCase;

class BeneficiaryChangeRequestServiceTest extends EnrollmentTestCase
{
    private BeneficiaryChangeRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $db = Database::connect($this->DBGroup);

        $this->service = new BeneficiaryChangeRequestService(
            new BeneficiaryChangeRequestModel($db),
            new BeneficiaryChangeDependentModel($db),
            new BeneficiaryChangeAuditModel($db),
            new BeneficiaryDependentV2Model($db),
            new BeneficiaryV2Model($db),
            $db
        );
    }

    public function testApplySoftDeletesDependent(): void
    {
        $beneficiary = $this->createBeneficiary();
        $dependent   = $this->createDependent($beneficiary['id']);

        $this->applyChanges(
            $beneficiary['id'],
            [
                'beneficiary' => [],
                'dependents'  => [
                    [
                        'action' => 'remove',
                        'id'     => $dependent['id'],
                        'data'   => ['id' => $dependent['id']],
                    ],
                ],
            ],
            99
        );

        $row = Database::connect($this->DBGroup)
            ->table('beneficiary_dependents_v2')
            ->where('id', $dependent['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('0', (string) $row['is_active']);
        $this->assertSame(99, (int) $row['deleted_by']);
        $this->assertNotNull($row['deleted_at']);
    }

    public function testApplyReactivatesInactiveDependent(): void
    {
        $beneficiary = $this->createBeneficiary();
        $dependent   = $this->createDependent($beneficiary['id']);

        // First, soft delete the dependent.
        $this->applyChanges(
            $beneficiary['id'],
            [
                'beneficiary' => [],
                'dependents'  => [
                    [
                        'action' => 'remove',
                        'id'     => $dependent['id'],
                        'data'   => ['id' => $dependent['id']],
                    ],
                ],
            ],
            50
        );

        // Now, reactivate with updated details.
        $this->applyChanges(
            $beneficiary['id'],
            [
                'beneficiary' => [],
                'dependents'  => [
                    [
                        'action' => 'update',
                        'id'     => $dependent['id'],
                        'data'   => [
                            'id'                  => $dependent['id'],
                            'relationship'        => 'spouse',
                            'dependant_order'     => 1,
                            'is_alive'            => 'alive',
                            'is_health_dependant' => 'yes',
                            'first_name'          => 'Reactivate Dependent',
                            'gender'              => 'female',
                            'blood_group_id'      => 1,
                            'date_of_birth'       => '1975-02-02',
                            'aadhaar'             => '444455556666',
                        ],
                    ],
                ],
            ],
            77
        );

        $row = Database::connect($this->DBGroup)
            ->table('beneficiary_dependents_v2')
            ->where('id', $dependent['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('1', (string) $row['is_active']);
        $this->assertSame(77, (int) $row['restored_by']);
        $this->assertNotNull($row['restored_at']);
        $this->assertSame('Reactivate Dependent', $row['first_name']);
    }

    public function testApplyCreatesNewDependent(): void
    {
        $beneficiary = $this->createBeneficiary();

        $this->applyChanges(
            $beneficiary['id'],
            [
                'beneficiary' => [],
                'dependents'  => [
                    [
                        'action' => 'add',
                        'data'   => [
                            'relationship'        => 'child',
                            'dependant_order'     => 2,
                            'is_alive'            => 'alive',
                            'is_health_dependant' => 'yes',
                            'first_name'          => 'New Child',
                            'gender'              => 'male',
                            'blood_group_id'      => 1,
                            'date_of_birth'       => '2005-05-05',
                            'aadhaar'             => '777788889999',
                        ],
                    ],
                ],
            ],
            88
        );

        $rows = Database::connect($this->DBGroup)
            ->table('beneficiary_dependents_v2')
            ->where('beneficiary_id', $beneficiary['id'])
            ->orderBy('dependant_order', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(1, $rows);

        $newRow = $rows[0];

        $this->assertSame('child', $newRow['relationship']);
        $this->assertSame('1', (string) $newRow['is_active']);
        $this->assertSame(88, (int) $newRow['created_by']);
        $this->assertNotNull($newRow['created_at']);
    }

    private function applyChanges(int $beneficiaryId, array $payload, int $reviewerId): void
    {
        $request = [
            'id'                => 1,
            'beneficiary_v2_id' => $beneficiaryId,
            'status'            => 'pending',
        ];

        $reflection = new ReflectionClass(BeneficiaryChangeRequestService::class);
        $method = $reflection->getMethod('applyApprovedChanges');
        $method->setAccessible(true);
        $method->invoke($this->service, $request, $payload, $reviewerId);
    }
}
