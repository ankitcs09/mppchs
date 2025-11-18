<?php

namespace App\Events;

class ChangeRequestEvent
{
    public function __construct(
        public readonly string $type,
        public readonly int $requestId,
        public readonly int $beneficiaryId,
        public readonly ?int $actorId = null,
        public readonly ?array $context = null
    ) {
    }
}
