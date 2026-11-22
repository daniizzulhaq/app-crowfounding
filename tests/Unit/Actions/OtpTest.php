<?php

use App\Actions\Otp\RequestOtp;
use App\Actions\Otp\VerifyOtp;
use App\Jobs\SendWhatsAppMessage;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Queue;

function sentOtpCode(): string
{
    $code = null;
    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use (&$code) {
        preg_match('/\*(\d{6})\*/', $job->message, $m);
        $code = $m[1] ?? null;

        return true;
    });

    return (string) $code;
}

beforeEach(fn () => Queue::fake());

it('sends a hashed 6-digit code over WhatsApp', function () {
    app(RequestOtp::class)->handle('6281234567890');

    $code = sentOtpCode();
    $otp = OtpCode::first();

    expect($code)->toMatch('/^\d{6}$/')
        ->and($otp->code_hash)->not->toBe($code)
        ->and(app(VerifyOtp::class)->handle('6281234567890', $code))->toBeTrue()
        ->and($otp->fresh()->consumed_at)->not->toBeNull();
});

it('cannot reuse a consumed code', function () {
    app(RequestOtp::class)->handle('6281234567890');
    $code = sentOtpCode();

    app(VerifyOtp::class)->handle('6281234567890', $code);

    expect(app(VerifyOtp::class)->handle('6281234567890', $code))->toBeFalse();
});

it('locks the code after too many wrong attempts', function () {
    app(RequestOtp::class)->handle('6281234567890');
    $code = sentOtpCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $i) {
        app(VerifyOtp::class)->handle('6281234567890', $wrong);
    }

    expect(app(VerifyOtp::class)->handle('6281234567890', $code))->toBeFalse();
});

it('rejects expired codes', function () {
    app(RequestOtp::class)->handle('6281234567890');
    $code = sentOtpCode();

    $this->travel(6)->minutes();

    expect(app(VerifyOtp::class)->handle('6281234567890', $code))->toBeFalse();
});

it('invalidates the previous code when a new one is requested', function () {
    app(RequestOtp::class)->handle('6281234567890');
    expect(OtpCode::query()->usableFor('6281234567890')->count())->toBe(1);

    app(RequestOtp::class)->handle('6281234567890');
    expect(OtpCode::query()->usableFor('6281234567890')->count())->toBe(1);
});
