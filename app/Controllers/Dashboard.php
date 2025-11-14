<?php

namespace App\Controllers;

use App\Services\BeneficiaryDashboardAssembler;
use App\Services\CompanyDashboardAssembler;
use App\Services\BeneficiaryV2SnapshotService;
use App\Controllers\Traits\AuthorizationTrait;
use Config\Services;

class Dashboard extends BaseController
{
    use AuthorizationTrait;
    private BeneficiaryV2SnapshotService $snapshotService;
    private BeneficiaryDashboardAssembler $dashboardAssembler;
    private CompanyDashboardAssembler $companyDashboard;

    public function __construct()
    {
        $this->snapshotService    = new BeneficiaryV2SnapshotService();
        $this->dashboardAssembler = new BeneficiaryDashboardAssembler();
        $this->companyDashboard   = new CompanyDashboardAssembler();
    }

    public function index()
    {
        $this->ensureLoggedIn();

        $rbac = Services::rbac();

        $uri       = $this->request->getUri();
        $segments  = $uri->getSegments();
        $path      = trim($uri->getPath(), '/');
        $last      = array_slice($segments, -2);
        $isCompanyRoute = count($last) === 2 && $last[0] === 'dashboard' && $last[1] === 'v2';

        $context = $rbac->context();
        log_message(
            'debug',
            '[Dashboard] {path} accessed by user {user} permissions={perms}',
            [
                'path'  => $path === '' ? 'dashboard' : $path,
                'user'  => $this->session->get('id'),
                'perms' => json_encode(array_keys($context['permission_set'] ?? [])),
            ]
        );

        $hasCompanyView = $rbac->hasPermission('view_dashboard_company') || $rbac->hasPermission('view_dashboard_global');
        if ($hasCompanyView) {
            if ($isCompanyRoute) {
                $requestedScope   = $this->request->getGet('company');
                $companyDashboard = $this->companyDashboard->summarize($context, $requestedScope);

                return view('v2/dashboard_company', [
                    'pageinfo'    => $this->pageInfo('Company Dashboard'),
                    'activeNav'   => 'dashboard',
                    'rbacContext' => $context,
                    'dashboard'   => $companyDashboard,
                ]);
            }

            return redirect()->to(site_url('dashboard/v2'));
        }

        if ($isCompanyRoute) {
            return redirect()->to(site_url('dashboard'));
        }

        $record = $this->loadSnapshotRecord();
        if ($record === null) {
            return view('v2/dashboard_missing', [
                'pageinfo'  => $this->pageInfo('Beneficiary Dashboard'),
                'activeNav' => 'dashboard',
            ]);
        }

        $snapshot = $this->dashboardAssembler->assemble($record);

        return view('v2/dashboard', [
            'pageinfo'  => $this->pageInfo('Beneficiary Dashboard'),
            'activeNav' => 'dashboard',
            'snapshot'  => $snapshot,
        ]);
    }

    private function loadSnapshotRecord(): ?array
    {
        $beneficiaryV2Id = (int) ($this->session->get('beneficiary_v2_id') ?? 0);
        $legacyId        = (int) ($this->session->get('beneficiary_id') ?? 0);

        if ($beneficiaryV2Id > 0) {
            $record = $this->snapshotService->findByBeneficiaryId($beneficiaryV2Id);
            if ($record !== null) {
                return $record;
            }
        }

        if ($legacyId > 0) {
            return $this->snapshotService->findByLegacyBeneficiaryId($legacyId);
        }

        return null;
    }

    private function pageInfo(string $title): array
    {
        return [
            'apptitle'    => 'MPPGCL :: ' . $title,
            'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            'frmmsg'      => $title,
        ];
    }
}
