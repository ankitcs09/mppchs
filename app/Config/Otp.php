<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Otp extends BaseConfig
{
    /**
     * MSG91 authentication key used for the Flow API.
     * Override in `.env` with OTP_MSG91_AUTH_KEY.
     */
    public string $msg91AuthKey = '';

    /**
     * MSG91 Flow ID configured for OTP delivery.
     * Override in `.env` with OTP_MSG91_FLOW_ID.
     */
    public string $msg91FlowId = '';

    /**
     * Default country code prefix (without the + symbol).
     * Override in `.env` with OTP_COUNTRY_CODE.
     */
    public string $countryCode = '91';

    /**
     * Minutes before an OTP expires.
     * Override in `.env` with OTP_TTL_MINUTES.
     */
    public int $ttlMinutes = 5;

    /**
     * Maximum number of verification attempts allowed per OTP.
     * Override in `.env` with OTP_MAX_ATTEMPTS.
     */
    public int $maxAttempts = 5;

    /**
     * During non-production we optionally expose the generated OTP
     * back to the session so QA can test without SMS delivery.
     * Override in `.env` with OTP_EXPOSE_DEBUG (true|false).
     */
    public bool $exposeOtpInDebug = (ENVIRONMENT !== 'production');

    /**
     * Minimum seconds between OTP sends to the same user.
     * Override in `.env` with OTP_RESEND_COOLDOWN_SECONDS.
     */
    public int $resendCooldownSeconds = 60;

    /**
     * Maximum number of OTP resends allowed before forcing a fresh login.
     * Override in `.env` with OTP_MAX_RESENDS.
     */
    public int $maxResends = 3;
}
