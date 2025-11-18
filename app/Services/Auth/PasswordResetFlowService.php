<?php

namespace App\Services\Auth;

use App\Config\AuthPolicy;
use App\Models\AppUserModel;
use App\Models\BeneficiaryV2Model;
use App\Services\PasswordPolicyService;
use App\Services\PasswordResetService;
use CodeIgniter\I18n\Time;

class PasswordResetFlowService
{
    private PasswordResetService $resetService;
    private PasswordPolicyService $policyService;
    private AppUserModel $appUsers;
    private BeneficiaryV2Model $beneficiaries;
    private AuthPolicy $policy;

    public function __construct(
        ?PasswordResetService $resetService = null,
        ?PasswordPolicyService $policyService = null,
        ?AppUserModel $appUsers = null,
        ?BeneficiaryV2Model $beneficiaries = null,
        ?AuthPolicy $policy = null
    ) {
        $this->resetService   = $resetService ?? new PasswordResetService();
        $this->policyService  = $policyService ?? new PasswordPolicyService();
        $this->appUsers       = $appUsers ?? new AppUserModel();
        $this->beneficiaries  = $beneficiaries ?? new BeneficiaryV2Model();
        $this->policy         = $policy ?? config('AuthPolicy');
    }

    public function verifyToken(string $selector, string $token): ?array
    {
        return $this->resetService->verifyToken($selector, $token);
    }

    /**
     * @return array{
     *   success:bool,
     *   errors?:array<string,string>,
     *   error?:string,
     *   message?:string,
     *   resetRecord?:array,
     *   debug_link?:string
     * }
     */
    public function submitResetRequest(array $input, string $ipAddress, string $userAgent): array
    {
        $username = trim((string) ($input['username'] ?? ''));
        $mobile   = preg_replace('/\D+/', '', (string) ($input['mobile'] ?? ''));

        log_message('debug', '[PasswordReset] Request received for username={username} from {ip}', [
            'username' => $username,
            'ip'       => $ipAddress,
        ]);

        $errors = [];
        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 100) {
            $errors['username'] = 'Please provide a valid username.';
        }

        if ($mobile === '' || strlen($mobile) < 8 || strlen($mobile) > 16) {
            $errors['mobile'] = 'Please enter your registered mobile number.';
        }

        if (! empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
            ];
        }

        $user = $this->appUsers->findByUsername($username);
        if (! $user) {
            $this->resetService->recordFailure(null, $username, $ipAddress, $userAgent, ['reason' => 'user_not_found']);
            log_message('notice', '[PasswordReset] No user matched username={username}', ['username' => $username]);
            return $this->mismatchResponse();
        }

        $status = strtolower((string) ($user['status'] ?? 'active'));
        if ($status !== 'active') {
            $this->resetService->recordFailure((int) $user['id'], $user['username'] ?? null, $ipAddress, $userAgent, ['reason' => 'account_inactive']);
            log_message('warning', '[PasswordReset] Inactive account reset attempt user_id={id}', ['id' => $user['id'] ?? null]);
            return [
                'success' => false,
                'error'   => 'Your account is not active. Please contact support.',
            ];
        }

        if (($user['user_type'] ?? 'beneficiary') !== 'beneficiary') {
            $this->resetService->recordFailure((int) $user['id'], $user['username'] ?? null, $ipAddress, $userAgent, ['reason' => 'unsupported_user_type']);
            log_message('warning', '[PasswordReset] Unsupported user type for username={username}', ['username' => $username]);
            return $this->mismatchResponse();
        }

        $beneficiary = $this->beneficiaries->find((int) ($user['beneficiary_v2_id'] ?? 0));
        if (! $beneficiary) {
            $this->resetService->recordFailure((int) $user['id'], $user['username'] ?? null, $ipAddress, $userAgent, ['reason' => 'beneficiary_missing']);
            log_message('warning', '[PasswordReset] Beneficiary record missing for user_id={id}', ['id' => $user['id'] ?? null]);
            return $this->mismatchResponse();
        }

        $mobileHash = $beneficiary['primary_mobile_hash'] ?? null;
        if (! $mobileHash) {
            $this->resetService->recordFailure((int) $user['id'], $user['username'] ?? null, $ipAddress, $userAgent, ['reason' => 'mobile_not_available']);
            log_message('warning', '[PasswordReset] Missing mobile hash for user_id={id}', ['id' => $user['id'] ?? null]);
            return $this->mismatchResponse();
        }

        $providedHash = hash('sha256', $mobile);
        if (! hash_equals($mobileHash, $providedHash)) {
            $this->resetService->recordFailure((int) $user['id'], $user['username'] ?? null, $ipAddress, $userAgent, ['reason' => 'mobile_mismatch']);
            log_message('notice', '[PasswordReset] Mobile mismatch for user_id={id}', ['id' => $user['id'] ?? null]);
            return $this->mismatchResponse();
        }

        if ($this->resetService->hasExceededDailyLimit((int) $user['id'], $this->policy->passwordResetDailyLimit)) {
            return [
                'success' => false,
                'error'   => 'You have reached the reset request limit for today. Please try again tomorrow or contact support.',
            ];
        }

        $resetRecord = $this->resetService->createToken(
            (int) $user['id'],
            $ipAddress,
            $userAgent,
            Time::now('UTC')->addMinutes(30)
        );

        $this->resetService->notifyUser($user, $beneficiary, $resetRecord);
        log_message('info', '[PasswordReset] Token generated for user_id={id} selector={selector}', [
            'id'       => $user['id'] ?? null,
            'selector' => $resetRecord['selector'] ?? null,
        ]);

        $response = [
            'success'     => true,
            'message'     => 'If the details provided are correct, a password reset link has been sent to your registered contact.',
            'resetRecord' => $resetRecord,
        ];

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $response['debug_link'] = site_url('password/reset/' . $resetRecord['selector'] . '/' . $resetRecord['token_plain']);
        }

        return $response;
    }

    /**
     * @param array $resetRecord verified token record
     * @param array $input expects keys new_password, confirm_password
     *
     * @return array{success:bool,errors?:array<string,string>,error?:string,message?:string}
     */
    public function completeReset(array $resetRecord, array $input, string $ipAddress, string $userAgent): array
    {
        $newPassword     = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');

        $minLength = max(1, (int) $this->policy->passwordMinLength);
        $errors    = [];

        if ($newPassword === '' || mb_strlen($newPassword) < $minLength) {
            $errors['new_password'] = 'Password must be at least ' . $minLength . ' characters.';
        }

        if ($confirmPassword === '' || mb_strlen($confirmPassword) < $minLength) {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (! empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
            ];
        }

        $user = $this->appUsers->find((int) $resetRecord['user_id']);
        if (! $user) {
            return [
                'success' => false,
                'error'   => 'Unable to locate your account. Please contact support.',
            ];
        }

        if (strtolower((string) ($user['status'] ?? 'active')) !== 'active') {
            return [
                'success' => false,
                'error'   => 'Your account is not active. Please contact support.',
            ];
        }

        $policyErrors = $this->policyService->validateNewPassword(
            (int) $user['id'],
            $newPassword,
            [
                'username'     => $user['username'] ?? null,
                'current_hash' => $user['password'] ?? null,
            ]
        );

        if (! empty($policyErrors)) {
            return [
                'success' => false,
                'errors'  => $policyErrors,
            ];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $nextSessionVersion = (int) ($user['session_version'] ?? 1) + 1;

        $this->appUsers->update((int) $user['id'], [
            'password'             => $newHash,
            'force_password_reset' => 0,
            'password_changed_at'  => Time::now('UTC')->toDateTimeString(),
            'session_version'      => $nextSessionVersion,
        ]);

        $this->policyService->recordPasswordChange((int) $user['id'], $newHash);
        $this->resetService->markTokenUsed((int) $resetRecord['id']);
        $this->resetService->recordSuccess((int) $user['id'], $ipAddress, $userAgent, ['reason' => 'password_reset']);
        log_message('info', '[PasswordReset] Password updated for user_id={id}', ['id' => $user['id'] ?? null]);

        return [
            'success' => true,
            'message' => 'Your password has been updated. Please sign in again with the new credentials.',
        ];
    }

    private function mismatchResponse(): array
    {
        return [
            'success' => false,
            'error'   => 'The username or mobile number did not match our records.',
        ];
    }
}
