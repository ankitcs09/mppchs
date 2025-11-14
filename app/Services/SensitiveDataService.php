<?php

namespace App\Services;

use CodeIgniter\Encryption\EncrypterInterface;
use Config\Services;
use RuntimeException;

class SensitiveDataService
{
    private EncrypterInterface $encrypter;

    public function __construct(?EncrypterInterface $encrypter = null)
    {
        $this->encrypter = $encrypter ?? Services::encrypter();

        if (empty($this->encrypter->encrypt('test'))) {
            throw new RuntimeException('Encryption service is not properly configured.');
        }
    }

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cipher = $this->encrypter->encrypt($value);
        return base64_encode($cipher);
    }

    public function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid encrypted payload.');
        }

        return $this->encrypter->decrypt($decoded);
    }
}
