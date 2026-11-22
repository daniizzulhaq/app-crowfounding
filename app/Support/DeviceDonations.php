<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Remembers which donations were made from this browser (encrypted cookie),
 * so "Donasi Saya" shows them automatically without a phone number or OTP.
 */
final class DeviceDonations
{
    public const COOKIE = 'aka_donasi';

    private const MAX = 50;

    private const MINUTES = 60 * 24 * 365;

    public function __construct(private readonly Request $request) {}

    /** @return list<string> */
    public function orderIds(): array
    {
        $ids = json_decode((string) $this->request->cookie(self::COOKIE, '[]'), true);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(
            $ids,
            fn ($id) => is_string($id) && preg_match('/^[A-Z]{2,5}-\d{6}-[A-Z0-9]{8}$/', $id) === 1,
        ));
    }

    public function remember(string $orderId): void
    {
        $ids = array_slice(array_values(array_unique([$orderId, ...$this->orderIds()])), 0, self::MAX);

        Cookie::queue(Cookie::make(self::COOKIE, json_encode($ids), self::MINUTES, httpOnly: true, sameSite: 'lax'));
    }

    public function forget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
