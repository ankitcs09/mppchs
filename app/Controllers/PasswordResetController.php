<?php

namespace App\Controllers;

use App\Services\Auth\PasswordResetFlowService;
use CodeIgniter\HTTP\RedirectResponse;

class PasswordResetController extends BaseController
{
    private PasswordResetFlowService $flow;

    public function __construct(?PasswordResetFlowService $flow = null)
    {
        $this->flow = $flow ?? new PasswordResetFlowService();
    }

    public function request()
    {
        helper(['form']);

        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->handleRequest();
        }

        return view('security/forgot_password');
    }

    public function reset(string $selector, string $token)
    {
        helper(['form']);

        $resetRecord = $this->flow->verifyToken($selector, $token);
        if (! $resetRecord) {
            return redirect()->to(site_url('password/forgot'))
                ->with('error', 'The password reset link is invalid or has expired. Please request a new one.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->handleReset($resetRecord);
        }

        return view('security/reset_password', [
            'selector' => $resetRecord['selector'],
            'token'    => $resetRecord['token_plain'],
        ]);
    }

    private function handleRequest(): RedirectResponse
    {
        $result = $this->flow->submitResetRequest(
            (array) $this->request->getPost(),
            $this->request->getIPAddress() ?? 'unknown',
            (string) $this->request->getUserAgent()
        );

        if (! $result['success']) {
            $redirect = redirect()->to(site_url('password/forgot'))->withInput();

            if (! empty($result['errors'])) {
                $redirect->with('errors', $result['errors']);
            }

            if (! empty($result['error'])) {
                $redirect->with('error', $result['error']);
            }

            return $redirect;
        }

        $redirect = redirect()->to(site_url('password/forgot'))
            ->with('success', $result['message'] ?? 'If the details provided are correct, a password reset link has been sent to your registered contact.');

        if (! empty($result['debug_link'])) {
            $redirect->with('debug_reset_link', $result['debug_link']);
        }

        return $redirect;
    }

    private function handleReset(array $resetRecord): RedirectResponse
    {
        $result = $this->flow->completeReset(
            $resetRecord,
            (array) $this->request->getPost(),
            $this->request->getIPAddress() ?? 'unknown',
            (string) $this->request->getUserAgent()
        );

        if (! $result['success']) {
            if (! empty($result['errors'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('errors', $result['errors']);
            }

            return redirect()->to(site_url('password/forgot'))
                ->with('error', $result['error'] ?? 'Unable to reset your password. Please try again.');
        }

        return redirect()->to(site_url('login'))
            ->with('success', $result['message'] ?? 'Your password has been updated. You can now sign in with the new password.');
    }
}
