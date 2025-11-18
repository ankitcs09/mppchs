<?php

namespace App\Commands;

use App\Config\SensitiveData;
use App\Services\SensitiveDataService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use Throwable;

class EncryptExistingSensitive extends BaseCommand
{
    protected $group = 'Enrollment';
    protected $name = 'enrollment:encrypt-existing';
    protected $description = 'Encrypts plaintext sensitive columns in beneficiaries_v2 and beneficiary_dependents_v2.';

    private SensitiveDataService $crypto;
    private SensitiveData $config;

    public function run(array $params)
    {
        $this->crypto = new SensitiveDataService();
        $this->config = new SensitiveData();

        $db = db_connect();
        $db->transException(true);

        try {
            $db->transStart();
            $beneficiaryUpdates = $this->encryptBeneficiaries($db);
            $dependentUpdates = $this->encryptDependents($db);
            $db->transComplete();
        } catch (Throwable $exception) {
            $db->transRollback();
            CLI::error('Encryption backfill failed: ' . $exception->getMessage());
            return;
        }

        CLI::write(sprintf('Beneficiaries updated: %d', $beneficiaryUpdates));
        CLI::write(sprintf('Dependents updated   : %d', $dependentUpdates));
    }

    private function encryptBeneficiaries($db): int
    {
        $columns = $this->config->beneficiaryColumns;
        if (empty($columns)) {
            return 0;
        }

        $select = array_column($columns, 'column');
        $select = array_merge(['id'], $select);
        $select = array_merge($select, array_filter(array_column($columns, 'mask')));

        $rows = $db->table('beneficiaries_v2')
            ->select(array_unique($select))
            ->get()
            ->getResultArray();

        $updated = 0;

        foreach ($rows as $row) {
            $changes = [];
            foreach ($columns as $info) {
                $encColumn = $info['column'];
                $maskColumn = $info['mask'] ?? null;
                $maskType = $info['masker'] ?? null;
                $current = $row[$encColumn] ?? null;

                if ($current === null || $current === '') {
                    continue;
                }

                if ($this->isAlreadyEncrypted($current)) {
                    continue;
                }

                $clean = trim((string) $current);
                $changes[$encColumn] = $this->crypto->encrypt($clean);

                if ($maskColumn !== null) {
                    $changes[$maskColumn] = $this->maskByType($clean, $maskType);
                }
            }

            if (! empty($changes)) {
                $db->table('beneficiaries_v2')
                    ->where('id', $row['id'])
                    ->update($changes);
                $updated++;
            }
        }

        return $updated;
    }

    private function encryptDependents($db): int
    {
        $columns = $this->config->dependentColumns;
        if (empty($columns)) {
            return 0;
        }

        $select = array_column($columns, 'column');
        $select = array_merge(['id'], $select);
        $select = array_merge($select, array_filter(array_column($columns, 'mask')));

        $rows = $db->table('beneficiary_dependents_v2')
            ->select(array_unique($select))
            ->get()
            ->getResultArray();

        $updated = 0;

        foreach ($rows as $row) {
            $changes = [];
            foreach ($columns as $info) {
                $encColumn = $info['column'];
                $maskColumn = $info['mask'] ?? null;
                $maskType = $info['masker'] ?? null;
                $current = $row[$encColumn] ?? null;

                if ($current === null || $current === '') {
                    continue;
                }

                if ($this->isAlreadyEncrypted($current)) {
                    continue;
                }

                $clean = trim((string) $current);
                $changes[$encColumn] = $this->crypto->encrypt($clean);

                if ($maskColumn !== null) {
                    $changes[$maskColumn] = $this->maskByType($clean, $maskType);
                }
            }

            if (! empty($changes)) {
                $db->table('beneficiary_dependents_v2')
                    ->where('id', $row['id'])
                    ->update($changes);
                $updated++;
            }
        }

        return $updated;
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        try {
            $this->crypto->decrypt($value);
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function maskByType(string $value, ?string $type): string
    {
        return match ($type) {
            'digits' => $this->maskDigits($value),
            default => $this->maskAlpha($value),
        };
    }

    private function maskDigits(string $value, int $visible = 4): string
    {
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }
        return str_repeat('X', $length - $visible) . substr($value, -$visible);
    }

    private function maskAlpha(string $value, int $visible = 4): string
    {
        $length = strlen($value);
        if ($length <= $visible) {
            return $value;
        }
        return str_repeat('X', $length - $visible) . substr($value, -$visible);
    }
}

