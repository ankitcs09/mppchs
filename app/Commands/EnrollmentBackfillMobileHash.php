<?php

namespace App\Commands;

use App\Services\SensitiveDataService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

class EnrollmentBackfillMobileHash extends BaseCommand
{
    protected $group       = 'Enrollment';
    protected $name        = 'enrollment:backfill-mobile-hash';
    protected $description = 'Populate beneficiaries_v2.primary_mobile_hash from encrypted mobile numbers.';
    protected $usage       = 'enrollment:backfill-mobile-hash [--dry-run] [--batch-size=500] [--start-id=0]';
    protected $options     = [
        '--dry-run'    => 'Preview the updates without writing changes to the database.',
        '--batch-size' => 'Number of rows to process per batch (default 500).',
        '--start-id'   => 'Resume processing from a specific beneficiary ID (default 0).',
    ];

    private SensitiveDataService $crypto;

    public function run(array $params)
    {
        $this->crypto = new SensitiveDataService();
        $db           = Database::connect();

        $dryRun    = CLI::getOption('dry-run') !== null;
        $batchSize = (int) (CLI::getOption('batch-size') ?? 500);
        $startId   = (int) (CLI::getOption('start-id') ?? 0);

        if ($batchSize <= 0) {
            $batchSize = 500;
        }

        $processed = 0;
        $updated   = 0;
        $skipped   = 0;
        $failed    = 0;

        $lastId = $startId;

        CLI::write(
            sprintf(
                'Starting backfill (dry-run: %s, batch-size: %d, start-id: %d)',
                $dryRun ? 'yes' : 'no',
                $batchSize,
                $startId
            ),
            'yellow'
        );

        while (true) {
            $rows = $db->table('beneficiaries_v2')
                ->select('id, primary_mobile_enc, primary_mobile_hash')
                ->where('id >', $lastId)
                ->orderBy('id', 'ASC')
                ->limit($batchSize)
                ->get()
                ->getResultArray();

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $processed++;
                $lastId = (int) $row['id'];

                try {
                    $plain = $this->crypto->decrypt($row['primary_mobile_enc']);
                } catch (Throwable $exception) {
                    $failed++;
                    CLI::error(
                        sprintf(
                            'Failed to decrypt beneficiary #%d: %s',
                            $row['id'],
                            $exception->getMessage()
                        )
                    );
                    continue;
                }

                $canonical = $this->canonicalMobile($plain);
                $hash      = $canonical === null ? null : hash('sha256', $canonical);

                if ($hash === ($row['primary_mobile_hash'] ?? null)) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $updated++;
                    CLI::write(
                        sprintf(
                            '[DRY-RUN] Would update beneficiary #%d hash to %s',
                            $row['id'],
                            $hash ?? 'NULL'
                        )
                    );
                    continue;
                }

                $db->table('beneficiaries_v2')
                    ->where('id', $row['id'])
                    ->update(['primary_mobile_hash' => $hash]);

                $updated++;
            }

            if (count($rows) < $batchSize) {
                break;
            }
        }

        CLI::write('');
        CLI::write('Backfill summary', 'yellow');
        CLI::write('  Processed: ' . $processed);
        CLI::write('  Updated:   ' . $updated);
        CLI::write('  Skipped:   ' . $skipped);
        CLI::write('  Failed:    ' . $failed);
        CLI::write('');
        CLI::write('Completed at ID ' . $lastId, 'yellow');
    }

    private function canonicalMobile(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }
}

