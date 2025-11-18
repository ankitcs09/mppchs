<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RememberMe extends BaseConfig
{
    /**
     * Name of the persistent cookie.
     */
    public string $cookieName = 'mppgcl_remember';

    /**
     * Lifespan in days.
     */
    public int $lifetimeDays = 30;

    /**
     * Selector length (bytes before base64).
     */
    public int $selectorBytes = 9;

    /**
     * Validator length (bytes before hash).
     */
    public int $validatorBytes = 33;

    /**
     * Limit how many devices can stay remembered simultaneously.
     */
    public int $maxDevicesPerUser = 5;

    /**
     * Whether to only allow HTTPS.
     */
    public bool $secureOnly = false;

    /**
     * Should the cookie be accessible via JS? (No)
     */
    public bool $httpOnly = true;
}
