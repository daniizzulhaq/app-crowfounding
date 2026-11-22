<?php

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Campaign;
use App\Models\Donation;
use App\Queries\HomePageData;
use App\Services\Payment\PaymentGateway;
use App\Services\Settings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The ONLY place where a donation becomes "paid" and campaign totals grow.
 * Idempotent: duplicate notifications never add money twice.
 */
class ApplyPaymentNotification
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly Settings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  Midtrans notification / status payload.
     *
     * @throws InvalidSignatureException
     * @throws ModelNotFoundException
     */
    public function handle(array $payload): Donation
    {
        if (! $this->gateway->hasValidSignature($payload)) {
            throw new InvalidSignatureException('Signature Midtrans tidak valid.');
        }

        $newlyPaid = false;

        $donation = DB::transaction(function () use ($payload, &$newlyPaid): Donation {
            /** @var Donation $donation */
            $donation = Donation::query()
                ->where('order_id', $payload['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Already paid: never touch money again (duplicate / late notification).
            if ($donation->status === DonationStatus::Paid) {
                return $donation;
            }

            $incoming = DonationStatus::fromMidtrans(
                (string) ($payload['transaction_status'] ?? ''),
                isset($payload['fraud_status']) ? (string) $payload['fraud_status'] : null,
            );

            $donation->forceFill([
                'payment_method' => PaymentMethod::fromMidtrans($payload['payment_type'] ?? null) ?? $donation->payment_method,
                'midtrans_payload' => $payload,
            ]);

            if ($incoming === DonationStatus::Paid) {
                if ($this->grossAmount($payload) !== $donation->amount) {
                    Log::warning('Midtrans gross_amount mismatch', [
                        'order_id' => $donation->order_id,
                        'expected' => $donation->amount,
                        'got' => $payload['gross_amount'] ?? null,
                    ]);
                    $donation->save();

                    return $donation;
                }

                // A late settlement after local expiry is still real money: accept it.
                $donation->forceFill([
                    'status' => DonationStatus::Paid,
                    'paid_at' => $this->paidAt($payload),
                ])->save();

                if ($donation->campaign_id !== null) {
                    Campaign::query()->whereKey($donation->campaign_id)->lockForUpdate()->first();
                    Campaign::query()->whereKey($donation->campaign_id)->incrementEach([
                        'collected_amount' => $donation->amount,
                        'donor_count' => 1,
                    ]);
                }

                $newlyPaid = true;

                return $donation;
            }

            // Only a pending donation may move to failed/expired; never downgrade.
            if ($donation->status === DonationStatus::Pending && $incoming !== DonationStatus::Pending) {
                $donation->status = $incoming;
            }

            $donation->save();

            return $donation;
        });

        if ($newlyPaid) {
            HomePageData::forget();
            $this->sendThankYou($donation);
        }

        return $donation;
    }

    private function grossAmount(array $payload): int
    {
        $gross = (string) ($payload['gross_amount'] ?? '0');

        // "25000.00" -> 25000 without touching floats.
        return (int) explode('.', $gross, 2)[0];
    }

    private function paidAt(array $payload): Carbon
    {
        $time = $payload['settlement_time'] ?? $payload['transaction_time'] ?? null;

        try {
            return $time ? Carbon::parse($time, 'Asia/Jakarta') : now();
        } catch (Throwable) {
            return now();
        }
    }

    private function sendThankYou(Donation $donation): void
    {
        $donation->loadMissing('campaign:id,title,slug');

        $message = sprintf(
            "Assalamu'alaikum %s,\n\nTerima kasih, donasi %s untuk \"%s\" sudah kami terima (No. %s).\nSemoga Allah membalas kebaikan Anda dengan berlipat ganda.\n\nSalam hangat,\n%s",
            $donation->is_anonymous ? 'Sahabat' : $donation->donor_name,
            rupiah($donation->amount),
            $donation->target_label,
            $donation->order_id,
            $this->settings->foundationName(),
        );

        SendWhatsAppMessage::dispatch($donation->phone, $message);
    }
}
