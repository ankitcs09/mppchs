<?php

namespace App\Commands;

use App\Services\EnrollmentV2Importer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class EnrollmentV2Import extends BaseCommand
{
    protected $group = 'Enrollment';
    protected $name = 'enrollment:import';
    protected $description = 'Import Zoho CSV data into the enrollment v2 tables.';
    protected $usage = 'enrollment:import [filename] [--batch=ID] [--dry-run]';
    protected $arguments = [
        'filename' => 'Optional CSV filename (relative to writable/imports/enrollment_v2/ or absolute path).',
    ];
    protected $options = [
        '--batch'   => 'Optional batch identifier (defaults to timestamp).',
        '--dry-run' => 'Validate the CSV without writing to the database.',
    ];

    public function run(array $params)
    {
        $filename = $params[0] ?? null;
        $batchId = CLI::getOption('batch');
        $dryRun = CLI::getOption('dry-run') !== null;

        $importer = new EnrollmentV2Importer();

        try {
            $summary = $importer->import($filename, $batchId, $dryRun);
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());
            return;
        }

        CLI::write(sprintf('Batch ID     : %s', $summary['batch_id']));
        CLI::write(sprintf('Source File  : %s', $summary['file']));
        CLI::write(sprintf('Dry Run      : %s', $summary['dry_run'] ? 'Yes' : 'No'));
        CLI::write(sprintf('Processed    : %d', $summary['processed']));
        CLI::write(sprintf('Created      : %d', $summary['created']));
        CLI::write(sprintf('Updated      : %d', $summary['updated']));
        CLI::write(sprintf('Skipped      : %d', $summary['skipped']));

        if (! empty($summary['errors'])) {
            CLI::newLine();
            CLI::error('Errors:');
            foreach ($summary['errors'] as $error) {
                CLI::error("  - {$error}");
            }
        }
    }
}
