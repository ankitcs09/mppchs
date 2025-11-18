<?php

namespace App\Controllers\Helpdesk;

use App\Controllers\BaseController;
use App\Controllers\Traits\AuthorizationTrait;
use App\Services\BeneficiaryV2SnapshotService;
use App\Services\CashlessFormViewService;
use App\Services\Helpdesk\BeneficiaryDirectoryService;
use App\Services\Helpdesk\BeneficiaryPdfService;
use App\Services\Helpdesk\HelpdeskEditRequestService;
use CodeIgniter\HTTP\RedirectResponse;

class BeneficiariesController extends BaseController
{
    use AuthorizationTrait;

    public function __construct(
        private readonly BeneficiaryDirectoryService $directory = new BeneficiaryDirectoryService(),
        private readonly BeneficiaryPdfService $pdfService = new BeneficiaryPdfService(),
        private readonly BeneficiaryV2SnapshotService $snapshotService = new BeneficiaryV2SnapshotService(),
        private readonly CashlessFormViewService $cashlessViewService = new CashlessFormViewService(),
        private readonly HelpdeskEditRequestService $editRequestService = new HelpdeskEditRequestService()
    ) {
    }

    public function index()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('search_beneficiaries');

        $companyScope = $this->resolvedCompanyScope();
        $query        = trim((string) $this->request->getGet('q'));
        $page         = max(1, (int) $this->request->getGet('page'));

        $results = [];
        if ($query !== '') {
            $results = $this->directory->search($companyScope, [
                'query'     => $query,
                'page'      => $page,
                'per_page'  => 20,
            ]);
        } else {
            $results = [
                'rows'       => [],
                'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'pages' => 0],
            ];
        }

        return view('helpdesk/beneficiaries/index', [
            'activeNav'  => 'helpdesk-directory',
            'pageinfo'   => [
                'apptitle'    => 'Beneficiary Directory',
                'appdashname' => $this->session->get('bname') ?? 'Helpdesk',
            ],
            'query'      => $query,
            'results'    => $results['rows'],
            'pagination' => $results['pagination'],
        ]);
    }

    public function show(int $beneficiaryId)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('view_beneficiary_profile_full');

        $companyScope = $this->resolvedCompanyScope();
        $record       = $this->directory->findWithinCompany($companyScope, $beneficiaryId);
        if (! $record) {
            return redirect()->to(site_url('helpdesk/beneficiaries'))
                ->with('warning', 'Beneficiary not found within your organisation.');
        }

        $snapshot = $this->snapshotService->findByBeneficiaryId($beneficiaryId);
        if (! $snapshot) {
            return redirect()->to(site_url('helpdesk/beneficiaries'))
                ->with('warning', 'Unable to load beneficiary details.');
        }

        $pageInfo = [
            'apptitle'    => 'Beneficiary Profile',
            'appdashname' => $this->session->get('bname') ?? 'Helpdesk',
            'frmmsg'      => 'Submitted Cashless Option Form',
        ];

        $legacyId = isset($snapshot['legacy_beneficiary_id']) ? (int) $snapshot['legacy_beneficiary_id'] : null;
        $viewData = $this->cashlessViewService->buildViewData($pageInfo, $beneficiaryId, $legacyId);
        $viewData['activeNav']                = 'helpdesk-directory';
        $viewData['helpdesk_mode']            = true;
        $viewData['helpdesk_beneficiary_id']  = $beneficiaryId;
        $viewData['helpdesk_reference']       = $snapshot['reference_number'] ?? '';
        $viewData['helpdesk_company']         = $record['company_name'] ?? '';

        return view('cashless_form_view_readonly_datatable', $viewData);
    }

    public function download(int $beneficiaryId)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('download_beneficiary_pdf');

        $companyScope = $this->resolvedCompanyScope();
        $record       = $this->directory->findWithinCompany($companyScope, $beneficiaryId);
        if (! $record) {
            return redirect()->to(site_url('helpdesk/beneficiaries'))
                ->with('warning', 'Beneficiary not found within your organisation.');
        }

        $pdf = $this->pdfService->build(
            $beneficiaryId,
            $record['company_name'] ?? null,
            'Helpdesk',
            null
        );
        if (! $pdf) {
            return redirect()->back()->with('warning', 'Unable to Generate the Beneficiary PDF at the moment.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['content']);
    }

    public function requestEdit(int $beneficiaryId): RedirectResponse
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('search_beneficiaries');

        $notes = trim((string) $this->request->getPost('notes'));
        if ($notes === '') {
            return redirect()->back()->withInput()->with('error', 'Please Describe the Correction Requested.');
        }

        $attachments = $this->handleAttachments();
        $helpdeskUserId = (int) ($this->session->get('id') ?? 0);
        $companyId      = $this->session->get('company_id') ? (int) $this->session->get('company_id') : null;

        $this->editRequestService->createRequest(
            $beneficiaryId,
            $helpdeskUserId,
            $notes,
            $companyId,
            $attachments
        );

        return redirect()->back()->with('success', 'Edit Request Submitted to the Admin Team.');
    }

    /**
     * @return list<array<string,string|int|null>>
     */
    private function handleAttachments(): array
    {
        $files = $this->request->getFiles()['attachments'] ?? null;
        if ($files === null) {
            return [];
        }

        $targetDir = WRITEPATH . 'uploads/helpdesk';
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $stored = [];
        foreach ((array) $files as $file) {
            if (! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            try {
                if ($file->getSize() > 5 * 1024 * 1024) {
                    continue;
                }

                $mime = $file->getClientMimeType();
                if (! in_array($mime, ['application/pdf', 'image/png', 'image/jpeg'], true)) {
                    continue;
                }

                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads/helpdesk', $newName);
                $stored[] = [
                    'name' => $file->getClientName(),
                    'path' => 'uploads/helpdesk/' . $newName,
                    'size' => $file->getSize(),
                    'type' => $mime,
                ];
            } catch (\Throwable $exception) {
                log_message('error', '[Helpdesk] Unable to store attachment: {message}', ['message' => $exception->getMessage()]);
            }
        }

        return $stored;
    }

    /**
     * Resolve the effective company scope for the logged-in user.
     *
     * @return list<int>|null
     */
    private function resolvedCompanyScope(): ?array
    {
        $scope = $this->companyScopeIds();
        if ($scope === null) {
            return null;
        }

        if ($scope === []) {
            $sessionCompany = (int) ($this->session->get('company_id') ?? 0);
            if ($sessionCompany > 0) {
                return [$sessionCompany];
            }

            return null;
        }

        return $scope;
    }
}
