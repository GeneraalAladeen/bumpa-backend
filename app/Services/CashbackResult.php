<?php

namespace App\Services;

class CashbackResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerReference = null,
        public readonly ?string $failureReason = null,
    ) {
    }
}
