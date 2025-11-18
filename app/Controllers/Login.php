<?php

namespace App\Controllers;

use App\Services\Auth\LoginService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Services;
use Throwable;

class Login extends BaseController
{
    private LoginService $loginService;

    public function __construct()
    {
        $this->loginService = new LoginService();
    }

    public function index()
    {
        if (session()->get('isLoggedIn')) {
            $target = 'dashboard';
            try {
                if (Services::rbac()->hasPermission('view_dashboard_company')) {
                    $target = 'dashboard/v2';
                }
            } catch (Throwable $e) {
                // ignore and fall back to default
            }

            return redirect()->to(site_url($target));
        }

        return view('login');
    }

    public function useraccess(): RedirectResponse
    {
        helper(['form']);

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to('login');
        }

        $validation = Services::validation();
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $validation->setRules($rules)->withRequest($this->request)->run()) {
            session()->setFlashdata('error', 'Please provide both username and password.');
            return redirect()->to('login')->withInput();
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        $result = $this->loginService->attempt($username, $password, $remember);
        if (! $result['success']) {
            if (! empty($result['handoff'])) {
                return redirect()->to($result['redirect'] ?? site_url('login/handoff'));
            }
            session()->setFlashdata('error', $result['error'] ?? 'Unable to sign in.');
            return redirect()->to('login')->withInput();
        }

        if (isset($result['flash'])) {
            session()->setFlashdata($result['flash']['type'], $result['flash']['message']);
        }

        return redirect()->to($result['redirect'] ?? site_url('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        $this->loginService->logout();
        return redirect()->to(site_url('/'));
    }

    public function handoff()
    {
        $context = $this->loginService->getPendingHandoff();
        if ($context === null || empty($context['user'])) {
            return redirect()->to('login');
        }

        $existing = $context['existing'] ?? null;
        $lastSeen = $existing && ! empty($existing['last_seen_at'])
            ? format_display_time($existing['last_seen_at'])
            : null;

        return view('auth/handoff', [
            'title'    => 'Confirm new login',
            'user'     => $context['user'],
            'method'   => $context['method'],
            'existing' => $existing,
            'lastSeen' => $lastSeen,
        ]);
    }

    public function handoffConfirm(): RedirectResponse
    {
        $result = $this->loginService->completePendingHandoff();
        if (! $result['success']) {
            session()->setFlashdata('error', $result['error'] ?? 'Unable to continue the session. Please sign in again.');
            return redirect()->to('login');
        }

        if (isset($result['flash'])) {
            session()->setFlashdata($result['flash']['type'], $result['flash']['message']);
        }

        return redirect()->to($result['redirect'] ?? site_url('dashboard'));
    }

    public function handoffCancel(): RedirectResponse
    {
        $this->loginService->cancelPendingHandoff();
        session()->setFlashdata('info', 'Sign-in cancelled. Your previous session remains active.');
        return redirect()->to('login');
    }
}
