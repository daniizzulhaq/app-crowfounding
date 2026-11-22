<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * Remembers which WhatsApp number a guest donor verified via OTP.
 */
final class DonorSession
{
    private const KEY = 'donor.phone';

    private const UNTIL = 'donor.until';

    public function __construct(private readonly Session $session) {}

    public function grant(string $phone): void
    {
        $this->session->migrate(true);
        $this->session->put(self::KEY, $phone);
        $this->session->put(self::UNTIL, now()->addMinutes((int) config('alkiroma.otp.session_minutes'))->getTimestamp());
    }

    public function phone(): ?string
    {
        $phone = $this->session->get(self::KEY);
        $until = (int) $this->session->get(self::UNTIL, 0);

        if (! is_string($phone) || $until < now()->getTimestamp()) {
            return null;
        }

        return $phone;
    }

    public function forget(): void
    {
        $this->session->forget([self::KEY, self::UNTIL]);
    }
}
