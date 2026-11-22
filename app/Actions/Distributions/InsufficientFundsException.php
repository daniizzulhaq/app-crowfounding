<?php

namespace App\Actions\Distributions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public function __construct(public readonly int $available)
    {
        parent::__construct('Nominal melebihi saldo yang tersedia ('.rupiah($available).').');
    }
}
