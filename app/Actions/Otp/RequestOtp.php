<?php

namespace App\Actions\Otp;

use App\Jobs\SendWhatsAppMessage;
use App\Models\OtpCode;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RequestOtp
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * @param  string  $phone  Normalized 62xxx number.
     */
    public function handle(string $phone): void
    {
        $length = (int) config('alkiroma.otp.length');
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($phone, $code) {
            // Invalidate older codes for this number.
            OtpCode::query()->usableFor($phone)->update(['consumed_at' => now()]);

            OtpCode::create([
                'phone' => $phone,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('alkiroma.otp.ttl_minutes')),
            ]);
        });

        SendWhatsAppMessage::dispatch($phone, sprintf(
            "Kode verifikasi %s: *%s*\nBerlaku %d menit. Jangan berikan kode ini kepada siapa pun.",
            $this->settings->foundationName(),
            $code,
            (int) config('alkiroma.otp.ttl_minutes'),
        ));
    }
}
