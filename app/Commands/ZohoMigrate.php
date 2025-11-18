<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;
use Exception;

/**
 * Migrates rows from staging_beneficiaries / staging_dependents (Zoho export)
 * into the normalised schema.
 *
 * Examples:
 *   php spark zoho:migrate --batch=20251027
 *   php spark zoho:migrate --batch=20251027 --dry-run
 *   php spark zoho:migrate --batch=20251027 --create-lookups=0
 */
class ZohoMigrate extends BaseCommand
{
    protected $group       = 'zoho';
    protected $name        = 'zoho:migrate';
    protected $description = 'Transform Zoho staging tables into beneficiaries, residences, and dependents.';
    protected $usage       = 'php spark zoho:migrate --batch=ID [--dry-run] [--create-lookups=1]';
    protected $options     = [
        '--batch'          => 'Required. Batch identifier used for the staging rows.',
        '--dry-run'        => 'When present, prints actions without mutating the database.',
        '--create-lookups' => 'Auto-create missing states/cities (1=yes, 0=no). Defaults to 1.',
    ];

    private BaseConnection $db;
    private string $batchId = '';
    private bool $dryRun = false;
    private bool $createLookups = true;

    /** @var array<string,int|null> */
    private array $stateCache = [];
    /** @var array<string,int|null> */
    private array $cityCache = [];
    private array $warnings  = [];

    private int $beneficiariesProcessed = 0;
    private int $residencesProcessed    = 0;
    private int $dependentsProcessed    = 0;

    public function run(array $params)
    {
        $this->db = Database::connect();

        $this->batchId = CLI::getOption('batch') ?? ($params[0] ?? '');
        if ($this->batchId === '') {
            CLI::error('Please provide --batch=YYYYMMDD... to select the staging rows.');
            return;
        }

        $this->dryRun        = CLI::getOption('dry-run') !== null;
        $this->createLookups = (int) (CLI::getOption('create-lookups') ?? 1) === 1;

        CLI::write("Processing Zoho staging batch {$this->batchId}", 'yellow');
        if ($this->dryRun) {
            CLI::write('Dry-run mode: no database changes will be committed.', 'yellow');
        }

        $beneficiaries = $this->db->table('staging_beneficiaries')
            ->where('load_batch_id', $this->batchId)
            ->orderBy('staging_id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($beneficiaries)) {
            CLI::error('No rows found in staging_beneficiaries for the supplied batch id.');
            return;
        }

        $dependentRows = $this->db->table('staging_dependents')
            ->where('load_batch_id', $this->batchId)
            ->orderBy('staging_id', 'ASC')
            ->get()
            ->getResultArray();

        $dependentsByRef = [];
        foreach ($dependentRows as $row) {
            $ref = trim((string) ($row['unique_ref_number'] ?? ''));
            if ($ref === '') {
                continue;
            }
            $dependentsByRef[$ref][] = $row;
        }

        if (! $this->dryRun) {
            $this->db->transBegin();
        }

        try {
            foreach ($beneficiaries as $row) {
                $uniqueRef = trim((string) ($row['unique_ref_number'] ?? ''));
                if ($uniqueRef === '') {
                    $this->warnings[] = 'Skipped a row with blank Unique Reference Number.';
                    continue;
                }

                $beneficiaryId = $this->upsertBeneficiary($row);
                if ($beneficiaryId === 0) {
                    // dry-run insert
                    continue;
                }
                $this->beneficiariesProcessed++;

                $this->syncResidence($beneficiaryId, $row);
                $this->residencesProcessed++;

                $dependentCount = $this->syncDependents(
                    $beneficiaryId,
                    $row,
                    $dependentsByRef[$uniqueRef] ?? []
                );
                $this->dependentsProcessed += $dependentCount;
            }

            if ($this->dryRun) {
                CLI::write('Dry-run finished.', 'yellow');
                $this->report();
                return;
            }

            if ($this->db->transStatus() === false) {
                throw new Exception('Database transaction failed.');
            }

            $this->db->transCommit();
            CLI::write('Batch committed successfully.', 'green');
        } catch (Exception $exception) {
            if (! $this->dryRun) {
                $this->db->transRollback();
            }
            CLI::error('Migration aborted: ' . $exception->getMessage());
            return;
        }

        $this->report();
    }

    private function report(): void
    {
        CLI::write("Beneficiaries processed : {$this->beneficiariesProcessed}");
        CLI::write("Residences refreshed    : {$this->residencesProcessed}");
        CLI::write("Dependents inserted     : {$this->dependentsProcessed}");

        if (! empty($this->warnings)) {
            CLI::write('Warnings:', 'yellow');
            foreach ($this->warnings as $warning) {
                CLI::write('  - ' . $warning, 'yellow');
            }
        }
    }

    private function upsertBeneficiary(array $row): int
    {
        $uniqueRef = trim((string) ($row['unique_ref_number'] ?? ''));
        if ($uniqueRef === '') {
            return 0;
        }

        $data = [
            'unique_ref_number'        => $uniqueRef,
            'category'                 => $this->string($row['category'] ?? null, 50),
            'scheme_option'            => $this->string($row['scheme_option'] ?? null, 15),
            'is_family_pensioner'      => $this->isFamilyPensioner($row),
            'deceased_employee_name'   => $this->string($row['name_of_deceased_employee'] ?? null, 255),
            'first_name'               => $this->string($row['first_name'] ?? null, 100),
            'middle_name'              => $this->string($row['middle_name'] ?? null, 100),
            'last_name'                => $this->string($row['last_name'] ?? null, 100),
            'gender'                   => $this->enum($row['gender'] ?? null, ['MALE','FEMALE','TRANSGENDER']),
            'date_of_birth'            => $this->date($row['date_of_birth'] ?? null),
            'blood_group'              => $this->string($row['blood_group'] ?? null, 5),
            'samagra_id'               => $this->string($row['samagra_id'] ?? null, 50),
            'retirement_date'          => $this->date($row['retirement_date'] ?? null),
            'rao'                      => $this->string($row['rao_retirement'] ?? null, 255),
            'raw_rao'                  => $this->string($row['manual_rao'] ?? null, 255),
            'office_at_retirement'     => $this->string($row['office_retirement'] ?? null, 255),
            'raw_office_at_retirement' => $this->string($row['manual_office_retirement'] ?? null, 255),
            'designation'              => $this->string($row['designation_retirement'] ?? null, 255),
            'raw_designation'          => $this->string($row['manual_designation'] ?? null, 255),
            'mobile_number'            => $this->string($row['mobile_number'] ?? null, 20),
            'alternate_mobile'         => $this->string($row['alternate_mobile'] ?? null, 20),
            'email'                    => $this->string($row['email'] ?? null, 255),
            'ppo_number'               => $this->string($row['ppo_number'] ?? null, 50),
            'gpf_number'               => $this->string($row['gpf_number'] ?? null, 50),
            'pran_number'              => $this->string($row['pran_number'] ?? null, 50),
            'bank_name'                => $this->pickBankName($row),
            'bank_account_number'      => $this->string($row['bank_account_number'] ?? null, 100),
            'aadhar_number'            => $this->stripSpaces($row['aadhar_number'] ?? null),
            'pan_number'               => $this->string($row['pan_details'] ?? null, 20),
            'terms_accepted_at'        => $this->dateTime($row['terms_and_conditions'] ?? null),
            'terms_version'            => $this->termsVersion($row['terms_and_conditions'] ?? null),
            'data_source'              => 'Zoho',
            'load_batch_id'            => $this->batchId,
            'updated_at'               => utc_now(),
        ];

        $existing = $this->db->table('beneficiaries')
            ->select('id')
            ->where('unique_ref_number', $uniqueRef)
            ->get()
            ->getRowArray();

        if ($existing) {
            if ($this->dryRun) {
                CLI::write("Would update beneficiary {$uniqueRef}", 'light_gray');
                return (int) $existing['id'];
            }

            $this->db->table('beneficiaries')
                ->where('id', $existing['id'])
                ->update($data);

            return (int) $existing['id'];
        }

        $data['created_at'] = utc_now();

        if ($this->dryRun) {
            CLI::write("Would insert beneficiary {$uniqueRef}", 'light_gray');
            return 0;
        }

        $this->db->table('beneficiaries')->insert($data);
        return (int) $this->db->insertID();
    }

    private function syncResidence(int $beneficiaryId, array $row): void
    {
        if ($this->dryRun) {
            CLI::write("Would refresh residence for beneficiary {$beneficiaryId}", 'light_gray');
            return;
        }

        $this->db->table('beneficiary_residences')
            ->where('beneficiary_id', $beneficiaryId)
            ->where('residence_type', 'CURRENT')
            ->delete();

        $cityId = $this->resolveCityId(
            $this->string($row['city'] ?? null, 120),
            $this->string($row['state'] ?? null, 120)
        );

        $this->db->table('beneficiary_residences')->insert([
            'beneficiary_id'      => $beneficiaryId,
            'address_line1'       => $this->string($row['address_line1'] ?? null, 255),
            'address_line2'       => $this->string($row['address_line2'] ?? null, 255),
            'postal_code'         => $this->string($row['postal_code'] ?? null, 20),
            'city_id'             => $cityId,
            'residence_type'      => 'CURRENT',
            'is_primary'          => 1,
            'source_address_line' => $this->string($row['address_line1'] ?? null, 500),
            'created_at'          => utc_now(),
            'updated_at'          => utc_now(),
        ]);
    }

    private function syncDependents(int $beneficiaryId, array $row, array $children): int
    {
        if ($this->dryRun) {
            CLI::write("Would replace dependents for beneficiary {$beneficiaryId}", 'light_gray');
            return 0;
        }

        $this->db->table('beneficiary_dependents')
            ->where('beneficiary_id', $beneficiaryId)
            ->delete();

        $inserted  = 0;
        $inserted += $this->insertDependent($beneficiaryId, [
            'relation'                => 'SPOUSE',
            'relation_group'          => 'SPOUSE',
            'source_relation'         => $row['spouse_status'] ?? null,
            'status'                  => $row['spouse_status'] ?? null,
            'is_dependent_for_health' => $row['spouse_dependent_flag'] ?? $row['spouse_dependent'] ?? null,
            'name'                    => $row['spouse_name'] ?? null,
            'gender'                  => $row['spouse_gender'] ?? null,
            'blood_group'             => $row['spouse_blood_group'] ?? null,
            'date_of_birth'           => $row['spouse_dob'] ?? $row['spouse_dob_alt'] ?? null,
            'aadhar_number'           => $row['spouse_aadhar'] ?? $row['spouse_aadhar_alt'] ?? null,
        ]);

        $inserted += $this->insertDependent($beneficiaryId, [
            'relation'                => 'FATHER',
            'relation_group'          => 'PARENT',
            'source_relation'         => $row['father_status'] ?? null,
            'status'                  => $row['father_status'] ?? null,
            'is_dependent_for_health' => $row['father_dependent_flag'] ?? $row['father_dependent_for_health'] ?? null,
            'name'                    => $row['father_name'] ?? null,
            'gender'                  => $row['father_gender'] ?? null,
            'blood_group'             => $row['father_blood_group'] ?? null,
            'date_of_birth'           => $row['father_dob'] ?? $row['father_dob_alt'] ?? null,
            'aadhar_number'           => $row['father_aadhar'] ?? $row['father_aadhar_alt'] ?? null,
        ]);

        $inserted += $this->insertDependent($beneficiaryId, [
            'relation'                => 'MOTHER',
            'relation_group'          => 'PARENT',
            'source_relation'         => $row['mother_status'] ?? null,
            'status'                  => $row['mother_status'] ?? null,
            'is_dependent_for_health' => $row['mother_dependent_flag'] ?? $row['mother_dependent_for_health'] ?? null,
            'name'                    => $row['mother_name'] ?? null,
            'gender'                  => $row['mother_gender'] ?? null,
            'blood_group'             => $row['mother_blood_group'] ?? null,
            'date_of_birth'           => $row['mother_dob'] ?? $row['mother_dob_alt'] ?? null,
            'aadhar_number'           => $row['mother_aadhar'] ?? $row['mother_aadhar_alt'] ?? null,
        ]);

        $sequence = 1;
        foreach ($children as $child) {
            $relation = $this->childRelation($child);
            $inserted += $this->insertDependent($beneficiaryId, [
                'relation'                => $relation,
                'relation_group'          => 'CHILD',
                'source_relation'         => $child['relation'] ?? null,
                'sequence'                => (int) ($child['dependent_order'] ?? $sequence),
                'status'                  => $child['status'] ?? null,
                'is_dependent_for_health' => $child['dependent_for_health'] ?? null,
                'name'                    => $child['name'] ?? null,
                'gender'                  => $child['gender'] ?? null,
                'blood_group'             => $child['blood_group'] ?? null,
                'date_of_birth'           => $child['date_of_birth'] ?? null,
                'aadhar_number'           => $child['aadhar_number'] ?? null,
                'notes'                   => $this->childNotes($relation, $child['relation'] ?? null),
            ]);
            $sequence++;
        }

        return $inserted;
    }

    private function insertDependent(int $beneficiaryId, array $payload): int
    {
        $hasData =
            trim((string) ($payload['name'] ?? '')) !== '' ||
            trim((string) ($payload['aadhar_number'] ?? '')) !== '' ||
            trim((string) ($payload['status'] ?? '')) !== '';

        if (! $hasData) {
            return 0;
        }

        $data = [
            'beneficiary_id'          => $beneficiaryId,
            'relation'                => strtoupper($payload['relation'] ?? 'OTHER'),
            'relation_group'          => strtoupper($payload['relation_group'] ?? 'OTHER'),
            'source_relation'         => $this->string($payload['source_relation'] ?? null, 100),
            'sequence'                => isset($payload['sequence']) ? (int) $payload['sequence'] : null,
            'status'                  => $this->status($payload['status'] ?? null),
            'alive'                   => $this->alive($payload['status'] ?? null),
            'is_dependent_for_health' => $this->bool($payload['is_dependent_for_health'] ?? null),
            'name'                    => $this->string($payload['name'] ?? null, 255),
            'gender'                  => $this->enum($payload['gender'] ?? null, ['MALE','FEMALE','TRANSGENDER']),
            'blood_group'             => $this->string($payload['blood_group'] ?? null, 5),
            'date_of_birth'           => $this->date($payload['date_of_birth'] ?? null),
            'aadhar_number'           => $this->stripSpaces($payload['aadhar_number'] ?? null),
            'notes'                   => $this->string($payload['notes'] ?? null, 255),
            'data_source'             => 'Zoho',
            'load_batch_id'           => $this->string($this->batchId, 50),
            'created_at'              => utc_now(),
            'updated_at'              => utc_now(),
        ];

        if ($this->dryRun) {
            CLI::write("Would insert dependent {$data['relation']} for beneficiary {$beneficiaryId}", 'light_gray');
            return 1;
        }

        $this->db->table('beneficiary_dependents')->insert($data);
        return 1;
    }

    private function string(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return mb_strlen($value) > $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }

    private function enum(?string $value, array $allowed): ?string
    {
        if ($value === null) {
            return null;
        }
        $upper = strtoupper(trim($value));
        return in_array($upper, $allowed, true) ? $upper : null;
    }

    private function date(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        $formats = ['d/m/Y','d-m-Y','Y-m-d','d-M-Y','d/m/y','m/d/Y'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d');
        } catch (Exception $exception) {
            $this->warnings[] = "Unable to parse date '{$value}'.";
            return null;
        }
    }

    private function dateTime(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d/m/Y H:i','d-m-Y H:i','Y-m-d H:i:s','d/m/Y'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            return null;
        }
    }

    private function termsVersion(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '' || strcasecmp($trimmed, 'Accepted') === 0) {
            return null;
        }
        return $trimmed;
    }

    private function stripSpaces(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = preg_replace('/\s+/', '', $value);
        $clean = trim((string) $clean);
        return $clean === '' ? null : $clean;
    }

    private function bool($value): ?int
    {
        if ($value === null) {
            return null;
        }
        $clean = strtoupper(trim((string) $value));
        if ($clean === '') {
            return null;
        }
        $truthy = ['Y','YES','TRUE','1','DEPENDENT'];
        $falsy  = ['N','NO','FALSE','0','NOT DEPENDENT','INDEPENDENT'];
        if (in_array($clean, $truthy, true)) {
            return 1;
        }
        if (in_array($clean, $falsy, true)) {
            return 0;
        }
        return null;
    }

    private function status(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $upper = strtoupper(trim($value));
        $map = [
            'ALIVE'          => 'ALIVE',
            'NOT ALIVE'      => 'NOT ALIVE',
            'NOT APPLICABLE' => 'NOT APPLICABLE',
            'NA'             => 'NOT APPLICABLE',
        ];
        return $map[$upper] ?? null;
    }

    private function alive(?string $status): ?int
    {
        $status = strtoupper(trim((string) ($status ?? '')));
        if ($status === '') {
            return null;
        }
        if (in_array($status, ['ALIVE','YES'], true)) {
            return 1;
        }
        if (in_array($status, ['NOT ALIVE','DECEASED','NO'], true)) {
            return 0;
        }
        return null;
    }

    private function pickBankName(array $row): ?string
    {
        $primary = $row['bank_name_primary'] ?? null;
        $fallback = $row['bank_name_secondary'] ?? null;
        $primary = $this->string($primary, 255);
        if ($primary !== null) {
            return $primary;
        }
        return $this->string($fallback, 255);
    }

    private function isFamilyPensioner(array $row): int
    {
        $category = strtoupper(trim((string) ($row['category'] ?? '')));
        return str_contains($category, 'FAMILY') ? 1 : 0;
    }

    private function childRelation(array $child): string
    {
        $explicit = strtoupper(trim((string) ($child['relation'] ?? '')));
        if (in_array($explicit, ['SON','DAUGHTER'], true)) {
            return $explicit;
        }
        $gender = strtoupper(trim((string) ($child['gender'] ?? '')));
        if ($gender === 'MALE') {
            return 'SON';
        }
        if ($gender === 'FEMALE') {
            return 'DAUGHTER';
        }
        return 'OTHER';
    }

    private function childNotes(string $resolved, ?string $original): ?string
    {
        $original = trim((string) ($original ?? ''));
        if ($original === '' || strtoupper($original) === $resolved) {
            return null;
        }
        return 'Legacy relation: ' . $original;
    }

    private function resolveStateId(?string $stateName): ?int
    {
        if ($stateName === null || $stateName === '') {
            return null;
        }

        $upper = strtoupper($stateName);
        if (array_key_exists($upper, $this->stateCache)) {
            return $this->stateCache[$upper];
        }

        $row = $this->db->table('states')
            ->select('state_id')
            ->where('UPPER(state_name)', $upper)
            ->get()
            ->getRowArray();

        if ($row) {
            return $this->stateCache[$upper] = (int) $row['state_id'];
        }

        if (! $this->createLookups) {
            $this->warnings[] = "State '{$stateName}' missing (auto-create disabled).";
            return $this->stateCache[$upper] = null;
        }

        if ($this->dryRun) {
            CLI::write("Would create state '{$stateName}'", 'light_gray');
            return $this->stateCache[$upper] = null;
        }

        $this->db->table('states')->insert(['state_name' => $stateName]);
        return $this->stateCache[$upper] = (int) $this->db->insertID();
    }

    private function resolveCityId(?string $cityName, ?string $stateName): ?int
    {
        if ($cityName === null || $cityName === '') {
            return null;
        }

        $stateId = $this->resolveStateId($stateName);
        $cacheKey = strtoupper($cityName) . '|' . (string) ($stateId ?? 0);
        if (array_key_exists($cacheKey, $this->cityCache)) {
            return $this->cityCache[$cacheKey];
        }

        $builder = $this->db->table('cities')
            ->select('city_id')
            ->where('UPPER(city_name)', strtoupper($cityName));
        if ($stateId !== null) {
            $builder->where('state_id', $stateId);
        }

        $row = $builder->get()->getRowArray();
        if ($row) {
            return $this->cityCache[$cacheKey] = (int) $row['city_id'];
        }

        if (! $this->createLookups || $stateId === null) {
            $this->warnings[] = "City '{$cityName}' (State '{$stateName}') missing – storing NULL.";
            return $this->cityCache[$cacheKey] = null;
        }

        if ($this->dryRun) {
            CLI::write("Would create city '{$cityName}' under state ID {$stateId}", 'light_gray');
            return $this->cityCache[$cacheKey] = null;
        }

        $this->db->table('cities')->insert([
            'city_name' => $cityName,
            'state_id'  => $stateId,
        ]);
        return $this->cityCache[$cacheKey] = (int) $this->db->insertID();
    }
}
