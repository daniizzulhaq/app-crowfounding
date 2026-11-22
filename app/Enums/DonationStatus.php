<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DonationStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Expired => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Map Midtrans transaction_status (+ fraud_status) to our status.
     */
    public static function fromMidtrans(string $transactionStatus, ?string $fraudStatus = null): self
    {
        return match ($transactionStatus) {
            'settlement' => self::Paid,
            'capture' => in_array($fraudStatus, [null, 'accept'], true) ? self::Paid : self::Pending,
            'pending', 'authorize' => self::Pending,
            'expire' => self::Expired,
            default => self::Failed, // deny, cancel, failure, refund, chargeback...
        };
    }
}
