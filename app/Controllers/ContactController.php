<?php

namespace App\Controllers;

use App\Services\SupportRequestService;
use Config\Services;

class ContactController extends BaseController
{
    public function __construct(
        private readonly SupportRequestService $supportRequests = new SupportRequestService()
    ) {
    }

    public function submit()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(site_url('contact'));
        }

        $validation = Services::validation();
        $rules = [
            'name'    => 'required|min_length[3]|max_length[120]',
            'email'   => 'required|valid_email|max_length[190]',
            'phone'   => 'permit_empty|max_length[30]',
            'subject' => 'required|min_length[5]|max_length[150]',
            'message' => 'required|min_length[10]|max_length[2000]',
        ];

        if (! $validation->setRules($rules)->withRequest($this->request)->run()) {
            return redirect()->to(site_url('contact'))->withInput()->with('errors', $validation->getErrors());
        }

        $payload = [
            'name'      => trim((string) $this->request->getPost('name')),
            'email'     => trim((string) $this->request->getPost('email')),
            'phone'     => trim((string) $this->request->getPost('phone')),
            'subject'   => trim((string) $this->request->getPost('subject')),
            'message'   => trim((string) $this->request->getPost('message')),
            'ip'        => $this->request->getIPAddress(),
            'userAgent' => (string) $this->request->getUserAgent(),
        ];

        $this->supportRequests->record($payload);

        return redirect()->to(site_url('contact'))
            ->with('success', 'Thank you, your request was shared with the helpdesk team. They will reach out shortly.');
    }
}
