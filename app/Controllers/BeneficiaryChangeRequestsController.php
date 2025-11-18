<?php

namespace App\Controllers;

use App\Controllers\Traits\AuthorizationTrait;
use App\Services\BeneficiaryChangeRequestService;
use RuntimeException;

class BeneficiaryChangeRequestsController extends BaseController
{
    use AuthorizationTrait;

    private BeneficiaryChangeRequestService $changeRequests;

    public function __construct()
    {
        $this->changeRequests = service('beneficiaryChangeRequest');
    }

    public function index()
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('edit_beneficiary_profile');

        $beneficiaryId = (int) ($this->session->get('beneficiary_v2_id') ?? 0);
        if ($beneficiaryId <= 0) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('warning', 'Beneficiary record not found for the logged-in user.');
        }

        $requests = $this->changeRequests->listForBeneficiary($beneficiaryId);

        $viewData = [
            'activeNav' => 'change-requests',
            'pageinfo'  => [
                'apptitle'    => 'My Change Requests',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'requests'  => $requests,
        ];

        return view('v2/change_requests/index', $viewData);
    }

    public function show(int $requestId)
    {
        $this->ensureLoggedIn();
        $this->enforcePermission('edit_beneficiary_profile');

        $beneficiaryId = (int) ($this->session->get('beneficiary_v2_id') ?? 0);
        if ($beneficiaryId <= 0) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('warning', 'Beneficiary record not found for the logged-in user.');
        }

        try {
            $detail = $this->changeRequests->getRequestForBeneficiary($beneficiaryId, $requestId);
        } catch (RuntimeException $exception) {
            return redirect()->to(site_url('enrollment/change-requests'))
                ->with('warning', 'Unable to locate that change request.');
        }

        $viewData = [
            'activeNav' => 'change-requests',
            'pageinfo'  => [
                'apptitle'    => 'Change Request Details',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'detail'    => $detail,
        ];

        return view('v2/change_requests/show', $viewData);
    }
}

