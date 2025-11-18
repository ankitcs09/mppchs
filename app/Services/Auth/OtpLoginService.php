<?php

namespace App\Services\Auth;

use App\Models\OtpModel;
use App\Services\OtpWorkflowService;

class OtpLoginService
{
    private OtpWorkflowService $workflow;
    private SessionManager $sessionManager;
    private SessionRegistry $sessionRegistry;
    private int $otpLength;

    public function __construct(
        ?OtpWorkflowService $workflow = null,
        ?SessionManager $sessionManager = null,
        ?SessionRegistry $sessionRegistry = null,
        int $otpLength = 6,
    ) {
        $this->workflow        = $workflow ?? new OtpWorkflowService(new OtpModel(), $otpLength);
        $this->sessionManager  = $sessionManager ?? new SessionManager();
        $this->sessionRegistry = $sessionRegistry ?? new SessionRegistry();
        $this->otpLength       = $otpLength;
    }

    /**
     * @return array{success:bool,redirect?:string,flash?:array{type:string,message:string},error?:string}
     */
    public function requestOtp(string $mobile): array
    {
        log_message('debug', '[Auth][OtpLogin] Initiating OTP for mobile={mobile}', ['mobile' => $mobile]);
        $result = $this->workflow->initiate($mobile);
        if (! $result['success']) {
            log_message('notice', '[Auth][OtpLogin] OTP request failed: {message}', [
                'message' => $result['message'] ?? 'unknown',
            ]);
            return [
                'success' => false,
                'error'   => $result['message'] ?? 'Unable to process OTP request.',
            ];
        }

        $session = session();
        if (isset($result['session'])) {
            $session->set($result['session']);
        }

        if (! empty($result['debug_code'])) {
            $session->setFlashdata('otp_debug_code', $result['debug_code']);
        }

        $masked = $result['masked_mobile'] ?? $session->get('otp_login_mobile_masked');
        $message = ($result['delivery_status'] ?? false)
            ? 'We sent a one-time password to ' . $masked . '.'
            : 'OTP generated. Unable to confirm SMS delivery; please contact support if the message does not arrive.';
        log_message('info', '[Auth][OtpLogin] OTP issued for mobile={mobile}', [
            'mobile' => $session->get('otp_login_mobile_full') ?? $masked,
        ]);

        return [
            'success'  => true,
            'redirect' => site_url('login/otp/verify'),
            'flash'    => ['type' => 'success', 'message' => $message],
        ];
    }

    /**
     * @return array{success:bool,redirect?:string,flash?:array{type:string,message:string},error?:string}
     */
    public function resend(string $mobileId): array
    {
        log_message('debug', '[Auth][OtpLogin] Resend requested for mobileId={mobileId}', ['mobileId' => $mobileId]);
        $result = $this->workflow->resend($mobileId);

        if (! $result['success']) {
            if (! empty($result['cleanup'])) {
                $this->sessionManager->clearOtpSession();
            }
            log_message('notice', '[Auth][OtpLogin] OTP resend failed: {message}', [
                'message' => $result['message'] ?? 'unknown',
            ]);

            return [
                'success'  => false,
                'error'    => $result['message'] ?? 'Unable to resend OTP right now.',
                'redirect' => ! empty($result['cleanup']) ? site_url('login/otp') : site_url('login/otp/verify'),
            ];
        }

        $session = session();
        if (isset($result['session'])) {
            $session->set($result['session']);
        }

        if (! empty($result['debug_code'])) {
            $session->setFlashdata('otp_debug_code', $result['debug_code']);
        }

        $masked = $session->get('otp_login_mobile_masked');
        $message = ($result['delivery_status'] ?? false)
            ? 'We resent the one-time password to ' . $masked . '.'
            : 'OTP generated. Unable to confirm SMS delivery; please contact support if the message does not arrive.';
        log_message('info', '[Auth][OtpLogin] OTP resent for mobileId={mobileId}', ['mobileId' => $mobileId]);

        return [
            'success'  => true,
            'redirect' => site_url('login/otp/verify'),
            'flash'    => ['type' => 'success', 'message' => $message],
        ];
    }

    /**
     * @return array{success:bool,redirect?:string,flash?:array{type:string,message:string},error?:string}
     */
    public function verify(?string $mobileId, string $otpInput): array
    {
        $session = session();

        if ($mobileId === null) {
            $this->sessionManager->clearOtpSession();
            log_message('notice', '[Auth][OtpLogin] OTP verification failed due to expired session');
            return [
                'success'  => false,
                'error'    => 'Your OTP session expired. Please request a new code.',
                'redirect' => site_url('login/otp'),
            ];
        }

        $otpInput = trim($otpInput);
        if (! ctype_digit($otpInput) || strlen($otpInput) !== $this->otpLength) {
            log_message('debug', '[Auth][OtpLogin] OTP format invalid for mobileId={mobileId}', ['mobileId' => $mobileId]);
            return [
                'success'  => false,
                'error'    => 'Enter the six-digit OTP that was sent to your phone.',
                'redirect' => site_url('login/otp/verify'),
            ];
        }

        $result = $this->workflow->verify($mobileId, $otpInput);

        if (! $result['success']) {
            if (! empty($result['cleanup'])) {
                $this->sessionManager->clearOtpSession();
                log_message('notice', '[Auth][OtpLogin] OTP verification cleanup triggered mobileId={mobileId}', ['mobileId' => $mobileId]);
                return [
                    'success'  => false,
                    'error'    => $result['message'] ?? 'Your OTP session expired. Please request a new code.',
                    'redirect' => site_url('login/otp'),
                ];
            }
            log_message('notice', '[Auth][OtpLogin] OTP verification failed: {message}', [
                'message' => $result['message'] ?? 'unknown',
            ]);

            return [
                'success'  => false,
                'error'    => $result['message'] ?? 'Unable to verify the OTP.',
                'redirect' => site_url('login/otp/verify'),
            ];
        }

        if (isset($result['session'])) {
            $session->set($result['session']);
        }

        $user = $result['user'];
        $sessionUserId = $session->get('otp_login_user_id');

        if ($sessionUserId !== null && (int) $sessionUserId !== (int) $user['id']) {
            $this->sessionManager->clearOtpSession();
            log_message('warning', '[Auth][OtpLogin] OTP verification mismatch sessionUser={sessionUser} actualUser={actualUser}', [
                'sessionUser' => $sessionUserId,
                'actualUser'  => $user['id'] ?? null,
            ]);
            return [
                'success'  => false,
                'error'    => 'Account mismatch detected. Please request a new OTP.',
                'redirect' => site_url('login'),
            ];
        }

        $authTable = $user['auth_table'] ?? 'app_users';

        $handoff = $this->maybeDeferLogin($user, $authTable, 'otp');
        if ($handoff !== null) {
            $this->sessionManager->clearOtpSession();
            return $handoff;
        }

        $payload = $this->sessionManager->finalizeLogin(
            $user,
            $authTable,
            'otp',
            ['type' => 'success', 'message' => 'Signed in with mobile OTP.']
        );

        $this->sessionManager->clearOtpSession();
        log_message('info', '[Auth][OtpLogin] OTP login success user_id={id}', ['id' => $user['id'] ?? null]);

        return [
            'success'  => true,
            'redirect' => $payload['redirect'],
            'flash'    => $payload['flash'],
        ];
    }

    private function maybeDeferLogin(array $user, string $authTable, string $method): ?array
    {
        if ($authTable !== 'app_users') {
            return null;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $existing = $this->sessionRegistry->getActiveSession($userId, $authTable);
        if ($existing === null) {
            return null;
        }

        $session = session();
        $session->set('login_handoff', [
            'user'       => $this->sanitizeUserForHandoff($user),
            'auth_table' => $authTable,
            'method'     => $method,
            'username'   => $user['username'] ?? null,
        ]);
        $session->set('login_handoff_existing', $existing);

        log_message('info', '[Auth][OtpLogin] Login handoff required for user_id={id}', ['id' => $userId]);

        return [
            'success'  => false,
            'handoff'  => true,
            'redirect' => site_url('login/handoff'),
        ];
    }

    private function sanitizeUserForHandoff(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
