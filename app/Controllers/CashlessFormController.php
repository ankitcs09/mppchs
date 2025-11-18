<?php

namespace App\Controllers;

use App\Models\BeneficiaryModel;
use App\Models\BeneficiaryResidenceModel;
use App\Models\CityModel;
use App\Models\DependentModel;
use App\Models\StateModel;
use App\Services\CashlessFormViewService;

class CashlessFormController extends BaseController
{
    private CashlessFormViewService $viewService;

    public function __construct()
    {
        $this->viewService = new CashlessFormViewService();
    }

    private function needLogin(): bool
    {
        return ! session()->get('isLoggedIn') || ! session()->get('id');
    }

    public function show()
    {
        if ($this->needLogin()) {
            return redirect()->to('/');
        }

        $pageinfo = [
            'apptitle'    => 'MPPGCL :: Submitted Cashless Form',
            'appdashname' => session()->get('bname') ?? 'MPPGCL',
            'frmmsg'      => 'Details of Submitted Cashless Option Form',
        ];

        $beneficiaryV2Id = (int) session()->get('beneficiary_v2_id');
        $legacyBeneficiaryId = (int) session()->get('beneficiary_id');
        $viewData            = $this->viewService->buildViewData($pageinfo, $beneficiaryV2Id, $legacyBeneficiaryId);

        return view('cashless_form_view_readonly_datatable', $viewData);
    }

    public function requestEdit()
    {
        if ($this->needLogin()) {
            return redirect()->to('/');
        }

        return redirect()->to(site_url('dashboard/cashless-form/edit'));
    }

    public function edit()
    {
        if ($this->needLogin()) {
            return redirect()->to('/');
        }

        $beneficiaryId    = (int) session()->get('beneficiary_id');
        $beneficiaries    = new BeneficiaryModel();
        $dependentsModel  = new DependentModel();
        $residenceModel   = new BeneficiaryResidenceModel();
        $cityModel        = new CityModel();
        $stateModel       = new StateModel();

        $beneficiary = $beneficiaries->find($beneficiaryId);
        if (! $beneficiary) {
            return redirect()->to(site_url('dashboard/cashless-form'));
        }

        $residence = $residenceModel->getCurrentResidence($beneficiaryId);
        $city      = null;
        $state     = null;

        if ($residence && ! empty($residence['city_id'])) {
            $city  = $cityModel->find((int) $residence['city_id']);
            $state = $city && isset($city['state_id'])
                ? $stateModel->find((int) $city['state_id'])
                : null;
        }

        $dependents = $dependentsModel
            ->where('beneficiary_id', $beneficiaryId)
            ->orderBy('relation')
            ->findAll();

        return view('cashless_form_view_edit', [
            'beneficiary' => $beneficiary,
            'dependents'  => $dependents,
            'residence'   => $residence,
            'city'        => $city,
            'state'       => $state,
        ]);
    }

    public function update()
    {
        if ($this->needLogin()) {
            return redirect()->to('/');
        }

        $beneficiaryId   = (int) session()->get('beneficiary_id');
        $beneficiaries   = new BeneficiaryModel();
        $dependentsModel = new DependentModel();

        $existing = $beneficiaries->find($beneficiaryId);
        if (! $existing) {
            return redirect()->to(site_url('dashboard/cashless-form'));
        }

        $payload = $this->request->getPost();

        $beneficiaryData = [];
        foreach ($beneficiaries->allowedFields as $field) {
            if (array_key_exists($field, $payload)) {
                $beneficiaryData[$field] = $payload[$field] === '' ? null : $payload[$field];
            }
        }

        $beneficiaryData['id'] = $beneficiaryId;
        $beneficiaries->save($beneficiaryData);

        if (isset($payload['dependent']) && is_array($payload['dependent'])) {
            foreach ($payload['dependent'] as $order => $row) {
                if (! isset($row['id']) && empty($row['name']) && empty($row['relation']) && empty($row['status'])) {
                    continue;
                }

                $row['beneficiary_id']  = $beneficiaryId;
                $row['dependent_order'] = $order + 1;

                if (isset($row['is_dependent_for_health']) && $row['is_dependent_for_health'] !== '') {
                    $row['is_dependent_for_health'] = (int) $row['is_dependent_for_health'];
                } else {
                    $row['is_dependent_for_health'] = null;
                }

                $dependentsModel->save($row);
            }
        }

        return redirect()->to(site_url('dashboard/cashless-form'))
            ->with('msg', 'Form updated successfully.');
    }
}

