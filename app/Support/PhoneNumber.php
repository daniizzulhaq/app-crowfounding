<?php

namespace App\Support;

final class PhoneNumber
{
    /**
     * Normalize an Indonesian mobile number to "62xxx". Returns null if invalid.
     * Accepts: 0812..., 812..., +62812..., 62812..., with spaces/dashes.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if ($digits === '') {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '0') => '62'.substr($digits, 1),
            str_starts_with($digits, '8') => '62'.$digits,
            default => $digits,
        };

        return preg_match('/^628\d{7,12}$/', $digits) === 1 ? $digits : null;
    }

    /**
     * Masked local format: 6281234567890 => "0812*****7890".
     */
    public static function mask(string $normalized): string
    {
        $local = '0'.substr($normalized, 2);

        return substr($local, 0, 4).str_repeat('*', max(strlen($local) - 8, 2)).substr($local, -4);
    }
}
