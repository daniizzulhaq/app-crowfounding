<?php

use App\Enums\DonationStatus;
use App\Support\Html;
use App\Support\PhoneNumber;
use App\Support\Rupiah;

describe('PhoneNumber::normalize', function () {
    it('normalizes Indonesian mobile formats to 62xxx', function (string $input) {
        expect(PhoneNumber::normalize($input))->toBe('6281234567890');
    })->with([
        '0812-3456-7890',
        '081234567890',
        '81234567890',
        '+62 812 3456 7890',
        '6281234567890',
    ]);

    it('rejects invalid numbers', function (?string $input) {
        expect(PhoneNumber::normalize($input))->toBeNull();
    })->with([null, '', 'abc', '021555123', '0812', '+1 202 555 0101']);

    it('masks numbers for display', function () {
        expect(PhoneNumber::mask('6281234567890'))->toBe('0812****7890');
    });
});

describe('Rupiah', function () {
    it('formats integer rupiah with dot thousands separator', function () {
        expect(Rupiah::format(25000))->toBe('Rp25.000')
            ->and(Rupiah::format(0))->toBe('Rp0')
            ->and(Rupiah::format(1250000))->toBe('Rp1.250.000')
            ->and(rupiah(10000))->toBe('Rp10.000');
    });

    it('formats short amounts without floats', function () {
        expect(Rupiah::short(10_000))->toBe('Rp10 rb')
            ->and(Rupiah::short(25_000))->toBe('Rp25 rb')
            ->and(Rupiah::short(1_250_000))->toBe('Rp1,25 jt')
            ->and(Rupiah::short(2_000_000_000))->toBe('Rp2 M')
            ->and(Rupiah::short(500))->toBe('Rp500');
    });
});

describe('DonationStatus::fromMidtrans', function () {
    it('maps transaction statuses', function (string $status, ?string $fraud, DonationStatus $expected) {
        expect(DonationStatus::fromMidtrans($status, $fraud))->toBe($expected);
    })->with([
        ['settlement', null, DonationStatus::Paid],
        ['capture', 'accept', DonationStatus::Paid],
        ['capture', 'challenge', DonationStatus::Pending],
        ['pending', null, DonationStatus::Pending],
        ['expire', null, DonationStatus::Expired],
        ['deny', null, DonationStatus::Failed],
        ['cancel', null, DonationStatus::Failed],
        ['failure', null, DonationStatus::Failed],
    ]);
});

describe('Html::clean', function () {
    it('strips scripts, event handlers and javascript urls', function () {
        $dirty = '<p onclick="x()">Hi</p><script>alert(1)</script><a href="javascript:alert(1)">x</a><img src="a.jpg" onerror="y()">';
        $clean = Html::clean($dirty);

        expect($clean)
            ->not->toContain('<script')
            ->not->toContain('onclick')
            ->not->toContain('onerror')
            ->not->toContain('javascript:')
            ->toContain('<p>Hi</p>');
    });
});
