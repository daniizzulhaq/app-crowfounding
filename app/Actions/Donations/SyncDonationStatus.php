<?php

namespace App\Actions\Donations;

use App\Models\Donation;
use App\Services\Payment\PaymentGateway;

/**
 * Admin "Cek status ke Midtrans": fetch the status server-to-server and run it
 * through the same verified, idempotent pipeline as the webhook.
 */
class SyncDonationStatus
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ApplyPaymentNotification $applyNotification,
    ) {}

    public function handle(Donation $donation): Donation
    {
        $payload = $this->gateway->fetchStatus($donation->order_id);

        // 404 from Midtrans = Snap opened but no payment chosen yet.
        if (($payload['status_code'] ?? null) === '404') {
            return $donation;
        }

        return $this->applyNotification->handle($payload);
    }
}
