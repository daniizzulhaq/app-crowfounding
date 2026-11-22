<?php

namespace App\Support;

final class Rupiah
{
    /**
     * Format integer rupiah: 25000 => "Rp25.000".
     */
    public static function format(int $amount): string
    {
        $prefix = $amount < 0 ? '-Rp' : 'Rp';

        return $prefix.number_format(abs($amount), 0, ',', '.');
    }

    /**
     * Short form for tight UI: 1250000 => "Rp1,25 jt".
     */
    public static function short(int $amount): string
    {
        return match (true) {
            $amount >= 1_000_000_000 => 'Rp'.self::decimal($amount, 1_000_000_000).' M',
            $amount >= 1_000_000 => 'Rp'.self::decimal($amount, 1_000_000).' jt',
            $amount >= 1_000 => 'Rp'.self::decimal($amount, 1_000).' rb',
            default => self::format($amount),
        };
    }

    /**
     * Integer-only division to max 2 decimals, Indonesian separator, no trailing zeros.
     */
    private static function decimal(int $amount, int $unit): string
    {
        $whole = intdiv($amount, $unit);
        $fraction = intdiv(($amount % $unit) * 100, $unit);

        if ($fraction === 0) {
            return (string) $whole;
        }

        return $whole.','.rtrim(str_pad((string) $fraction, 2, '0', STR_PAD_LEFT), '0');
    }
}
