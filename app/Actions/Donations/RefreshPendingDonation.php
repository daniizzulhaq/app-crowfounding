<?php

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback when the webhook is late or cannot reach us (e.g. localhost):
 * ask Midtrans server-to-server for a pending donation's status, throttled.
 * Goes through the same signature-verified, idempotent pipeline as the webhook.
 */
class RefreshPendingDonation
{
    public const THROTTLE_SECONDS = 20;

    public function __construct(private readonly SyncDonationStatus $sync) {}

    public function handle(Donation $donation): Donation
    {
        if ($donation->status !== DonationStatus::Pending) {
            return $donation;
        }

        // At most one Midtrans call per donation per window, whoever triggers it.
        if (! Cache::add('donation-sync:'.$donation->id, true, self::THROTTLE_SECONDS)) {
            return $donation;
        }

        try {
            return $this->sync->handle($donation);
        } catch (Throwable $e) {
            Log::warning('Cek status Midtrans gagal', ['order_id' => $donation->order_id, 'error' => $e->getMessage()]);

            return $donation;
        }
    }
}
