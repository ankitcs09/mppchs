<?php

namespace App\Config;

if (class_exists(__NAMESPACE__ . '\\AuthPolicy', false)) {
    return;
}

use CodeIgniter\Config\BaseConfig;

/**
 * Centralised authentication & password policy configuration.
 * Update values here (or override via environment) when requirements change.
 */
class AuthPolicy extends BaseConfig
{
    /**
     * Minimum password length.
     */
    public int $passwordMinLength = 10;

    /**
     * Number of character classes (uppercase, lowercase, digit, symbol) required.
     */
    public int $passwordClassesRequired = 4;

    /**
     * Number of previous passwords to keep in history and disallow reuse.
     */
    public int $passwordHistoryDepth = 3;

    /**
     * Whether to check against a blacklist of weak/common passwords.
     */
    public bool $blockCommonPasswords = true;

    /**
     * List of weak/common passwords to block. Extend as needed.
     *
     * @var string[]
     */
    public array $commonPasswordList = [
        'password',
        'password123',
        '123456',
        '123456789',
        'welcome',
        'admin123',
        'letmein',
        'qwerty',
        'iloveyou',
        'mpower123',
    ];

    /**
     * Password expiry in days. Null disables expiry enforcement.
     */
    public ?int $passwordExpiryDays = null;

    /**
     * Maximum number of self-service reset requests per day per user.
     */
    public int $passwordResetDailyLimit = 3;

    /**
     * Idle minutes before the session forces re-authentication. Null uses defaults.
     */
    public ?int $sessionIdleTimeoutMinutes = 30;

    /**
     * Total session lifetime in hours. Null uses defaults.
     */
    public ?int $sessionAbsoluteTimeoutHours = 24;
}
