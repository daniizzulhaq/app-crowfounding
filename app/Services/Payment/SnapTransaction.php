<?php

namespace App\Services\Payment;

final readonly class SnapTransaction
{
    public function __construct(
        public string $token,
        public string $redirectUrl,
    ) {}
}
