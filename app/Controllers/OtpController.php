<?php

namespace App\Controllers;

use App\Services\Auth\OtpLoginService;
use CodeIgniter\HTTP\RedirectResponse;

class OtpController extends BaseController
{
    private OtpLoginService $otp;

    public function __construct()
    {
        $this->otp = new OtpLoginService();
    }

    public function index(): string
    {
        return view('otp_form');
    }

    public function verifyForm(): RedirectResponse|string
    {
        if (! session()->has('otp_login_mobile')) {
            return redirect()->to(site_url('login/otp'))
                ->with('error', 'Please request an OTP before attempting verification.');
        }

        return view('otp_verify', [
            'maskedMobile' => session()->get('otp_login_mobile_masked'),
            'isLoggedIn'   => (bool) session()->get('isLoggedIn'),
        ]);
    }

    public function sendOtp(): RedirectResponse
    {
        $mobileInput = trim((string) $this->request->getPost('mobile'));
        $result      = $this->otp->requestOtp($mobileInput);

        if (! $result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['error'] ?? 'Unable to process OTP request.');
        }

        return redirect()->to($result['redirect'] ?? site_url('login/otp/verify'))
            ->with($result['flash']['type'], $result['flash']['message']);
    }

    public function resend(): RedirectResponse
    {
        $mobileId = session()->get('otp_login_mobile');

        if ($mobileId === null) {
            return redirect()->to(site_url('login/otp'))
                ->with('error', 'Your OTP session has ended. Please request a new code.');
        }

        $result = $this->otp->resend((string) $mobileId);

        if (! $result['success']) {
            $target = $result['redirect'] ?? site_url('login/otp/verify');
            return redirect()->to($target)
                ->with('error', $result['error'] ?? 'Unable to resend OTP right now.');
        }

        return redirect()->to($result['redirect'] ?? site_url('login/otp/verify'))
            ->with($result['flash']['type'], $result['flash']['message']);
    }

    public function verifyOtp(): RedirectResponse
    {
        $mobileId = session()->get('otp_login_mobile');
        $otpInput = (string) $this->request->getPost('otp');

        $result = $this->otp->verify($mobileId, $otpInput);

        if (! $result['success']) {
            $target   = $result['redirect'] ?? site_url('login/otp/verify');
            $redirect = redirect()->to($target)->with('error', $result['error'] ?? 'Unable to verify the OTP.');

            if ($target === site_url('login/otp/verify')) {
                return $redirect->withInput();
            }

            return $redirect;
        }

        return redirect()->to($result['redirect'] ?? site_url('dashboard'))
            ->with($result['flash']['type'], $result['flash']['message']);
    }
}
