<?php

namespace App\Services;

use App\Models\BeneficiaryDependentV2Model;
use App\Models\BeneficiaryV2Model;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\BaseBuilder;
use Config\Database;

class BeneficiaryV2SnapshotService
{
    private ConnectionInterface $db;
    private BeneficiaryV2Model $beneficiaries;
    private BeneficiaryDependentV2Model $dependents;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? Database::connect();
        $this->beneficiaries = new BeneficiaryV2Model($this->db);
        $this->dependents    = new BeneficiaryDependentV2Model($this->db);
    }

    public function findByBeneficiaryId(int $beneficiaryId): ?array
    {
        if ($beneficiaryId <= 0) {
            return null;
        }

        $builder = $this->baseBuilder()
            ->where('beneficiaries_v2.id', $beneficiaryId)
            ->limit(1);

        return $this->finalizeRecord($builder, $beneficiaryId);
    }

    public function findByLegacyBeneficiaryId(?int $legacyId): ?array
    {
        if ($legacyId === null || $legacyId <= 0) {
            return null;
        }

        $builder = $this->baseBuilder()
            ->where('beneficiaries_v2.legacy_beneficiary_id', $legacyId)
            ->limit(1);

        $record = $this->finalizeRecord($builder);
        if ($record !== null) {
            return $record;
        }

        return $this->buildLegacySnapshot($legacyId);
    }

    private function finalizeRecord(BaseBuilder $builder, ?int $beneficiaryId = null): ?array
    {
        $record = $builder->get()->getRowArray();
        if (! $record) {
            return null;
        }

        $dependentId = $beneficiaryId ?? (int) $record['id'];
        $record['dependents'] = $this->fetchDependents($dependentId);

        return $record;
    }

    private function baseBuilder(): BaseBuilder
    {
        $builder = $this->beneficiaries->builder();
        $builder
            ->select([
                'beneficiaries_v2.id',
                'beneficiaries_v2.legacy_beneficiary_id',
                'beneficiaries_v2.reference_number',
                'beneficiaries_v2.legacy_reference',
                'beneficiaries_v2.plan_option_id',
                'beneficiaries_v2.category_id',
                'beneficiaries_v2.first_name',
                'beneficiaries_v2.middle_name',
                'beneficiaries_v2.last_name',
                'beneficiaries_v2.gender',
                'beneficiaries_v2.date_of_birth',
                'beneficiaries_v2.retirement_or_death_date',
                'beneficiaries_v2.deceased_employee_name',
                'beneficiaries_v2.rao_id',
                'beneficiaries_v2.rao_other',
                'beneficiaries_v2.retirement_office_id',
                'beneficiaries_v2.retirement_office_other',
                'beneficiaries_v2.designation_id',
                'beneficiaries_v2.designation_other',
                'beneficiaries_v2.correspondence_address',
                'beneficiaries_v2.city',
                'beneficiaries_v2.state_id',
                'beneficiaries_v2.postal_code',
                'beneficiaries_v2.ppo_number_masked',
                'beneficiaries_v2.pran_number_masked',
                'beneficiaries_v2.gpf_number_masked',
                'beneficiaries_v2.bank_source_id',
                'beneficiaries_v2.bank_source_other',
                'beneficiaries_v2.bank_servicing_id',
                'beneficiaries_v2.bank_servicing_other',
                'beneficiaries_v2.bank_account_masked',
                'beneficiaries_v2.aadhaar_masked',
                'beneficiaries_v2.pan_masked',
                'beneficiaries_v2.primary_mobile_masked',
                'beneficiaries_v2.alternate_mobile_masked',
                'beneficiaries_v2.email',
                'beneficiaries_v2.blood_group_id',
                'beneficiaries_v2.samagra_masked',
                'beneficiaries_v2.terms_accepted_at',
                'beneficiaries_v2.otp_verified_at',
                'beneficiaries_v2.otp_reference',
                'beneficiaries_v2.submission_source',
                'beneficiaries_v2.version',
                'beneficiaries_v2.pending_review',
                'beneficiaries_v2.created_at',
                'beneficiaries_v2.updated_at',
                'plan_options.label AS plan_option_label',
                'beneficiary_categories.label AS category_label',
                'regional_account_offices.name AS rao_label',
                'retirement_offices.name AS retirement_office_label',
                'designations_ref.title AS designation_label',
                'bank_source.name AS bank_source_label',
                'bank_servicing.name AS bank_servicing_label',
                'blood_groups_ref.label AS blood_group_label',
                'states.state_name AS state_name',
            ])
            ->join('plan_options', 'plan_options.id = beneficiaries_v2.plan_option_id', 'left')
            ->join('beneficiary_categories', 'beneficiary_categories.id = beneficiaries_v2.category_id', 'left')
            ->join('regional_account_offices', 'regional_account_offices.id = beneficiaries_v2.rao_id', 'left')
            ->join('retirement_offices', 'retirement_offices.id = beneficiaries_v2.retirement_office_id', 'left')
            ->join('designations_ref', 'designations_ref.id = beneficiaries_v2.designation_id', 'left')
            ->join('banks_ref bank_source', 'bank_source.id = beneficiaries_v2.bank_source_id', 'left')
            ->join('banks_ref bank_servicing', 'bank_servicing.id = beneficiaries_v2.bank_servicing_id', 'left')
            ->join('blood_groups_ref', 'blood_groups_ref.id = beneficiaries_v2.blood_group_id', 'left')
            ->join('states', 'states.state_id = beneficiaries_v2.state_id', 'left');

        return $builder;
    }

    private function fetchDependents(int $beneficiaryId): array
    {
        $builder = $this->dependents->builder();
        $builder
            ->select([
                'beneficiary_dependents_v2.id',
                'beneficiary_dependents_v2.beneficiary_id',
                'beneficiary_dependents_v2.relationship',
                'beneficiary_dependents_v2.dependant_order',
                'beneficiary_dependents_v2.twin_group',
                'beneficiary_dependents_v2.is_alive',
                'beneficiary_dependents_v2.is_health_dependant',
                'beneficiary_dependents_v2.first_name',
                'beneficiary_dependents_v2.gender',
                'beneficiary_dependents_v2.blood_group_id',
                'beneficiary_dependents_v2.date_of_birth',
                'beneficiary_dependents_v2.aadhaar_masked',
                'beneficiary_dependents_v2.created_at',
                'beneficiary_dependents_v2.updated_at',
                'beneficiary_dependents_v2.is_active',
                'beneficiary_dependents_v2.deleted_at',
                'beneficiary_dependents_v2.deleted_by',
                'beneficiary_dependents_v2.restored_at',
                'beneficiary_dependents_v2.restored_by',
                'blood_groups_ref.label AS blood_group_label',
            ])
            ->join('blood_groups_ref', 'blood_groups_ref.id = beneficiary_dependents_v2.blood_group_id', 'left')
            ->where('beneficiary_dependents_v2.beneficiary_id', $beneficiaryId)
            ->where('beneficiary_dependents_v2.is_active', 1)
            ->orderBy('beneficiary_dependents_v2.dependant_order', 'ASC');

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['full_name']          = $this->buildDependentName($row);
            $row['relationship_key']   = $this->normalizeRelationshipKey($row['relationship'] ?? null);
            $row['health_status_raw']  = $this->normalizeHealthStatus($row['is_health_dependant'] ?? null);
            $row['alive_status_raw']   = $this->normalizeAliveStatus($row['is_alive'] ?? null);
            $row['health_label']       = $this->humanizeHealthStatus($row['health_status_raw']);
            $row['alive_status_label'] = $this->humanizeAliveStatus($row['alive_status_raw']);
            $row['gender_label']       = $this->humanizeGender($row['gender'] ?? null);
            $row['order']              = $row['dependant_order'] !== null
                ? (int) $row['dependant_order']
                : null;
        }

        return $rows;
    }

    private function buildDependentName(array $row): ?string
    {
        $parts = [];

        foreach (['first_name', 'middle_name', 'last_name'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if (! empty($parts)) {
            return implode(' ', $parts);
        }

        $fallback = trim((string) ($row['first_name'] ?? ''));

        return $fallback !== '' ? $fallback : null;
    }

    private function normalizeRelationshipKey(?string $value): ?string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeHealthStatus(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 'yes' : 'no';
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'yes', 'y', '1'                       => 'yes',
            'no', 'n', '0'                        => 'no',
            'not_applicable', 'not applicable', 'na' => 'not_applicable',
            default                               => null,
        };
    }

    private function normalizeAliveStatus(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'alive' : 'not_alive';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 'alive' : 'not_alive';
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'alive', 'yes', 'y', '1'                  => 'alive',
            'not_alive', 'not alive', 'no', 'n', '0', 'deceased', 'dead' => 'not_alive',
            'not_applicable', 'not applicable', 'na'  => 'not_applicable',
            default                                   => null,
        };
    }

    private function humanizeHealthStatus(?string $value): ?string
    {
        return match ($value) {
            'yes'            => 'Yes',
            'no'             => 'No',
            'not_applicable' => 'Not Applicable',
            default          => null,
        };
    }

    private function humanizeAliveStatus(?string $value): ?string
    {
        return match ($value) {
            'alive'          => 'Alive',
            'not_alive'      => 'Not Alive',
            'not_applicable' => 'Not Applicable',
            default          => null,
        };
    }

    private function humanizeGender(?string $value): ?string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));

        return match ($normalized) {
            ''             => null,
            'male', 'm'    => 'Male',
            'female', 'f'  => 'Female',
            'transgender'  => 'Transgender',
            default        => ucwords($normalized),
        };
    }

    private function buildLegacySnapshot(int $legacyId): ?array
    {
        $row = $this->db->table('beneficiaries')
            ->where('id', $legacyId)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        $record = [
            'id'                       => null,
            'legacy_beneficiary_id'    => $legacyId,
            'reference_number'         => $row['unique_ref_number'] ?? null,
            'legacy_reference'         => $row['unique_ref_number'] ?? null,
            'plan_option_label'        => $row['scheme_option'] ?? null,
            'category_label'           => $row['category'] ?? null,
            'first_name'               => $row['first_name'] ?? null,
            'middle_name'              => $row['middle_name'] ?? null,
            'last_name'                => $row['last_name'] ?? null,
            'gender'                   => strtolower((string) ($row['gender'] ?? '')) ?: null,
            'date_of_birth'            => $row['date_of_birth'] ?? null,
            'retirement_or_death_date' => $row['retirement_date'] ?? null,
            'correspondence_address'   => $this->formatLegacyAddress($row),
            'city'                     => $row['city'] ?? null,
            'state_name'               => $row['state'] ?? null,
            'state_id'                 => null,
            'postal_code'              => $row['postal_code'] ?? null,
            'primary_mobile_masked'    => $this->maskPhone($row['mobile_number'] ?? null),
            'alternate_mobile_masked'  => $this->maskPhone($row['alternate_mobile'] ?? null),
            'email'                    => $row['email'] ?? null,
            'bank_source_label'        => $row['bank_name'] ?? null,
            'bank_servicing_label'     => null,
            'bank_account_masked'      => $this->maskAccount($row['bank_account_number'] ?? null),
            'aadhaar_masked'           => $this->maskNumber($row['aadhar_number'] ?? null, 4),
            'pan_masked'               => $this->maskAlphaNumeric($row['pan_number'] ?? null),
            'ppo_number_masked'        => $this->maskAlphaNumeric($row['ppo_number'] ?? null),
            'gpf_number_masked'        => $this->maskAlphaNumeric($row['gpf_number'] ?? null),
            'pran_number_masked'       => $this->maskAlphaNumeric($row['pran_number'] ?? null),
            'samagra_masked'           => $this->maskNumber($row['samagra_id'] ?? null, 4),
            'updated_at'               => $row['updated_at'] ?? $row['created_at'] ?? null,
            'created_at'               => $row['created_at'] ?? null,
            'dependents'               => $this->fetchLegacyDependents($legacyId),
        ];

        return $record;
    }

    private function formatLegacyAddress(array $row): string
    {
        $lines = array_filter([
            trim((string) ($row['address_line1'] ?? '')),
            trim((string) ($row['address_line2'] ?? '')),
        ]);

        return implode(PHP_EOL, $lines);
    }

    private function fetchLegacyDependents(int $legacyId): array
    {
        $rows = $this->db->table('beneficiary_dependents')
            ->where('beneficiary_id', $legacyId)
            ->orderBy('sequence', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(function (array $row): array {
            $name = trim((string) ($row['name'] ?? ''));
            return [
                'full_name'         => $name !== '' ? $name : null,
                'relationship'      => $this->normalizeRelation($row['relation'] ?? null),
                'dependant_order'   => $row['sequence'] ?? null,
                'is_alive'          => $this->convertLegacyStatus($row['status'] ?? null),
                'is_health_dependant' => $row['is_dependent_for_health'] ?? null,
                'blood_group_label' => $row['blood_group'] ?? null,
                'date_of_birth'     => $row['date_of_birth'] ?? null,
                'aadhaar_masked'    => $this->maskNumber($row['aadhar_number'] ?? null, 4),
            ];
        }, $rows);
    }

    private function normalizeRelation(?string $relation): ?string
    {
        if ($relation === null) {
            return null;
        }

        return ucwords(strtolower(str_replace('_', ' ', $relation)));
    }

    private function convertLegacyStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $status = strtolower($status);
        return match ($status) {
            'alive'       => 'alive',
            'not alive',
            'deceased'    => 'not_alive',
            'not applicable' => 'not_applicable',
            default       => null,
        };
    }

    private function maskPhone(?string $value): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $value);
        if ($value === '') {
            return null;
        }

        return $this->maskTrailing($value, 4);
    }

    private function maskAccount(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $this->maskTrailing($value, 4);
    }

    private function maskNumber(?string $value, int $visible): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        return $this->maskTrailing($digits, $visible);
    }

    private function maskAlphaNumeric(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $this->maskTrailing($value, 3);
    }

    private function maskTrailing(string $value, int $visible): string
    {
        $length = strlen($value);
        if ($length <= $visible) {
            return str_repeat('*', $length);
        }

        $masked = str_repeat('*', max(0, $length - $visible));
        return $masked . substr($value, -$visible);
    }
}
