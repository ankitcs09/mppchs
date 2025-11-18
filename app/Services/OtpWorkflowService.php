<?php

namespace App\Services;

use App\Models\OtpModel;
use CodeIgniter\I18n\Time;
use Config\Services;
use Throwable;

class OtpWorkflowService
{
    private OtpModel $otpModel;
    private int $otpLength;

    public function __construct(OtpModel $otpModel, int $otpLength = 6)
    {
        $this->otpModel = $otpModel;
        $this->otpLength = $otpLength;
    }

    public function initiate(string $rawMobile): array
    {
        $canonical = $this->canonicalMobile($rawMobile);
        if ($canonical === '') {
            return ['success' => false, 'message' => 'Enter a valid mobile number.'];
        }

        $user = $this->findUserByMobile($canonical);
        if ($user === null) {
            return ['success' => false, 'message' => 'We could not find an account for that mobile number.'];
        }

        $otpCode  = $this->generateOtpCode();
        $otpHash  = password_hash($otpCode, PASSWORD_DEFAULT);
        $now      = Time::now();
        $expires  = (clone $now)->addMinutes($this->getTtlMinutes());
        $mobileId = $this->canonicalStoreKey($canonical);

        $this->otpModel->purgeExpired();
        $this->otpModel->where('mobile', $mobileId)->delete();

        $this->otpModel->insert([
            'user_id'      => $user['id'],
            'mobile'       => $mobileId,
            'otp_hash'     => $otpHash,
            'expires_at'   => $expires->toDateTimeString(),
            'attempts'     => 0,
            'last_sent_at' => $now->toDateTimeString(),
            'resend_count' => 0,
        ]);

        $masked = $this->maskMobile($rawMobile);
        $deliveryStatus = $this->dispatchOtp($otpCode, $mobileId, $user['bname'] ?? $user['username'] ?? '');

        return [
            'success'          => true,
            'user'             => $user,
            'masked_mobile'    => $masked,
            'session'          => [
                'otp_login_mobile'        => $mobileId,
                'otp_login_mobile_masked' => $masked,
                'otp_login_user_id'       => $user['id'],
                'otp_last_sent'           => $now->getTimestamp(),
            ],
            'delivery_status' => $deliveryStatus,
            'debug_code'      => $this->shouldExposeOtp() ? $otpCode : null,
        ];
    }

    public function resend(string $mobileId): array
    {
        $record = $this->otpModel->where('mobile', $mobileId)->first();

        if ($record === null) {
            return ['success' => false, 'message' => 'OTP expired or already used. Please request a new code.', 'cleanup' => true];
        }

        $now             = Time::now();
        $cooldownSeconds = $this->getResendCooldownSeconds();
        $maxResends      = $this->getMaxResends();
        $lastSentAt      = isset($record['last_sent_at']) ? Time::parse($record['last_sent_at']) : null;
        $resendCount     = (int) ($record['resend_count'] ?? 0);
        $elapsed         = $lastSentAt !== null ? $now->getTimestamp() - $lastSentAt->getTimestamp() : $cooldownSeconds;

        if ($elapsed < $cooldownSeconds) {
            $wait = max(1, (int) ceil($cooldownSeconds - $elapsed));
            return ['success' => false, 'message' => 'Please wait ' . $wait . ' second(s) before requesting another OTP.', 'wait' => $wait];
        }

        if ($resendCount >= $maxResends) {
            $this->otpModel->delete($record['id']);
            return ['success' => false, 'message' => 'You have reached the maximum OTP resend attempts. Please start over.', 'cleanup' => true];
        }

        $user = $this->findUserByMobile($mobileId);
        if ($user === null) {
            $this->otpModel->delete($record['id']);
            return ['success' => false, 'message' => 'We could not locate the user profile tied to this mobile number.', 'cleanup' => true];
        }

        $otpCode = $this->generateOtpCode();
        $otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
        $expires = (clone $now)->addMinutes($this->getTtlMinutes());

        $this->otpModel->update($record['id'], [
            'otp_hash'     => $otpHash,
            'expires_at'   => $expires->toDateTimeString(),
            'attempts'     => 0,
            'last_sent_at' => $now->toDateTimeString(),
            'resend_count' => $resendCount + 1,
        ]);

        $deliveryStatus = $this->dispatchOtp($otpCode, $mobileId, $user['bname'] ?? $user['username'] ?? '');

        return [
            'success'         => true,
            'session'         => ['otp_last_sent' => $now->getTimestamp()],
            'delivery_status' => $deliveryStatus,
            'debug_code'      => $this->shouldExposeOtp() ? $otpCode : null,
        ];
    }

    public function verify(string $mobileId, string $otpInput): array
    {
        $record = $this->otpModel->where('mobile', $mobileId)->first();

        if ($record === null) {
            return ['success' => false, 'message' => 'OTP expired or already used. Please request a new code.', 'cleanup' => true];
        }

        $maxAttempts = $this->getMaxAttempts();

        if ((int) $record['attempts'] >= $maxAttempts) {
            $this->otpModel->delete($record['id']);
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new OTP.', 'cleanup' => true];
        }

        $now     = Time::now();
        $expires = Time::parse($record['expires_at']);
        $isValid = password_verify($otpInput, $record['otp_hash']);

        if ($now->isAfter($expires) || ! $isValid) {
            $this->otpModel->update($record['id'], [
                'attempts' => (int) $record['attempts'] + 1,
            ]);

            if ($now->isAfter($expires)) {
                $this->otpModel->delete($record['id']);
                return ['success' => false, 'message' => 'OTP expired. Please request a fresh code.', 'cleanup' => true];
            }

            $remaining = max(0, $maxAttempts - ((int) $record['attempts'] + 1));
            return ['success' => false, 'message' => 'Incorrect OTP. ' . $remaining . ' attempt(s) remaining.', 'remaining' => $remaining];
        }

        $this->otpModel->delete($record['id']);

        $user = $this->findUserByMobile($mobileId);
        if ($user === null) {
            return ['success' => false, 'message' => 'We could not locate the user profile tied to this mobile number.', 'cleanup' => true];
        }

        return ['success' => true, 'user' => $user];
    }

    private function dispatchOtp(string $otpCode, string $mobileId, string $recipientName = ''): bool
    {
        $authKey     = (string) env('OTP_MSG91_AUTH_KEY', '');
        $flowId      = (string) env('OTP_MSG91_FLOW_ID', '');
        $countryCode = (string) env('OTP_COUNTRY_CODE', '91');

        if ($authKey === '' || $flowId === '') {
            log_message('warning', 'MSG91 credentials missing; OTP not dispatched for mobile: {mobile}', [
                'mobile' => $mobileId,
            ]);

            return false;
        }

        $client = Services::curlrequest([
            'baseURI'     => 'https://control.msg91.com',
            'http_errors' => false,
        ]);

        $payload = [
            'flow_id'    => $flowId,
            'recipients' => [[
                'mobiles' => $countryCode . $mobileId,
                'var'     => $otpCode,
                'name'    => $recipientName,
            ]],
        ];

        try {
            $response = $client->post('/api/v5/flow/', [
                'headers' => [
                    'authkey'      => $authKey,
                    'Content-Type' => 'application/json',
                ],
                'json'    => $payload,
                'verify'  => true,
            ]);

            log_message(
                'info',
                'MSG91 OTP response for {mobile}: {status} {body}',
                [
                    'mobile' => $mobileId,
                    'status' => $response->getStatusCode(),
                    'body'   => (string) $response->getBody(),
                ]
            );

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Failed to dispatch OTP via MSG91 for {mobile}: {message}',
                [
                    'mobile'  => $mobileId,
                    'message' => $exception->getMessage(),
                ]
            );

            return false;
        }
    }

    private function canonicalMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile);
        return $digits ?? '';
    }

    private function canonicalStoreKey(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits);

        if ($digits === null) {
            return '';
        }

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    private function findUserByMobile(string $digits): ?array
    {
        $canonical = $this->canonicalMobile($digits);
        if ($canonical === '') {
            return null;
        }

        $candidates = $this->candidateMobiles($canonical);
        if ($candidates === []) {
            return null;
        }

        $user = $this->findAppUserByMobile($candidates);
        if ($user !== null) {
            return $user;
        }

        return $this->findLegacyUserByMobile($candidates);
    }

    private function candidateMobiles(string $digits): array
    {
        $digits = preg_replace('/\D+/', '', $digits);
        if ($digits === null || $digits === '') {
            return [];
        }

        $candidates = [$digits];

        if (strlen($digits) > 10) {
            $candidates[] = substr($digits, -10);
        } elseif (strlen($digits) === 10) {
            $candidates[] = '91' . $digits;
        }

        return array_values(array_unique($candidates));
    }

    private function findAppUserByMobile(array $candidates): ?array
    {
        if ($candidates === []) {
            return null;
        }

        $db = db_connect();
        $builder = $db->table('app_users u')
            ->select([
                'u.id',
                'u.username',
                'COALESCE(u.display_name, u.bname, u.username) AS bname',
                'u.password',
                'u.user_type',
                'u.status',
                'u.force_password_reset',
                'u.company_id',
                'u.beneficiary_v2_id',
                'bv2.legacy_beneficiary_id',
                'COALESCE(b.id, bv2.legacy_beneficiary_id, 0) AS beneficiary_id',
                "'app_users' AS auth_table",
            ])
            ->join('beneficiaries_v2 bv2', 'bv2.id = u.beneficiary_v2_id', 'left')
            ->join('beneficiaries b', 'b.id = bv2.legacy_beneficiary_id', 'left')
            ->where('u.user_type', 'beneficiary')
            ->where('u.status', 'active');

        $builder->groupStart();
        foreach ($candidates as $value) {
            $builder->orWhere($this->sanitizeMobileColumn('u.mobile'), $value);
            $builder->orWhere($this->sanitizeMobileColumn('b.mobile_number'), $value);
            $builder->orWhere($this->sanitizeMobileColumn('b.alternate_mobile'), $value);
        }

        $hashes = $this->hashCandidates($candidates);
        if ($hashes !== []) {
            $builder->orWhereIn('bv2.primary_mobile_hash', $hashes);
        }

        $builder->groupEnd();

        $record = $builder->get(1)->getRowArray();
        if ($record !== null) {
            return $record;
        }

        return null;
    }

    private function findLegacyUserByMobile(array $candidates): ?array
    {
        if ($candidates === []) {
            return null;
        }

        $db = db_connect();
        $builder = $db->table('tmusers u')
            ->select([
                'u.id',
                'u.username',
                'u.bname',
                'u.password',
                'b.id AS beneficiary_id',
                'bv2.id AS beneficiary_v2_id',
                'bv2.legacy_beneficiary_id',
                '0 AS company_id',
                '0 AS force_password_reset',
                "'beneficiary' AS user_type",
                "'active' AS status",
                "'tmusers' AS auth_table",
            ])
            ->join('beneficiaries b', 'b.id = u.beneficiary_id', 'left')
            ->join('beneficiaries_v2 bv2', 'bv2.legacy_beneficiary_id = b.id', 'left');

        $builder->groupStart();
        foreach ($candidates as $value) {
            $builder->orWhere($this->sanitizeMobileColumn('b.mobile_number'), $value);
            $builder->orWhere($this->sanitizeMobileColumn('b.alternate_mobile'), $value);
        }
        $builder->groupEnd();

        $record = $builder->get(1)->getRowArray();

        return $record ?: null;
    }

    private function sanitizeMobileColumn(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(IFNULL({$column}, ''), '+', ''), '-', ''), ' ', ''), '(', ''), ')', '')";
    }

    private function hashCandidates(array $candidates): array
    {
        $hashes = [];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $hashes[] = hash('sha256', $candidate);
        }

        return array_values(array_unique($hashes));
    }

    private function maskMobile(string $mobile): string
    {
        $digits = $this->canonicalMobile($mobile);
        if ($digits === '' || strlen($digits) < 4) {
            return '***';
        }

        $suffix = substr($digits, -4);
        return '******' . $suffix;
    }

    private function generateOtpCode(): string
    {
        $min = ($this->otpLength === 1) ? 0 : 10 ** ($this->otpLength - 1);
        $max = (10 ** $this->otpLength) - 1;

        return str_pad((string) random_int($min, $max), $this->otpLength, '0', STR_PAD_LEFT);
    }

    private function getTtlMinutes(): int
    {
        $value = env('OTP_TTL_MINUTES');
        return $value !== null ? (int) $value : 5;
    }

    private function getMaxAttempts(): int
    {
        $value = env('OTP_MAX_ATTEMPTS');
        return $value !== null ? (int) $value : 5;
    }

    private function getResendCooldownSeconds(): int
    {
        $seconds = env('OTP_RESEND_COOLDOWN_SECONDS');
        if ($seconds === null) {
            $seconds = env('OTP_RESEND_COOLDOWN');
        }

        return max(0, $seconds !== null ? (int) $seconds : 60);
    }

    private function getMaxResends(): int
    {
        $value = env('OTP_MAX_RESENDS');
        return max(0, $value !== null ? (int) $value : 3);
    }

    private function shouldExposeOtp(): bool
    {
        $override = env('OTP_EXPOSE_DEBUG');
        if ($override !== null) {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return ENVIRONMENT !== 'production';
    }
}
