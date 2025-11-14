<?php

namespace App\Services\Helpdesk;

use App\Services\BeneficiaryV2SnapshotService;
use App\Services\CashlessFormViewService;
use CodeIgniter\I18n\Time;
use Mpdf\Mpdf;

class BeneficiaryPdfService
{
    public function __construct(
        private readonly BeneficiaryV2SnapshotService $snapshotService = new BeneficiaryV2SnapshotService(),
        private readonly CashlessFormViewService $cashlessViewService = new CashlessFormViewService()
    ) {
    }

    public function build(
        int $beneficiaryId,
        ?string $companyName = null,
        ?string $generatedBy = null,
        ?int $generatedById = null
    ): ?array {
        $snapshot = $this->snapshotService->findByBeneficiaryId($beneficiaryId);
        if (! $snapshot) {
            return null;
        }

        $legacyId = isset($snapshot['legacy_beneficiary_id'])
            ? (int) $snapshot['legacy_beneficiary_id']
            : null;

        $pageInfo = [
            'apptitle'    => 'Beneficiary Profile',
            'appdashname' => 'Helpdesk',
            'frmmsg'      => 'Submitted Cashless Option Form',
        ];

        $viewData = $this->cashlessViewService->buildViewData($pageInfo, $beneficiaryId, $legacyId);
        $generatedAt = Time::now('UTC');
        $formattedGenerated = $this->formatTimestamp($generatedAt);
        $lastUpdatedDisplay = $this->formatTimestamp($viewData['lastUpdated'] ?? null);

        $mpdf = new Mpdf([
            'format'  => 'A4',
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $html = view('helpdesk/beneficiaries/pdf', [
            'snapshot'               => $snapshot,
            'beneficiary'            => $viewData['beneficiary'] ?? null,
            'detailSections'         => $viewData['detailSections'] ?? [],
            'dependentsCovered'      => $viewData['dependentsCovered'] ?? [],
            'dependentsNotDependent' => $viewData['dependentsNotDependent'] ?? [],
            'dependentsOverview'     => $viewData['dependentsOverview'] ?? [],
            'lastUpdatedDisplay'     => $lastUpdatedDisplay,
            'companyName'            => $companyName ?? ($snapshot['company_name'] ?? null),
            'generatedBy'            => $generatedBy,
            'generatedById'          => $generatedById,
            'generatedAtDisplay'     => $formattedGenerated,
        ]);

        $mpdf->WriteHTML($html);

        return [
            'filename' => sprintf('Beneficiary-%s.pdf', $snapshot['reference_number'] ?? $beneficiaryId),
            'content'  => $mpdf->Output('', 'S'),
        ];
    }

    private function formatTimestamp($value): string
    {
        if ($value instanceof Time) {
            return $value->toLocalizedString('dd MMM yyyy, hh:mm a');
        }

        if (empty($value)) {
            return '-';
        }

        try {
            return Time::parse((string) $value, 'UTC')->toLocalizedString('dd MMM yyyy, hh:mm a');
        } catch (\Throwable) {
            // fallthrough
        }

        return (string) $value;
    }
}
