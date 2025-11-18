<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class SpreadsheetExportService
{
    /**
     * Generates an XLSX binary string for a simple table report.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     * @param array<string, mixed> $options
     */
    public function exportTable(string $title, array $headers, array $rows, array $options = []): string
    {
        if (! class_exists(Spreadsheet::class)) {
            throw new RuntimeException('PhpSpreadsheet is not installed. Please run composer install.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheetTitle = trim($title) !== '' ? trim($title) : 'Report';
        $sheet->setTitle(mb_substr($sheetTitle, 0, 31));

        $headerRow = (int) ($options['headerRow'] ?? 1);
        foreach ($headers as $index => $label) {
            $sheet->setCellValueByColumnAndRow($index + 1, $headerRow, $label);
        }

        $headerStyle = $sheet->getStyleByColumnAndRow(1, $headerRow, count($headers), $headerRow);
        $headerStyle->getFont()->setBold(true);

        $currentRow = $headerRow + 1;
        foreach ($rows as $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $currentRow, $value);
            }
            $currentRow++;
        }

        if (! empty($options['summaryRows']) && is_array($options['summaryRows'])) {
            foreach ($options['summaryRows'] as $summaryRow) {
                foreach ($summaryRow as $colIndex => $value) {
                    $sheet->setCellValueByColumnAndRow($colIndex + 1, $currentRow, $value);
                }
                $currentRow++;
            }
        }

        if (! empty($options['columnFormats']) && is_array($options['columnFormats'])) {
            foreach ($options['columnFormats'] as $colIndex => $format) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                $range        = sprintf('%s%d:%s%d', $columnLetter, $headerRow + 1, $columnLetter, $currentRow - 1);
                $style        = $sheet->getStyle($range);

                switch ($format) {
                    case 'currency':
                        $style->getNumberFormat()->setFormatCode('"₹"#,##0.00');
                        break;
                    case 'date':
                        $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD2);
                        break;
                    case 'datetime':
                        $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
                        break;
                    default:
                        // Leave as-is.
                        break;
                }
            }
        }

        if (($options['autoFilter'] ?? true) && $currentRow > $headerRow + 1) {
            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->setAutoFilter(sprintf(
                '%s%d:%s%d',
                'A',
                $headerRow,
                $lastColumn,
                $currentRow - 1
            ));
        }

        if ($options['freezeHeader'] ?? true) {
            $sheet->freezePaneByColumnAndRow(1, $headerRow + 1);
        }

        $columnCount = count($headers);
        for ($col = 1; $col <= $columnCount; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $generatedAt = format_display_time(utc_now()) ?? utc_now();
        $sheet->setCellValueByColumnAndRow(1, $currentRow + 1, 'Generated on: ' . $generatedAt);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contents = (string) ob_get_clean();

        $spreadsheet->disconnectWorksheets();
        unset($writer, $spreadsheet);

        return $contents;
    }
}
