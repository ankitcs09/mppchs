<?php

namespace App\Services;

use App\Config\EnrollmentV2Masters;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use RuntimeException;
use SplFileObject;
use Throwable;

class EnrollmentV2Importer
{
    private const COLUMN_COUNT = 83;
    private const STATUS_ALIVE = 'alive';
    private const STATUS_NOT_ALIVE = 'not_alive';
    private const STATUS_NOT_APPLICABLE = 'not_applicable';
    private const HEALTH_YES = 'yes';
    private const HEALTH_NO = 'no';
    private const HEALTH_NOT_APPLICABLE = 'not_applicable';

    private ConnectionInterface $db;
    private SensitiveDataService $crypto;
    private string $importDirectory;
    private string $defaultFilename = 'zoho_export.csv';

    private array $planOptions = [];
    private array $categories = [];
    private array $bloodGroups = [];
    private array $banks = [];
    private array $designations = [];
    private array $raos = [];
    private array $retirementOffices = [];
    private array $states = [];
    private array $synonyms = [];

    private ?int $raoOtherId = null;
    private ?int $retirementOtherId = null;
    private ?int $designationOtherId = null;
    private ?int $bankOtherId = null;

    public function __construct(?ConnectionInterface $db = null, ?SensitiveDataService $crypto = null)
    {
        $this->db = $db ?? db_connect();
        $this->db->transException(true);
        $this->importDirectory = WRITEPATH . 'imports/enrollment_v2/';
        $this->crypto = $crypto ?? new SensitiveDataService();
        $this->primeLookups();
        $this->loadSynonyms();
    }

    /**
     * Import CSV into beneficiaries + dependents tables.
     *
     * @return array<string,mixed>
     */
    public function import(?string $filename = null, ?string $batchId = null, bool $dryRun = false): array
    {
        $filePath = $this->resolveFilePath($filename);
        if (! is_file($filePath)) {
            throw new RuntimeException("CSV file not found: {$filePath}");
        }

        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(',', '"', '\\');

        // Skip header row.
        if (! $file->eof()) {
            $file->fgetcsv();
        }

        $batchId = $batchId ?? date('YmdHis');
        $summary = [
            'batch_id'   => $batchId,
            'file'       => $filePath,
            'processed'  => 0,
            'created'    => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'dry_run'    => $dryRun,
            'errors'     => [],
        ];

        $rowNumber = 0;
        while (! $file->eof()) {
            $row = $file->fgetcsv();
            if ($row === false || $row === null) {
                continue;
            }
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $rowNumber++;
            $summary['processed']++;

            try {
                if ($dryRun) {
                    // Validate mapping without touching the datastore.
                    $this->mapBeneficiaryRow($row, $batchId, $rowNumber);
                    continue;
                }

                $result = $this->persistRow($row, $batchId, $rowNumber);
                if ($result === 'created') {
                    $summary['created']++;
                } elseif ($result === 'updated') {
                    $summary['updated']++;
                } else {
                    $summary['skipped']++;
                }
            } catch (Throwable $exception) {
                $summary['errors'][] = sprintf(
                    'Row %d: %s',
                    $rowNumber,
                    $exception->getMessage()
                );
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    private function persistRow(array $row, string $batchId, int $rowNumber): string
    {
        $mapped = $this->mapBeneficiaryRow($row, $batchId, $rowNumber);
        $beneficiaryData = $mapped['beneficiary'];
        $dependents = $mapped['dependents'];
        $metadata = $mapped['meta'];

        $reference = $beneficiaryData['reference_number'];
        $legacyReferenceKey = $beneficiaryData['legacy_reference'] ?? null;

        $existingBuilder = $this->db->table('beneficiaries_v2');
        if ($legacyReferenceKey !== null) {
            $existingBuilder->where('legacy_reference', $legacyReferenceKey);
        } else {
            $existingBuilder->where('reference_number', $reference);
        }
        $existing = $existingBuilder->get()->getRowArray();

        if (! $existing && $legacyReferenceKey !== null) {
            $fallback = $this->db->table('beneficiaries_v2')
                ->where('reference_number', $legacyReferenceKey)
                ->get()
                ->getRowArray();
            if ($fallback) {
                $existing = $fallback;
            }
        }

        $now = Time::now('UTC')->toDateTimeString();
        $addedAt = $metadata['added_at'] ?? $now;

        $this->db->transStart();

        $changeType = 'create';
        $diff = [];
        $beneficiaryId = null;

        if ($existing) {
            $beneficiaryId = (int) $existing['id'];

            $updateData = $beneficiaryData;
            unset($updateData['created_at'], $updateData['version']);

            $updateData['updated_at'] = $now;
            $updateData['version'] = (int) $existing['version'] + 1;

            $diff = $this->calculateDiff($existing, $updateData);

            $this->db->table('beneficiaries_v2')
                ->where('id', $beneficiaryId)
                ->update($updateData);

            $changeType = 'update';
        } else {
            $beneficiaryData['created_at'] = $addedAt;
            $beneficiaryData['version'] = 1;
            $this->db->table('beneficiaries_v2')->insert($beneficiaryData);
            $beneficiaryId = (int) $this->db->insertID();
        }

        // Refresh dependents.
        $this->db->table('beneficiary_dependents_v2')
            ->where('beneficiary_id', $beneficiaryId)
            ->delete();

        foreach ($dependents as $dependent) {
            $dependent['beneficiary_id'] = $beneficiaryId;
            $dependent['created_at'] = $now;
            $dependent['updated_at'] = null;
            $dependent['created_by'] = null;
            $dependent['updated_by'] = null;

            $this->db->table('beneficiary_dependents_v2')->insert($dependent);
        }

        // Change log.
        $summary = sprintf(
            'CSV import %s row %d (%s)',
            $batchId,
            $rowNumber,
            $changeType
        );

        if ($changeType === 'update' && ! empty($diff)) {
            $summary .= sprintf(' changes: %d fields', count($diff));
        }

        $this->db->table('beneficiary_change_logs')->insert([
            'beneficiary_id'   => $beneficiaryId,
            'change_reference' => sprintf('CSV-%s-%05d', $batchId, $rowNumber),
            'change_type'      => $changeType,
            'summary'          => $summary,
            'diff_json'        => empty($diff) ? null : json_encode($diff, JSON_UNESCAPED_UNICODE),
            'changed_by'       => null,
            'changed_at'       => $now,
            'previous_version' => $existing['version'] ?? null,
            'new_version'      => $changeType === 'create' ? 1 : ($existing['version'] ?? 0) + 1,
            'review_status'    => 'pending',
        ]);

        $this->db->transComplete();

        return $changeType === 'create' ? 'created' : 'updated';
    }

    /**
     * @return array{beneficiary:array<string,mixed>,dependents:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    private function mapBeneficiaryRow(array $row, string $batchId, int $rowNumber): array
    {
        $values = $this->normaliseRowValues($row);

        $legacyReference = $values[77] ?? null;
        if ($legacyReference === null) {
            throw new RuntimeException('Missing unique reference number.');
        }
        $legacyReference = strtoupper($legacyReference);
        $referenceNumber = $this->generateReferenceNumber($batchId, $rowNumber, $legacyReference);

        $planOptionId = $this->lookupId($this->planOptions, $values[0], 'plan_options');
        if ($planOptionId === null) {
            throw new RuntimeException(sprintf('Unknown plan option "%s".', $values[0] ?? ''));
        }

        $categoryId = $this->lookupId($this->categories, $values[1], 'beneficiary_categories');
        if ($categoryId === null) {
            throw new RuntimeException(sprintf('Unknown beneficiary category "%s".', $values[1] ?? ''));
        }

        $stateId = $this->ensureStateId($values[16]);
        if ($stateId === null) {
            throw new RuntimeException('State value required for beneficiary.');
        }

        $bloodGroupId = $this->lookupId($this->bloodGroups, $values[30], 'blood_groups');

        $planData = $this->resolveOptionalLookup($this->raos, $values[8], $this->raoOtherId, 'raos');
        $raoOther = $values[9] ?? $planData['other'] ?? null;

        $retirementData = $this->resolveOptionalLookup($this->retirementOffices, $values[10], $this->retirementOtherId, 'retirement_offices');
        if ($values[12] !== null) {
            $retirementData['other'] = $values[12];
        }

        $designationData = $this->resolveOptionalLookup($this->designations, $values[11], $this->designationOtherId, 'designations');
        if ($values[13] !== null) {
            $designationData['other'] = $values[13];
        }

        $bankSourceData = $this->resolveOptionalLookup($this->banks, $values[21], $this->bankOtherId, 'banks');
        $bankServicingData = $this->resolveOptionalLookup($this->banks, $values[22], $this->bankOtherId, 'banks');

        $primaryMobile = $this->normaliseNumericString($values[27] ?? $values[76]);
        $alternateMobile = $this->normaliseNumericString($values[28]);
        $aadhaarNumber = $this->normaliseNumericString($values[24]);
        $samagraNumber = $this->normaliseNumericString($values[31]);
        $bankAccount = $this->normaliseNumericString($values[23]);

        $gpfNumber = $this->normaliseNumericString($values[20]);
        $pranNumber = $this->normaliseNumericString($values[19]);

        $ppoValue = $values[18];
        $addedAt = $this->parseDateTime($values[78]) ?? Time::now('UTC')->toDateTimeString();

        $beneficiary = [
            'legacy_beneficiary_id'   => null,
            'reference_number'        => $referenceNumber,
            'legacy_reference'        => $legacyReference,
            'plan_option_id'          => $planOptionId,
            'category_id'             => $categoryId,
            'first_name'              => $values[2],
            'middle_name'             => $values[3],
            'last_name'               => $values[4],
            'gender'                  => $this->mapGender($values[5]),
            'date_of_birth'           => $this->parseDate($values[26]),
            'retirement_or_death_date'=> $this->parseDate($values[6]),
            'deceased_employee_name'  => $values[7],
            'rao_id'                  => $planData['id'],
            'rao_other'               => $raoOther,
            'retirement_office_id'    => $retirementData['id'],
            'retirement_office_other' => $retirementData['other'],
            'designation_id'          => $designationData['id'],
            'designation_other'       => $designationData['other'],
            'correspondence_address'  => $values[14],
            'city'                    => $values[15],
            'state_id'                => $stateId,
            'postal_code'             => $values[17],
            'ppo_number_enc'          => $this->toBinary($ppoValue),
            'ppo_number_masked'       => $this->maskAlphaNumeric($ppoValue),
            'pran_number_enc'         => $this->toBinary($pranNumber),
            'pran_number_masked'      => $this->maskAlphaNumeric($pranNumber),
            'gpf_number_enc'          => $this->toBinary($gpfNumber),
            'gpf_number_masked'       => $this->maskAlphaNumeric($gpfNumber),
            'bank_source_id'          => $bankSourceData['id'],
            'bank_source_other'       => $bankSourceData['other'],
            'bank_servicing_id'       => $bankServicingData['id'],
            'bank_servicing_other'    => $bankServicingData['other'],
            'bank_account_enc'        => $this->toBinary($bankAccount),
            'bank_account_masked'     => $this->maskDigits($bankAccount),
            'aadhaar_enc'             => $this->toBinary($aadhaarNumber),
            'aadhaar_masked'          => $this->maskDigits($aadhaarNumber),
            'pan_enc'                 => $this->toBinary($values[25] ? strtoupper($values[25]) : null),
            'pan_masked'              => $this->maskAlphaNumeric($values[25] ? strtoupper($values[25]) : null),
            'primary_mobile_enc'      => $this->toBinary($primaryMobile),
            'primary_mobile_masked'   => $this->maskDigits($primaryMobile),
            'primary_mobile_hash'     => $this->hashCanonicalMobile($primaryMobile),
            'alternate_mobile_enc'    => $this->toBinary($alternateMobile),
            'alternate_mobile_masked' => $this->maskDigits($alternateMobile),
            'email'                   => $values[29],
            'blood_group_id'          => $bloodGroupId,
            'samagra_enc'             => $this->toBinary($samagraNumber),
            'samagra_masked'          => $this->maskDigits($samagraNumber),
            'terms_accepted_at'       => $this->termsAccepted($values[75]) ? $addedAt : null,
            'otp_verified_at'         => null,
            'otp_reference'           => null,
            'submission_source'       => 'csv-import',
            'pending_review'          => 0,
            'created_by'              => null,
            'updated_by'              => null,
            'created_at'              => $addedAt,
            'updated_at'              => null,
        ];

        $dependents = $this->buildDependents($values);

        return [
            'beneficiary' => $beneficiary,
            'dependents'  => $dependents,
            'meta'        => [
                'batch_id' => $batchId,
                'row'      => $rowNumber,
                'added_at' => $addedAt,
            ],
        ];
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function normaliseRowValues(array $row): array
    {
        $values = array_pad($row, self::COLUMN_COUNT, null);
        foreach ($values as $index => $value) {
            $values[$index] = $this->normalizeString($value);
        }
        return $values;
    }

    private function normalizeString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }
        if (strcasecmp($string, 'NA') === 0 || strcasecmp($string, 'N/A') === 0) {
            return null;
        }
        return $string;
    }

    private function normaliseNumericString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper($value);
        if (strpos($upper, 'E+') !== false || strpos($upper, 'E-') !== false) {
            if (preg_match('/^([0-9]+)(?:\.([0-9]+))?E([+-]?[0-9]+)$/i', $upper, $matches)) {
                $integer = $matches[1];
                $fraction = $matches[2] ?? '';
                $exponent = (int) $matches[3];

                if ($exponent >= strlen($fraction)) {
                    $combined = $integer . $fraction . str_repeat('0', $exponent - strlen($fraction));
                } else {
                    $combined = $integer . substr($fraction, 0, $exponent);
                }

                $combined = ltrim($combined, '0');
                return $combined === '' ? '0' : $combined;
            }
        }

        $digits = preg_replace('/\D+/', '', $value);
        return $digits === '' ? null : $digits;
    }

    private function canonicalMobileKey(?string $value): ?string
    {
        $digits = $this->normaliseNumericString($value);

        if ($digits === null) {
            return null;
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    private function hashCanonicalMobile(?string $value): ?string
    {
        $canonical = $this->canonicalMobileKey($value);

        if ($canonical === null || $canonical === '') {
            return null;
        }

        return hash('sha256', $canonical);
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace('.', '/', $value);

        $formats = [
            'Y-m-d',
            'Y/m/d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
        ];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value, new \DateTimeZone('UTC'));
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function parseDateTime(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace('.', '/', $value);
        $formats = [
            'Y-m-d H:i:s',
            'd/m/Y H:i',
            'm/d/Y H:i',
            'd-m-Y H:i',
            'm-d-Y H:i',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value, new \DateTimeZone('UTC'));
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function termsAccepted(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return stripos($value, 'agreed') !== false || stripos($value, 'yes') !== false;
    }

    private function buildDependents(array $values): array
    {
        $dependents = [];

        $dependents[] = $this->buildDependent([
            'relation'   => 'spouse',
            'status'     => $values[33],
            'health'     => $values[34],
            'name'       => $values[35],
            'gender'     => $values[36],
            'blood'      => $values[37],
            'dob'        => $values[38],
            'aadhaar'    => $values[39],
            'order'      => null,
        ]);

        $dependents[] = $this->buildDependent([
            'relation'   => 'child',
            'status'     => $values[40],
            'health'     => $values[41],
            'name'       => $values[42],
            'gender'     => $values[43],
            'blood'      => $values[44],
            'dob'        => $values[45],
            'aadhaar'    => $values[46],
            'order'      => 1,
        ]);

        $dependents[] = $this->buildDependent([
            'relation'   => 'child',
            'status'     => $values[47],
            'health'     => $values[48],
            'name'       => $values[49],
            'gender'     => $values[50],
            'blood'      => $values[51],
            'dob'        => $values[52],
            'aadhaar'    => $values[53],
            'order'      => 2,
        ]);

        $dependents[] = $this->buildDependent([
            'relation'   => 'child',
            'status'     => $values[54],
            'health'     => $values[55],
            'name'       => $values[56],
            'gender'     => $values[57],
            'blood'      => $values[58],
            'dob'        => $values[59],
            'aadhaar'    => $values[60],
            'order'      => 3,
        ]);

        $dependents[] = $this->buildDependent([
            'relation'   => 'father',
            'status'     => $values[62],
            'health'     => $values[63],
            'name'       => $values[61],
            'gender'     => $values[64],
            'blood'      => $values[65],
            'dob'        => $values[66],
            'aadhaar'    => $values[67],
            'order'      => null,
        ]);

        $dependents[] = $this->buildDependent([
            'relation'   => 'mother',
            'status'     => $values[68],
            'health'     => $values[69],
            'name'       => $values[70],
            'gender'     => $values[71],
            'blood'      => $values[72],
            'dob'        => $values[73],
            'aadhaar'    => $values[74],
            'order'      => null,
        ]);

        return array_values(array_filter($dependents));
    }

    private function buildDependent(array $input): ?array
    {
        $aadhaar = $this->normaliseNumericString($input['aadhaar']);
        $hasIdentifyingData = ($input['name'] !== null && $input['name'] !== '')
            || $aadhaar !== null;

        if (! $hasIdentifyingData) {
            return null;
        }

        $status = $this->mapStatus($input['status']);

        // Skip if explicitly not applicable and no identifying data.
        if ($status === self::STATUS_NOT_APPLICABLE && $input['name'] === null && $input['aadhaar'] === null) {
            return null;
        }

        $gender = $this->mapGender($input['gender']);
        if ($gender === null) {
            $gender = match ($input['relation']) {
                'father' => 'male',
                'mother' => 'female',
                default => null,
            };
        }

        if ($gender === null) {
            // Without gender we cannot persist due to schema constraints.
            return null;
        }

        return [
            'relationship'         => $input['relation'],
            'dependant_order'      => $input['order'],
            'twin_group'           => null,
            'is_alive'             => $status,
            'is_health_dependant'  => $this->mapHealth($input['health']),
            'first_name'           => $input['name'],
            'gender'               => $gender,
            'blood_group_id'       => $this->lookupId($this->bloodGroups, $input['blood'], 'blood_groups'),
            'date_of_birth'        => $this->parseDate($input['dob']),
            'aadhaar_enc'          => $this->toBinary($aadhaar),
            'aadhaar_masked'       => $this->maskDigits($aadhaar),
        ];
    }

    private function mapGender(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = $this->normalizeKey($value);
        return match ($normalized) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            'transgender', 't' => 'transgender',
            default => null,
        };
    }

    private function mapStatus(?string $value): string
    {
        if ($value === null) {
            return self::STATUS_NOT_APPLICABLE;
        }
        $normalized = $this->normalizeKey($value);
        return match ($normalized) {
            'alive' => self::STATUS_ALIVE,
            'not alive', 'deceased', 'dead' => self::STATUS_NOT_ALIVE,
            default => self::STATUS_NOT_APPLICABLE,
        };
    }

    private function mapHealth(?string $value): string
    {
        if ($value === null) {
            return self::HEALTH_NOT_APPLICABLE;
        }
        $normalized = $this->normalizeKey($value);
        return match ($normalized) {
            'yes', 'y' => self::HEALTH_YES,
            'no', 'n' => self::HEALTH_NO,
            default => self::HEALTH_NOT_APPLICABLE,
        };
    }

    private function primeLookups(): void
    {
        $this->planOptions = $this->fetchLookupMap('plan_options', ['code', 'label']);
        $this->categories = $this->fetchLookupMap('beneficiary_categories', ['code', 'label']);
        $this->bloodGroups = $this->fetchLookupMap('blood_groups_ref', ['label']);
        $this->banks = $this->fetchLookupMap('banks_ref', ['code', 'name']);
        $this->designations = $this->fetchLookupMap('designations_ref', ['code', 'title']);
        $this->raos = $this->fetchLookupMap('regional_account_offices', ['code', 'name']);
        $this->retirementOffices = $this->fetchLookupMap('retirement_offices', ['code', 'name']);
        $this->states = $this->fetchLookupMap('states', ['state_name'], 'state_id');

        $this->raoOtherId = $this->lookupId($this->raos, 'RAO-OTHER', 'raos') ?? $this->lookupId($this->raos, 'Other RAO', 'raos');
        $this->retirementOtherId = $this->lookupId($this->retirementOffices, 'RO-OTHER', 'retirement_offices') ?? $this->lookupId($this->retirementOffices, 'Other Retirement Office', 'retirement_offices');
        $this->designationOtherId = $this->lookupId($this->designations, 'DESG-OTHER', 'designations') ?? $this->lookupId($this->designations, 'Other Designation', 'designations');
        $this->bankOtherId = $this->lookupId($this->banks, 'BANK-OTHER', 'banks') ?? $this->lookupId($this->banks, 'Other Bank', 'banks');
    }

    private function loadSynonyms(): void
    {
        $config = config(EnrollmentV2Masters::class)->data ?? [];
        $this->synonyms = [
            'plan_options' => $this->buildSynonymMap($config['planOptionsSynonyms'] ?? [], $config['planOptions'] ?? []),
            'beneficiary_categories' => $this->buildSynonymMap($config['beneficiaryCategoriesSynonyms'] ?? [], $config['beneficiaryCategories'] ?? []),
            'genders' => $this->buildSynonymMap($config['gendersSynonyms'] ?? [], $config['genders'] ?? []),
            'raos' => $this->buildSynonymMap($config['raosSynonyms'] ?? [], $config['raos'] ?? []),
            'retirement_offices' => $this->buildSynonymMap($config['retirementOfficesSynonyms'] ?? [], $config['retirementOffices'] ?? []),
            'designations' => $this->buildSynonymMap($config['designationsSynonyms'] ?? [], $config['designations'] ?? []),
            'banks' => $this->buildSynonymMap($config['banksSynonyms'] ?? [], $config['banks'] ?? []),
            'blood_groups' => $this->buildSynonymMap($config['bloodGroupsSynonyms'] ?? [], $config['bloodGroups'] ?? []),
            'states' => $this->buildSynonymMap($config['statesSynonyms'] ?? [], $config['states'] ?? []),
        ];
    }

    private function buildSynonymMap(array $synonyms, array $entries): array
    {
        $map = [];
        $targets = [];
        foreach ($entries as $entry) {
            $codeKey = isset($entry['code']) ? $this->normalizeKey($entry['code']) : '';
            $labelKey = isset($entry['label']) ? $this->normalizeKey($entry['label']) : '';

            if ($codeKey !== '') {
                $targets[$codeKey] = $labelKey !== '' ? $labelKey : $codeKey;
            }

            if ($labelKey !== '') {
                $targets[$labelKey] = $labelKey;
            }
        }

        foreach ($synonyms as $raw => $target) {
            $targetKey = $this->normalizeKey($target);
            if (isset($targets[$targetKey])) {
                $map[$this->normalizeKey($raw)] = $targets[$targetKey];
            } else {
                $map[$this->normalizeKey($raw)] = $targetKey;
            }
        }

        return $map;
    }

    /**
     * @param array<string,array<string,mixed>> $lookup
     */
    private function fetchLookupMap(string $table, array $columns, string $primaryKey = 'id'): array
    {
        $select = $columns;
        $select[] = $primaryKey === 'id' ? 'id' : "{$primaryKey} AS id";
        $select = array_unique($select);
        $rows = $this->db->table($table)->select($select)->get()->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            foreach ($columns as $column) {
                if (! isset($row[$column]) || $row[$column] === null) {
                    continue;
                }
                $key = $this->normalizeKey($row[$column]);
                if ($key === '') {
                    continue;
                }
                $map[$key] = $row;
            }
        }

        return $map;
    }

    private function lookupId(array $lookup, ?string $value, ?string $type = null): ?int
    {
        if ($value === null) {
            return null;
        }
        $key = $this->normalizeKey($value);
        if ($key === '') {
            return null;
        }
        if ($type !== null && isset($this->synonyms[$type][$key])) {
            $target = $this->synonyms[$type][$key];
            if (isset($lookup[$target])) {
                return (int) $lookup[$target]['id'];
            }
        }
        if (isset($lookup[$key])) {
            return (int) $lookup[$key]['id'];
        }

        return null;
    }

    private function resolveOptionalLookup(array $lookup, ?string $value, ?int $fallbackId, ?string $type = null): array
    {
        $id = $this->lookupId($lookup, $value, $type);
        if ($id !== null) {
            return ['id' => $id, 'other' => null];
        }

        if ($value === null) {
            return ['id' => $fallbackId, 'other' => null];
        }

        return ['id' => $fallbackId, 'other' => $value];
    }

    private function ensureStateId(?string $stateName): ?int
    {
        if ($stateName === null) {
            return null;
        }

        $rawKey = $this->normalizeKey($stateName);
        if ($rawKey === '') {
            return null;
        }

        $key = $rawKey;
        if (isset($this->synonyms['states'][$key])) {
            $key = $this->synonyms['states'][$key];
        }

        if (isset($this->states[$key])) {
            return (int) $this->states[$key]['id'];
        }

        $existing = $this->db->table('states')
            ->where('state_name', strtoupper($stateName))
            ->get()
            ->getRowArray();

        if ($existing) {
            $normalizedExisting = $this->normalizeKey($existing['state_name']);
            $this->states[$normalizedExisting] = $existing;
            $this->synonyms['states'][$rawKey] = $normalizedExisting;
            return (int) $existing['id'];
        }

        $this->db->table('states')->insert([
            'state_name' => strtoupper($stateName),
            'allow_unrestricted_cities' => 0,
        ]);

        $id = (int) $this->db->insertID();
        $normalizedNew = $this->normalizeKey($stateName);
        $this->states[$normalizedNew] = ['id' => $id, 'state_name' => strtoupper($stateName)];
        $this->synonyms['states'][$rawKey] = $normalizedNew;

        return $id;
    }

    private function calculateDiff(array $existing, array $updates): array
    {
        $diff = [];

        foreach ($updates as $field => $newValue) {
            $oldValue = $existing[$field] ?? null;
            if ($oldValue == $newValue) {
                continue;
            }
            $diff[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $diff;
    }

    private function maskDigits(?string $value, int $visible = 4): ?string
    {
        if ($value === null) {
            return null;
        }
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }
        return str_repeat('X', $length - $visible) . substr($value, -$visible);
    }

    private function maskAlphaNumeric(?string $value, int $visible = 4): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }
        return str_repeat('X', $length - $visible) . substr($value, -$visible);
    }

    private function toBinary(?string $value): ?string
    {
        $trimmed = $value;
        if (is_string($trimmed)) {
            $trimmed = trim($trimmed);
            if ($trimmed === '') {
                $trimmed = null;
            }
        }

        return $this->crypto->encrypt($trimmed);
    }

    private function normalizeKey(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $normalized = trim(mb_strtolower($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized ?? '';
    }

    private function generateReferenceNumber(string $batchId, int $rowNumber, string $legacyReference): string
    {
        $batchSegment = preg_replace('/\D+/', '', $batchId);
        if ($batchSegment === '') {
            $batchSegment = date('Ymd');
        }
        $batchSegment = substr($batchSegment, 0, 8);

        $sequence = str_pad((string) $rowNumber, 3, '0', STR_PAD_LEFT);
        $legacySegment = preg_replace('/[^A-Z0-9]/', '', strtoupper($legacyReference));
        $legacySegment = substr($legacySegment, 0, 10);

        return sprintf('BEN%s%s-%s', $batchSegment, $sequence, $legacySegment);
    }

    private function resolveFilePath(?string $filename): string
    {
        if ($filename !== null) {
            if (is_file($filename)) {
                return $filename;
            }
            $candidate = rtrim($this->importDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($candidate)) {
                return $candidate;
            }
            throw new RuntimeException("Unable to locate CSV file: {$filename}");
        }

        return rtrim($this->importDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $this->defaultFilename;
    }
}


