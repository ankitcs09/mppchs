<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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
        $this->enforcePermission('manage_claims');

        $filters = $this->request->getGet() ?? [];
        $page    = (int) ($filters['page'] ?? 1);

        $listing = $this->claims->listAdminClaims(
            $filters,
            $this->companyScopeIds(),
            $page
        );

        return view('admin/claims/index', [
            'activeNav' => 'admin-claims',
            'pageinfo'  => [
                'apptitle'    => 'Claims Registry',
                'appdashname' => $this->session->get('bname') ?? 'Admin',
            ],
            'filters'  => $filters,
            'listing'  => $listing,
            'statuses' => $this->claims->getStatusOptions(),
            'types'    => $this->claims->getTypeOptions(),
        ]);
    }

    public function show(int $id)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $claim = $this->claims->findAdminClaim($id, $this->companyScopeIds());
        if (! $claim) {
            return redirect()->to(site_url('admin/claims'))
                ->with('warning', 'Claim not found or outside your scope.');
        }

        return view('admin/claims/show', [
            'activeNav' => 'admin-claims',
            'pageinfo'  => [
                'apptitle'    => 'Claim Details',
                'appdashname' => $this->session->get('bname') ?? 'Admin',
            ],
            'claim'    => $claim,
            'statuses' => $this->claims->getStatusOptions(),
            'types'    => $this->claims->getTypeOptions(),
        ]);
    }

    public function document(int $claimId, int $documentId): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('download_claim_documents');

        $claim = $this->claims->findAdminClaim($claimId, $this->companyScopeIds());
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
            'user_type'  => 'admin',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'company_ids'=> $this->companyScopeIds(),
        ];

        return service('claimsDocumentStreamer')->stream($claim, $document, true, $context);
    }

    public function export(): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $filters = $this->request->getGet() ?? [];
        $limit   = (int) ($filters['limit'] ?? 2000);
        $limit   = max(10, min($limit, 10000));

        $listing = $this->claims->listAdminClaims(
            $filters,
            $this->companyScopeIds(),
            1,
            $limit
        );

        $headers = [
            'Claim #',
            'Company',
            'Beneficiary',
            'Status',
            'Type',
            'Hospital',
            'Claimed',
            'Approved',
            'Cashless',
            'Co-pay',
            'Non-payable',
            'Claim Date',
        ];

        $sheetRows = [];
        $rows = $listing['data'] ?? [];
        foreach ($rows as $row) {
            $beneficiary = $row['beneficiary'] ?? [];
            $company     = $row['company'] ?? [];
            $amounts     = $row['amounts'] ?? [];
            $sheetRows[] = [
                $row['claim_reference'] ?? '',
                $company['code'] ?? '',
                $beneficiary['name'] ?? '',
                $row['status']['label'] ?? 'Unknown',
                $row['type']['label'] ?? '-',
                $row['hospital']['name'] ?? '-',
                (float) ($amounts['claimed'] ?? 0),
                (float) ($amounts['approved'] ?? 0),
                (float) ($amounts['cashless'] ?? 0),
                (float) ($amounts['copay'] ?? 0),
                (float) ($amounts['non_payable'] ?? 0),
                $row['dates']['claim'] ?? '',
            ];
        }

        $totals = $listing['summary']['totals'] ?? [];
        $summaryRow = [
            'Totals',
            '',
            '',
            '',
            '',
            '',
            (float) ($totals['total_claimed'] ?? 0),
            (float) ($totals['total_approved'] ?? 0),
            (float) ($totals['total_cashless'] ?? 0),
            (float) ($totals['total_copay'] ?? 0),
            (float) ($totals['total_non_payable'] ?? 0),
            '',
        ];

        $spreadsheetService = service('reportSpreadsheet');
        $xlsx = $spreadsheetService->exportTable(
            'Claims Registry',
            $headers,
            $sheetRows,
            [
                'columnFormats' => [
                    6  => 'currency',
                    7  => 'currency',
                    8  => 'currency',
                    9  => 'currency',
                    10 => 'currency',
                ],
                'summaryRows'   => [$summaryRow],
            ]
        );

        $filename = 'claims-export-' . date('Ymd-His') . '.xlsx';

        return Services::response()
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($xlsx);
    }

    public function exportPdf(): ResponseInterface
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('manage_claims');

        $filters = $this->request->getGet() ?? [];
        $limit   = (int) ($filters['limit'] ?? 2000);
        $limit   = max(10, min($limit, 10000));

        $listing = $this->claims->listAdminClaims(
            $filters,
            $this->companyScopeIds(),
            1,
            $limit
        );

        $rows = [];
        foreach ($listing['data'] ?? [] as $row) {
            $beneficiary = $row['beneficiary'] ?? [];
            $company     = $row['company'] ?? [];
            $amounts     = $row['amounts'] ?? [];
            $rows[] = [
                'claim_reference' => $row['claim_reference'] ?? '',
                'company'         => $company['code'] ?? '',
                'beneficiary'     => $beneficiary['name'] ?? '',
                'status'          => $row['status']['label'] ?? 'Unknown',
                'type'            => $row['type']['label'] ?? '-',
                'hospital'        => $row['hospital']['name'] ?? '-',
                'claimed'         => number_format((float) ($amounts['claimed'] ?? 0), 2),
                'approved'        => number_format((float) ($amounts['approved'] ?? 0), 2),
                'cashless'        => number_format((float) ($amounts['cashless'] ?? 0), 2),
                'copay'           => number_format((float) ($amounts['copay'] ?? 0), 2),
                'non_payable'     => number_format((float) ($amounts['non_payable'] ?? 0), 2),
                'claim_date'      => $row['dates']['claim'] ?? '',
            ];
        }

        $totals = $listing['summary']['totals'] ?? [];
        $pdfData = [
            'title'       => 'Claims Registry',
            'generatedAt' => date('Y-m-d H:i'),
            'filters'     => $filters,
            'rows'        => $rows,
            'totals'      => [
                'claimed'     => number_format((float) ($totals['total_claimed'] ?? 0), 2),
                'approved'    => number_format((float) ($totals['total_approved'] ?? 0), 2),
                'cashless'    => number_format((float) ($totals['total_cashless'] ?? 0), 2),
                'copay'       => number_format((float) ($totals['total_copay'] ?? 0), 2),
                'non_payable' => number_format((float) ($totals['total_non_payable'] ?? 0), 2),
            ],
        ];

        $pdfContent = service('reportPdf')->renderView('reports/admin_claims_pdf', $pdfData, 'Claims Registry');
        $filename   = 'claims-export-' . date('Ymd-His') . '.pdf';

        return Services::response()
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdfContent);
    }
}
