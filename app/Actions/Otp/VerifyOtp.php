<?php

namespace App\Actions\Otp;

use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifyOtp
{
    public function handle(string $phone, string $code): bool
    {
        return DB::transaction(function () use ($phone, $code): bool {
            $otp = OtpCode::query()
                ->usableFor($phone)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null || $otp->attempts >= (int) config('alkiroma.otp.max_attempts')) {
                return false;
            }

            $otp->increment('attempts');

            if (! Hash::check($code, $otp->code_hash)) {
                return false;
            }

            $otp->forceFill(['consumed_at' => now()])->save();

            return true;
        });
    }
}
