<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BankTransfer = 'bank_transfer';
    case Mandiri = 'echannel';
    case Qris = 'qris';
    case Gopay = 'gopay';
    case ShopeePay = 'shopeepay';
    case CreditCard = 'credit_card';
    case Retail = 'cstore';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transfer Bank (VA)',
            self::Mandiri => 'Mandiri Bill',
            self::Qris => 'QRIS',
            self::Gopay => 'GoPay',
            self::ShopeePay => 'ShopeePay',
            self::CreditCard => 'Kartu Kredit',
            self::Retail => 'Gerai Retail',
            self::Other => 'Lainnya',
        };
    }

    public static function fromMidtrans(?string $paymentType): ?self
    {
        if ($paymentType === null || $paymentType === '') {
            return null;
        }

        return self::tryFrom($paymentType) ?? self::Other;
    }
}
