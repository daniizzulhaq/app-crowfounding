<?php

namespace App\Http\Controllers\Api;

use App\Actions\Donations\CreateDonation;
use App\Actions\Donations\RefreshPendingDonation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDonationRequest;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payment\PaymentGatewayException;
use App\Support\MobileData;
use Illuminate\Support\Facades\Log;

class DonationController extends Controller
{
    public function store(StoreDonationRequest $request, CreateDonation $create)
    {
        $campaign = $request->validated('campaign')
            ? Campaign::query()->where('slug', $request->validated('campaign'))->firstOrFail() : null;
        abort_if($campaign && (! $campaign->isPubliclyVisible() || ! $campaign->acceptsDonations()), 422, 'Campaign ini sudah tidak menerima donasi.');
        try {
            $donation = $create->handle($request->donationData(), $campaign);
        } catch (PaymentGatewayException $e) {
            Log::warning('Pembayaran mobile gagal dibuat', ['exception' => $e::class]);

            return response()->json(['message' => 'Layanan pembayaran sedang sibuk. Coba lagi beberapa saat.'], 503);
        }

        return response()->json(['data' => MobileData::receipt($donation)], 201)->header('Cache-Control', 'no-store');
    }

    public function show(string $orderId, RefreshPendingDonation $refresh)
    {
        $donation = Donation::query()->with('campaign:id,title,slug')->where('order_id', $orderId)->firstOrFail();

        return response()->json(['data' => MobileData::receipt($refresh->handle($donation)->loadMissing('campaign:id,title,slug'))])
            ->header('Cache-Control', 'no-store');
    }
}
