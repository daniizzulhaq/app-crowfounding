<?php

namespace App\Http\Controllers;

use App\Actions\Donations\CreateDonation;
use App\Actions\Donations\RefreshPendingDonation;
use App\Http\Requests\StoreDonationRequest;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payment\PaymentGatewayException;
use App\Support\DeviceDonations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DonationController extends Controller
{
    /** Form for a campaign (/campaign/{slug}/donasi) or the general fund (/donasi). */
    public function create(Request $request, ?Campaign $campaign = null): View|RedirectResponse
    {
        if ($campaign !== null) {
            abort_unless($campaign->isPubliclyVisible(), 404);

            if (! $campaign->acceptsDonations()) {
                return redirect()->route('campaigns.show', $campaign)
                    ->with('notice', 'Campaign ini sudah tidak menerima donasi.');
            }

            $campaign->load('media');
        }

        $initial = (int) $request->integer('nominal');
        $min = (int) config('alkiroma.donation.min_amount');

        return view('donations.create', [
            'campaign' => $campaign,
            'initial' => $initial >= $min ? min($initial, (int) config('alkiroma.donation.max_amount')) : 0,
            'presets' => config('alkiroma.donation.form_amounts'),
        ]);
    }

    public function store(StoreDonationRequest $request, CreateDonation $createDonation): JsonResponse|RedirectResponse
    {
        $campaign = $request->validated('campaign')
            ? Campaign::query()->where('slug', $request->validated('campaign'))->firstOrFail()
            : null;

        if ($campaign !== null && (! $campaign->isPubliclyVisible() || ! $campaign->acceptsDonations())) {
            return $this->fail($request, 'Campaign ini sudah tidak menerima donasi.', 422);
        }

        try {
            $donation = $createDonation->handle($request->donationData(), $campaign);
        } catch (PaymentGatewayException $e) {
            Log::error('Snap token gagal dibuat', ['error' => $e->getMessage()]);

            return $this->fail($request, 'Layanan pembayaran sedang sibuk. Coba lagi beberapa saat.', 503);
        }

        // "Donasi Saya" on this device works automatically, no phone/OTP needed.
        app(DeviceDonations::class)->remember($donation->order_id);

        $redirectUrl = $this->snapRedirectUrl($donation->snap_token);
        $statusUrl = route('donations.status', $donation->order_id);

        if (! $request->expectsJson()) {
            return redirect()->away($redirectUrl);
        }

        return response()->json([
            'order_id' => $donation->order_id,
            'snap_token' => $donation->snap_token,
            'redirect_url' => $redirectUrl,
            'status_url' => $statusUrl,
        ], 201);
    }

    /**
     * Status comes from our database, updated by the verified webhook or by a
     * throttled server-to-server check with Midtrans. The browser redirect is never trusted.
     */
    public function status(string $orderId, RefreshPendingDonation $refresh): View
    {
        $donation = $refresh->handle($this->findByOrderId($orderId));

        return view('donations.status', ['donation' => $donation->load('campaign:id,title,slug')]);
    }

    public function statusJson(string $orderId, RefreshPendingDonation $refresh): JsonResponse
    {
        $donation = $refresh->handle($this->findByOrderId($orderId));

        return response()->json(['status' => $donation->status->value])
            ->header('Cache-Control', 'no-store');
    }

    private function findByOrderId(string $orderId): Donation
    {
        abort_unless(preg_match('/^[A-Z]{2,5}-\d{6}-[A-Z0-9]{8}$/', $orderId) === 1, 404);

        return Donation::query()
            ->with(['campaign:id,title,slug'])
            ->where('order_id', $orderId)
            ->firstOrFail();
    }

    private function snapRedirectUrl(string $token): string
    {
        $base = config('midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';

        return $base.'/snap/v4/redirection/'.$token;
    }

    private function fail(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->withInput()->with('error', $message);
    }
}
