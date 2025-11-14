<?php

namespace App\Controllers;

use App\Services\BeneficiaryV2EditService;
use App\Controllers\Traits\AuthorizationTrait;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use RuntimeException;

class BeneficiaryEditController extends BaseController
{
    use AuthorizationTrait;
    private BeneficiaryV2EditService $service;

    public function __construct()
    {
        $this->service = new BeneficiaryV2EditService();
    }

    public function edit()
    {
        $this->ensureLoggedIn();

        $beneficiaryId = $this->currentBeneficiaryId();
        if ($beneficiaryId === null) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('error', 'Beneficiary record not found for the logged-in user.');
        }

        try {
            $payload = $this->service->buildInitialForm($beneficiaryId);
        } catch (RuntimeException $exception) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('error', $exception->getMessage());
        }

        $viewData = [
            'activeNav' => 'edit-profile',
            'pageinfo'  => [
                'apptitle'    => 'Edit Profile',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'masters'  => $this->service->getMasterOptions(),
            'snapshot' => $payload['snapshot'],
            'form'     => $payload['form'],
            'errors'   => $this->session->getFlashdata('errors') ?? [],
        ];

        return view('v2/edit_form', $viewData);
    }

    public function preview()
    {
        $this->ensureLoggedIn();

        $beneficiaryId = $this->currentBeneficiaryId();
        if ($beneficiaryId === null) {
            return redirect()->to(site_url('dashboard/v2'))
                ->with('error', 'Beneficiary record not found for the logged-in user.');
        }

        $input      = $this->request->getPost() ?? [];
        $validation = Services::validation();

        if (! $this->service->validateDraft($beneficiaryId, $input, $validation)) {
            $errors = $validation->getErrors();
            $dependentErrors = $this->service->getDependentValidationErrors();
            if (! empty($dependentErrors)) {
                $errors['dependents'] = $dependentErrors;
            }

            try {
                log_message('debug', '[EnrollmentEdit] validation errors: {errors}', [
                    'errors' => json_encode($errors, JSON_THROW_ON_ERROR),
                ]);
            } catch (\Throwable $exception) {
                log_message('debug', '[EnrollmentEdit] validation errors (unencoded): {errors}', [
                    'errors' => print_r($errors, true),
                ]);
            }

            return redirect()->back()
                ->withInput()
                ->with('errors', $errors);
        }

        $preview = $this->service->buildPreviewPayload($beneficiaryId, $input);
        $preview['user_id'] = (int) $this->session->get('id');

        $this->session->set('enrollment_edit_preview', [
            'beneficiary_id' => $beneficiaryId,
            'draft'          => $preview,
        ]);

        $viewData = [
            'activeNav' => 'edit-profile',
            'pageinfo'  => [
                'apptitle'    => 'Review Changes',
                'appdashname' => $this->session->get('bname') ?? 'Beneficiary',
            ],
            'preview'   => $preview,
            'lookups'   => $this->service->getDiffLookups(),
        ];

        return view('v2/edit_preview', $viewData);
    }

    public function confirm(): RedirectResponse
    {
        $this->ensureLoggedIn();

        $payload = $this->session->get('enrollment_edit_preview');
        if (! is_array($payload) || empty($payload['beneficiary_id']) || empty($payload['draft'])) {
            return redirect()->to(site_url('enrollment/edit'))
                ->with('warning', 'No pending changes were found. Please review your edits again.');
        }

        $payload['draft']['user_id'] = $payload['draft']['user_id'] ?? (int) $this->session->get('id');

        try {
            $this->service->applyConfirmedChanges(
                (int) $payload['beneficiary_id'],
                $payload['draft'],
                [
                    'ip_address' => $this->request->getIPAddress(),
                    'user_agent' => (string) $this->request->getUserAgent(),
                ]
            );
            $this->session->remove('enrollment_edit_preview');
            return redirect()->to(site_url('dashboard/v2'))
                ->with('success', 'Your profile changes have been submitted for review.');
        } catch (RuntimeException $exception) {
            log_message('notice', 'Beneficiary edit confirmation pending: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to(site_url('enrollment/edit'))
                ->with('warning', 'Unable to submit your changes: ' . $exception->getMessage());
        }
    }

    private function currentBeneficiaryId(): ?int
    {
        $beneficiaryId = $this->session->get('beneficiary_v2_id');
        if ($beneficiaryId === null) {
            return null;
        }

        return (int) $beneficiaryId;
    }
}
