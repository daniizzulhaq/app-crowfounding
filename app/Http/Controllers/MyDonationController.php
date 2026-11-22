<?php

namespace App\Http\Controllers;

use App\Actions\Otp\RequestOtp;
use App\Actions\Otp\VerifyOtp;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Donation;
use App\Support\DeviceDonations;
use App\Support\DonorSession;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyDonationController extends Controller
{
    /**
     * 1) Verified WhatsApp number (OTP) → all donations of that number.
     * 2) Otherwise → donations made from this device (encrypted cookie), no login needed.
     * 3) Nothing on this device → explain & offer OTP for other devices.
     */
    public function index(DonorSession $donor, DeviceDonations $device): View
    {
        $phone = $donor->phone();

        $scope = $phone !== null
            ? fn ($query) => $query->forPhone($phone)
            : fn ($query) => $query->whereIn('order_id', $device->orderIds());

        if ($phone === null && $device->orderIds() === []) {
            return view('my-donations.login');
        }

        $donations = Donation::query()
            ->tap($scope)
            ->with('campaign:id,title,slug')
            ->latest()
            ->paginate(15);

        if ($phone === null && $donations->isEmpty()) {
            return view('my-donations.login');
        }

        return view('my-donations.index', [
            'donations' => $donations,
            'maskedPhone' => $phone ? PhoneNumber::mask($phone) : null,
            'totalPaid' => (int) Donation::query()->tap($scope)->paid()->sum('amount'),
        ]);
    }

    public function requestOtp(RequestOtpRequest $request, RequestOtp $requestOtp): RedirectResponse
    {
        $phone = $request->phone();

        // Same response whether or not the number has donations (no enumeration).
        $requestOtp->handle($phone);

        $request->session()->put('otp.phone', $phone);

        return redirect()->route('my-donations.verify')
            ->with('status', 'Kode verifikasi sudah dikirim ke WhatsApp '.PhoneNumber::mask($phone).'.');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('otp.phone');

        if (! is_string($phone)) {
            return redirect()->route('my-donations.phone');
        }

        return view('my-donations.verify', ['maskedPhone' => PhoneNumber::mask($phone)]);
    }

    public function verify(VerifyOtpRequest $request, VerifyOtp $verifyOtp, DonorSession $donor): RedirectResponse
    {
        $phone = $request->session()->get('otp.phone');

        if (! is_string($phone)) {
            return redirect()->route('my-donations.phone');
        }

        if (! $verifyOtp->handle($phone, $request->validated('code'))) {
            return back()->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa. Minta kode baru bila perlu.']);
        }

        $request->session()->forget('otp.phone');
        $donor->grant($phone);

        return redirect()->route('my-donations.index');
    }

    public function logout(DonorSession $donor): RedirectResponse
    {
        $donor->forget();

        return redirect()->route('my-donations.index')->with('status', 'Anda sudah keluar dari riwayat donasi.');
    }
}
