<?php

namespace App\Commands;

use App\Services\SensitiveDataService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Config\Database;

class EnrollmentUpdateMobile extends BaseCommand
{
    protected $group       = 'Enrollment';
    protected $name        = 'enrollment:update-mobile';
    protected $description = 'Update a beneficiary\'s primary mobile number (beneficiaries_v2).';
    protected $usage       = 'enrollment:update-mobile --id=<beneficiaryId> --mobile=<mobile> [--dry-run]';
    protected $options     = [
        '--id'       => 'Beneficiary_v2 ID to update.',
        '--mobile'   => 'New mobile number (digits, spaces, +, etc. are accepted).',
        '--dry-run'  => 'Preview the change without writing to the database.',
    ];

    private SensitiveDataService $crypto;

    public function run(array $params)
    {
        $idOption  = CLI::getOption('id');
        $mobileRaw = CLI::getOption('mobile');
        $dryRun    = CLI::getOption('dry-run') !== null;

        $idOption  = $idOption ?? $this->findOptionValue($params, 'id');
        $mobileRaw = $mobileRaw ?? $this->findOptionValue($params, 'mobile');
        $dryRun    = $dryRun || $this->optionExists($params, 'dry-run');

        if ($idOption === null || ! is_numeric($idOption)) {
            CLI::error('You must supply a numeric --id value for the beneficiary.');
            return;
        }

        $beneficiaryId = (int) $idOption;

        if ($mobileRaw === null || trim($mobileRaw) === '') {
            CLI::error('You must supply the new --mobile value.');
            return;
        }

        $canonical = $this->canonicalMobile($mobileRaw);
        if ($canonical === null) {
            CLI::error('The supplied mobile number does not contain enough digits to be valid.');
            return;
        }

        $this->crypto ??= new SensitiveDataService();

        $db  = Database::connect();
        $row = $db->table('beneficiaries_v2')
            ->where('id', $beneficiaryId)
            ->get(1)
            ->getRowArray();

        if (! $row) {
            CLI::error(sprintf('No beneficiaries_v2 record found with ID %d.', $beneficiaryId));
            return;
        }

        $oldMasked = $row['primary_mobile_masked'] ?? '(none)';

        $newMasked = $this->maskDigits($canonical);
        $newHash   = hash('sha256', $this->canonicalStoreKey($canonical));
        $newEnc    = $this->crypto->encrypt($canonical);

        CLI::write('');
        CLI::write('Beneficiary #' . $beneficiaryId, 'yellow');
        CLI::write('  Name      : ' . trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        CLI::write('  Old mobile: ' . $oldMasked);
        CLI::write('  New mobile: ' . $newMasked);
        CLI::write('  New hash  : ' . $newHash);
        CLI::write('');

        if ($dryRun) {
            CLI::write('Dry-run complete. No changes saved.', 'yellow');
            return;
        }

        $update = [
            'primary_mobile_enc'    => $newEnc,
            'primary_mobile_masked' => $newMasked,
            'primary_mobile_hash'   => $newHash,
            'updated_at'            => Time::now('UTC')->toDateTimeString(),
        ];

        if (isset($row['version'])) {
            $update['version'] = (int) $row['version'] + 1;
        }

        $db->table('beneficiaries_v2')
            ->where('id', $beneficiaryId)
            ->update($update);

        CLI::write('Mobile updated successfully.', 'green');
    }

    private function canonicalMobile(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) < 4) {
            return null;
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    private function canonicalStoreKey(string $digits): string
    {
        $clean = preg_replace('/\D+/', '', $digits);

        if ($clean === null) {
            return '';
        }

        return strlen($clean) > 10 ? substr($clean, -10) : $clean;
    }

    private function maskDigits(string $digits, int $visible = 4): string
    {
        $length = strlen($digits);

        if ($length <= $visible) {
            return $digits;
        }

        return str_repeat('X', $length - $visible) . substr($digits, -$visible);
    }

    private function findOptionValue(array $params, string $name): ?string
    {
        $value = $this->scanOptionArray($params, $name);
        if ($value !== null) {
            return $value;
        }

        $argv = $_SERVER['argv'] ?? [];
        return $this->scanOptionArray($argv, $name);
    }

    private function optionExists(array $params, string $name): bool
    {
        if (CLI::getOption($name) !== null) {
            return true;
        }

        if (in_array('--' . $name, $params, true)) {
            return true;
        }

        $argv = $_SERVER['argv'] ?? [];
        return in_array('--' . $name, $argv, true);
    }

    private function scanOptionArray(array $source, string $name): ?string
    {
        $prefix = '--' . $name . '=';

        foreach ($source as $index => $entry) {
            if (strpos($entry, $prefix) === 0) {
                return substr($entry, strlen($prefix));
            }

            if ($entry === '--' . $name) {
                $next = $source[$index + 1] ?? null;

                if ($next !== null && strncmp($next, '--', 2) !== 0) {
                    return $next;
                }
            }
        }

        return null;
    }
}
