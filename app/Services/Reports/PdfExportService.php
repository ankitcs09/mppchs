<?php

namespace App\Services\Reports;

use Mpdf\Mpdf;
use RuntimeException;

class PdfExportService
{
    public function renderHtml(string $html, string $title = 'Report'): string
    {
        if (! class_exists(Mpdf::class)) {
            throw new RuntimeException('mPDF is not installed. Please run composer install.');
        }

        $tempDir = WRITEPATH . 'mpdf';
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'format'  => 'A4',
        ]);
        $mpdf->SetTitle($title);
        $mpdf->setFooter('Page {PAGENO}');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function renderView(string $view, array $data = [], string $title = 'Report'): string
    {
        $html = view($view, $data);
        return $this->renderHtml($html, $title);
    }
}

