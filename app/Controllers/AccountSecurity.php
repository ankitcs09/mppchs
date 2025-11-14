<?php

namespace App\Controllers;

use App\Models\AppUserModel;
use App\Models\UserAccountModel;
use App\Services\Auth\RememberMeService;
use App\Services\PasswordPolicyService;
use App\Controllers\Traits\AuthorizationTrait;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Config\Services;

class AccountSecurity extends BaseController
{
    use AuthorizationTrait;
    public function changePassword()
    {
        $this->ensureLoggedIn();

        $request = $this->request;
        $session = $this->session;
        $isPost  = strtolower($request->getMethod()) === 'post';

        if (! $isPost) {
            return view('security/change_password', [
                'pageinfo' => [
                    'apptitle'    => 'Change Password',
                    'appdashname' => $session->get('bname') ?? 'Account',
                ],
                'activeNav' => 'change-password',
            ]);
        }

        $validation   = Services::validation();
        $policyConfig = config('AuthPolicy');
        $minLength    = max(1, (int) $policyConfig->passwordMinLength);
        $rules = [
            'current_password' => 'required|min_length[6]',
            'new_password'     => 'required|min_length[' . $minLength . ']',
            'confirm_password' => 'required|matches[new_password]|min_length[' . $minLength . ']',
        ];

        if (! $validation->setRules($rules)->withRequest($request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $authTable = (string) $session->get('authUserTable');
        $userId    = (int) $session->get('id');

        $model = match ($authTable) {
            'app_users' => new AppUserModel(),
            'tmusers'   => new UserAccountModel(),
            default     => new AppUserModel(),
        };
        $user  = $model->find($userId);

        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Unable to locate your account. Please contact support.');
        }

        $currentPassword = (string) $request->getPost('current_password');
        if (! password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'The current password provided is incorrect.');
        }

        $newPassword = (string) $request->getPost('new_password');

        $policy = new PasswordPolicyService();
        $policyErrors = $policy->validateNewPassword($userId, $newPassword, [
            'username'     => $user['username'] ?? null,
            'current_hash' => $user['password'] ?? null,
        ]);

        if (! empty($policyErrors)) {
            return redirect()->back()->withInput()->with('errors', $policyErrors);
        }

        $nextSessionVersion = null;
        if ($authTable === 'app_users') {
            $nextSessionVersion = (int) ($user['session_version'] ?? 1) + 1;
        }

        $update = [
            'password'             => password_hash($newPassword, PASSWORD_DEFAULT),
            'force_password_reset' => 0,
            'password_changed_at'  => Time::now('UTC')->toDateTimeString(),
        ];

        if ($nextSessionVersion !== null) {
            $update['session_version'] = $nextSessionVersion;
        }

        $model->update($userId, $update);

        $policy->recordPasswordChange($userId, $update['password']);

        $rememberService = new RememberMeService();
        $rememberService->forgetAllForUser($userId, $authTable);

        $session->setFlashdata('success', 'Password updated successfully. You will be signed out to keep your account secure.');

        $logoutUrl = site_url('logout?reason=password-change');

        return view('security/logout_notice', [
            'pageinfo' => [
                'apptitle'    => 'Password Updated',
                'appdashname' => 'Account',
            ],
            'logoutUrl' => $logoutUrl,
            'delayMs'   => 3000,
        ]);
    }
}


