<?php

namespace App\Commands;

use App\Services\SensitiveDataService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

class ProvisionBeneficiaryUsers extends BaseCommand
{
    protected $group       = 'Enrollment';
    protected $name        = 'enrollment:provision-users';
    protected $description = 'Create app_users accounts for beneficiaries_v2 records.';
    protected $usage       = 'enrollment:provision-users [--dry-run] [--output=filename]';
    protected $options     = [
        '--dry-run' => 'Preview the actions without writing to the database or creating credentials.',
        '--output'  => 'Optional relative path (from writable/) for the credential CSV export.',
    ];

    private SensitiveDataService $crypto;

    public function run(array $params)
    {
        $this->crypto = new SensitiveDataService();

        $dryRun    = CLI::getOption('dry-run') !== null;
        $outputOpt = CLI::getOption('output');

        $db = Database::connect();

        $builder = $db->table('beneficiaries_v2 b')
            ->select('b.id, b.first_name, b.middle_name, b.last_name, b.reference_number, b.legacy_reference, b.primary_mobile_enc, b.primary_mobile_masked')
            ->join('app_users u', 'u.beneficiary_v2_id = b.id', 'left')
            ->where('u.id', null)
            ->orderBy('b.id', 'ASC');

        $records = $builder->get()->getResultArray();

        if (empty($records)) {
            CLI::write('All beneficiaries already have v2 user accounts. Nothing to do.', 'green');
            return;
        }

        $existingUsernames = $this->loadExistingUsernames($db);
        $provisioned       = [];
        $now               = utc_now();

        CLI::write(sprintf('Found %d beneficiary records without v2 user accounts.', count($records)), 'yellow');
        if ($dryRun) {
            CLI::write('Dry-run mode enabled. No database changes will be made.', 'yellow');
        }

        foreach ($records as $row) {
            $usernameBase = $row['legacy_reference'] ?: $row['reference_number'];
            $username     = $this->generateUniqueUsername($usernameBase, $existingUsernames);
            $existingUsernames[strtolower($username)] = true;

            $temporaryPassword = $this->generateTemporaryPassword();
            $hashedPassword    = password_hash($temporaryPassword, PASSWORD_DEFAULT);

            $nameParts = array_filter([
                $row['first_name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
            ]);
            $fullName = trim(implode(' ', $nameParts));
            if ($fullName === '') {
                $fullName = 'Beneficiary #' . $row['id'];
            }

            $mobilePlain = $this->decryptMobile($row['primary_mobile_enc'] ?? null);

            $data = [
                'beneficiary_v2_id'    => $row['id'],
                'username'             => $username,
                'display_name'         => $fullName,
                'bname'                => $fullName,
                'mobile'               => $mobilePlain,
                'password'             => $hashedPassword,
                'user_type'            => 'beneficiary',
                'company_id'           => null,
                'status'               => 'active',
                'force_password_reset' => 1,
                'password_changed_at'  => null,
                'last_login_at'        => null,
                'session_version'      => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];

            if (! $dryRun) {
                $db->table('app_users')->insert($data);
            }

            $provisioned[] = [
                'username'         => $username,
                'password'         => $temporaryPassword,
                'reference_number' => $row['reference_number'],
                'legacy_reference' => $row['legacy_reference'] ?? '',
                'mobile_masked'    => $row['primary_mobile_masked'] ?? '',
                'mobile_plain'     => $mobilePlain,
            ];
        }

        CLI::write(sprintf('%d account(s) %s.', count($provisioned), $dryRun ? 'would be created' : 'created'), 'green');

        if ($dryRun) {
            return;
        }

        $outputPath = $this->resolveOutputPath($outputOpt);
        $this->writeCsv($outputPath, $provisioned);
        CLI::write('Credentials exported to: ' . $outputPath, 'light_gray');
    }

    /**
     * @return array<string,bool>
     */
    private function loadExistingUsernames($db): array
    {
        $rows = $db->table('app_users')->select('username')->get()->getResultArray();
        $indexed = [];
        foreach ($rows as $row) {
            $username = strtolower(trim((string) ($row['username'] ?? '')));
            if ($username !== '') {
                $indexed[$username] = true;
            }
        }

        return $indexed;
    }

    private function generateUniqueUsername(string $base, array $existing): string
    {
        $candidate = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $base));
        if ($candidate === '') {
            $candidate = 'beneficiary';
        }

        $username = $candidate;
        $suffix   = 1;
        while (isset($existing[strtolower($username)])) {
            $username = $candidate . $suffix;
            $suffix++;
        }

        return $username;
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#%&';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $index    = random_int(0, $maxIndex);
            $password .= $alphabet[$index];
        }

        return $password;
    }

    private function decryptMobile(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }

        try {
            return $this->crypto->decrypt($encoded);
        } catch (Throwable $exception) {
            log_message('error', 'Failed to decrypt mobile number: {message}', ['message' => $exception->getMessage()]);
            return null;
        }
    }

    private function resolveOutputPath(?string $option): string
    {
        $base = WRITEPATH . 'provisioning';
        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }

        if ($option) {
            $option = ltrim($option, '/\\');
            $path   = WRITEPATH . $option;
            $dir    = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return $path;
        }

        return $base . DIRECTORY_SEPARATOR . 'provisioned-users-' . date('Ymd-His') . '.csv';
    }

    private function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create export file: ' . $path);
        }

        fputcsv($handle, ['username', 'temporary_password', 'reference_number', 'legacy_reference', 'mobile_masked', 'mobile_plain']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['username'],
                $row['password'],
                $row['reference_number'],
                $row['legacy_reference'],
                $row['mobile_masked'],
                $row['mobile_plain'],
            ]);
        }

        fclose($handle);
    }
}
