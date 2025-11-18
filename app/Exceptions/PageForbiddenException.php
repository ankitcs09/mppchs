<?php

namespace App\Exceptions;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use RuntimeException;

class PageForbiddenException extends RuntimeException implements HTTPExceptionInterface
{
    protected $code = 403;

    public static function forPageForbidden(string $message = 'Forbidden.'): self
    {
        return new static($message, 403);
    }

    public function getStatusCode(): int
    {
        return 403;
    }

    public function getHeaders(): array
    {
        return [];
    }
}

