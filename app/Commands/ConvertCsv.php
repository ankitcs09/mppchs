<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ConvertCsv extends BaseCommand
{
    protected $group       = 'CSV Tools';
    protected $name        = 'convert:csv';
    protected $description = 'Convert a Zoho UTF-16 CSV file to UTF-8 for easier processing.';

    public function run(array $params)
    {
        $source = $params[0] ?? null;
        $target = $params[1] ?? null;

        if (! $source || ! is_file($source)) {
            CLI::error('Please provide the source CSV path. Example: php spark convert:csv writable/uploads/zoho.csv');
            return;
        }

        if (! $target) {
            $target = $source . '.utf8.csv';
        }

        $raw = @file_get_contents($source);
        if ($raw === false) {
            CLI::error('Unable to read source file: ' . $source);
            return;
        }

        $encoding = mb_detect_encoding($raw, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'], true);
        if (! $encoding) {
            // assume UTF-16LE if detection fails (common for Zoho exports)
            $encoding = 'UTF-16LE';
        }

        if (strtoupper($encoding) === 'UTF-8') {
            $converted = $raw;
        } else {
            $converted = mb_convert_encoding($raw, 'UTF-8', $encoding);
        }

        if (@file_put_contents($target, $converted) === false) {
            CLI::error('Unable to write target file: ' . $target);
            return;
        }

        CLI::write('Conversion complete.', 'green');
        CLI::write('Source: ' . realpath($source));
        CLI::write('Output: ' . realpath($target));
    }
}
