<?php

namespace App\Http\Controllers\Api;

use App\Actions\Otp\RequestOtp;
use App\Actions\Otp\VerifyOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpRequest;
use App\Models\Donation;
use App\Models\MobileDonorToken;
use App\Rules\IndonesianPhone;
use App\Support\MobileData;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DonorController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, RequestOtp $otp)
    {
        $otp->handle($request->phone());

        return response()->json(['message' => 'Kode verifikasi dikirim ke WhatsApp '.PhoneNumber::mask($request->phone()).'.',
            'data' => ['masked_phone' => PhoneNumber::mask($request->phone()), 'expires_in' => (int) config('alkiroma.otp.ttl_minutes') * 60]])
            ->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request, VerifyOtp $otp)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20', new IndonesianPhone],
            'code' => ['required', 'string', 'digits:'.config('alkiroma.otp.length')]]);
        $phone = PhoneNumber::normalize($data['phone']);
        $plain = bin2hex(random_bytes(32));
        $expires = now()->addMinutes((int) config('alkiroma.otp.session_minutes'));
        DB::transaction(function () use ($otp, $phone, $data, $plain, $expires) {
            if (! $otp->handle($phone, $data['code'])) {
                // Do not throw inside the transaction: failed attempts must remain recorded.
                return;
            }
            MobileDonorToken::query()->where('expires_at', '<=', now())->delete();
            MobileDonorToken::create(['phone' => $phone, 'token_hash' => hash('sha256', $plain), 'expires_at' => $expires]);
        });
        if (! MobileDonorToken::query()->where('token_hash', hash('sha256', $plain))->exists()) {
            throw ValidationException::withMessages(['code' => 'Kode salah atau sudah kedaluwarsa. Minta kode baru bila perlu.']);
        }

        return response()->json(['data' => ['token' => $plain, 'token_type' => 'Bearer',
            'expires_at' => $expires->toIso8601String(), 'masked_phone' => PhoneNumber::mask($phone)]])
            ->header('Cache-Control', 'no-store');
    }

    public function me(Request $request)
    {
        $token = $request->attributes->get('mobile_donor');

        return response()->json(['data' => ['masked_phone' => PhoneNumber::mask($token->phone),
            'expires_at' => $token->expires_at->toIso8601String(),
            'total_paid' => (int) Donation::query()->forPhone($token->phone)->paid()->sum('amount')]])
            ->header('Cache-Control', 'no-store');
    }

    public function donations(Request $request)
    {
        return response()->json(MobileData::page(Donation::query()->forPhone($request->attributes->get('mobile_donor')->phone)
            ->with('campaign:id,title,slug')->latest('id')->paginate(15), fn ($donation) => MobileData::receipt($donation)))
            ->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        $request->attributes->get('mobile_donor')->delete();

        return response()->json(['message' => 'Anda sudah keluar.'])->header('Cache-Control', 'no-store');
    }
}
