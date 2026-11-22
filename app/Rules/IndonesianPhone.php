<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IndonesianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (PhoneNumber::normalize(is_string($value) ? $value : null) === null) {
            $fail('Nomor WhatsApp tidak valid. Contoh: 0812 3456 7890.');
        }
    }
}
