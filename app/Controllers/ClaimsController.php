<?php

namespace App\Controllers;

use App\Controllers\Traits\AuthorizationTrait;
use App\Services\Claims\ClaimRepositoryService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class ClaimsController extends BaseController
{
    use AuthorizationTrait;

    private ClaimRepositoryService $claims;

    public function __construct(?ClaimRepositoryService $service = null)
    {
        $this->claims = $service ?? service('claimRepository');
    }

    public function index()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('view_claims');

        $beneficiaryId = $this->session->get('beneficiary_v2_id');
        if (! $beneficiaryId) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('warning', 'Beneficiary record not associated with your account.');
        }

        $filters = $this->request->getGet() ?? [];
        $page    = (int) ($filters['page'] ?? 1);

        $listing = $this->claims->listBeneficiaryClaims((int) $beneficiaryId, $filters, $page);

        return view('claims/index', [
            'activeNav' => 'claims',
            'pageinfo'  => [
                'apptitle'    => 'My Claims',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'filters' => $filters,
            'listing' => $listing,
            'statuses'=> $this->claims->getStatusOptions(),
            'types'   => $this->claims->getTypeOptions(),
        ]);
    }

    public function show(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('view_claims');

        $beneficiaryId = $this->session->get('beneficiary_v2_id');
        if (! $beneficiaryId) {
            return redirect()->to(site_url('claims'))
                ->with('warning', 'Beneficiary record not associated with your account.');
        }

        $claim = $this->claims->findBeneficiaryClaim($id, (int) $beneficiaryId);
        if (! $claim) {
            return redirect()->to(site_url('claims'))
                ->with('warning', 'Claim not found.');
        }

        return view('claims/show', [
            'activeNav' => 'claims',
            'pageinfo'  => [
                'apptitle'    => 'Claim Details',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'claim'    => $claim,
            'statuses' => $this->claims->getStatusOptions(),
            'types'    => $this->claims->getTypeOptions(),
        ]);
    }

    public function export(): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('view_claims');

        $beneficiaryId = (int) $this->session->get('beneficiary_v2_id');
        if (! $beneficiaryId) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }

        $filters = $this->request->getGet() ?? [];
        $limit   = (int) ($filters['limit'] ?? 1000);
        $limit   = max(10, min($limit, 5000));

        $listing = $this->claims->listBeneficiaryClaims($beneficiaryId, $filters, 1, $limit);

        $headers = [
            'Claim #',
            'Status',
            'Type',
            'Hospital',
            'Claimed',
            'Approved',
            'Claim Date',
        ];

        $sheetRows = [];
        foreach ($listing['data'] ?? [] as $row) {
            $amounts = $row['amounts'] ?? [];
            $sheetRows[] = [
                $row['claim_reference'] ?? '',
                $row['status']['label'] ?? 'Unknown',
                $row['type']['label'] ?? '-',
                $row['hospital']['name'] ?? '-',
                (float) ($amounts['claimed'] ?? 0),
                (float) ($amounts['approved'] ?? 0),
                $row['dates']['claim'] ?? '',
            ];
        }

        $totals = $listing['summary'] ?? [];
        $summaryRow = [
            'Totals',
            '',
            '',
            '',
            (float) ($totals['total_claimed'] ?? 0),
            (float) ($totals['total_approved'] ?? 0),
            '',
        ];

        $spreadsheet = service('reportSpreadsheet');
        $xlsx = $spreadsheet->exportTable(
            'My Claims',
            $headers,
            $sheetRows,
            [
                'columnFormats' => [
                    4 => 'currency',
                    5 => 'currency',
                ],
                'summaryRows' => [$summaryRow],
            ]
        );

        $filename = 'my-claims-' . date('Ymd-His') . '.xlsx';

        return Services::response()
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    public function exportPdf(): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('view_claims');

        $beneficiaryId = (int) $this->session->get('beneficiary_v2_id');
        if (! $beneficiaryId) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }

        $filters = $this->request->getGet() ?? [];
        $limit   = (int) ($filters['limit'] ?? 1000);
        $limit   = max(10, min($limit, 5000));

        $listing = $this->claims->listBeneficiaryClaims($beneficiaryId, $filters, 1, $limit);

        $rows = [];
        foreach ($listing['data'] ?? [] as $row) {
            $amounts = $row['amounts'] ?? [];
            $rows[] = [
                'claim_reference' => $row['claim_reference'] ?? '',
                'status'          => $row['status']['label'] ?? 'Unknown',
                'type'            => $row['type']['label'] ?? '-',
                'hospital'        => $row['hospital']['name'] ?? '-',
                'claimed'         => number_format((float) ($amounts['claimed'] ?? 0), 2),
                'approved'        => number_format((float) ($amounts['approved'] ?? 0), 2),
                'claim_date'      => $row['dates']['claim'] ?? '',
            ];
        }

        $totals = [
            'claimed'  => number_format((float) ($listing['summary']['total_claimed'] ?? 0), 2),
            'approved' => number_format((float) ($listing['summary']['total_approved'] ?? 0), 2),
            'cashless' => number_format((float) ($listing['summary']['total_cashless'] ?? 0), 2),
            'copay'    => number_format((float) ($listing['summary']['total_copay'] ?? 0), 2),
        ];

        $pdfData = [
            'title'       => 'My Claims',
            'generatedAt' => date('Y-m-d H:i'),
            'rows'        => $rows,
            'totals'      => $totals,
        ];

        $pdfContent = service('reportPdf')->renderView('reports/beneficiary_claims_pdf', $pdfData, 'My Claims');
        $filename   = 'my-claims-' . date('Ymd-His') . '.pdf';

        return Services::response()
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfContent);
    }

    public function document(int $claimId, int $documentId): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('download_claim_documents');

        $beneficiaryId = $this->session->get('beneficiary_v2_id');
        if (! $beneficiaryId) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }

        $claim = $this->claims->findBeneficiaryClaim($claimId, (int) $beneficiaryId);
        if (! $claim) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
        }

        $document = null;
        foreach ($claim['documents'] as $doc) {
            if ($doc['id'] === $documentId) {
                $document = $doc;
                break;
            }
        }

        if (! $document) {
            return Services::response()->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
        }

        $context = [
            'user_id'    => $this->session->get('id'),
            'user_type'  => 'beneficiary',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'beneficiary_id' => (int) $beneficiaryId,
        ];

        return service('claimsDocumentStreamer')->stream($claim, $document, false, $context);
    }
}
