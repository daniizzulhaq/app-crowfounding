<?php

namespace App\Actions\Donations;

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\PaymentGatewayException;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateDonation
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Create a pending donation and request a Snap token.
     * Money is NOT added to the campaign here — only the verified webhook does that.
     *
     * @param  array{donor_name: ?string, is_anonymous: bool, phone: string, amount: int, prayer: ?string}  $data
     *
     * @throws PaymentGatewayException
     */
    public function handle(array $data, ?Campaign $campaign = null): Donation
    {
        if ($campaign !== null && ! $campaign->acceptsDonations()) {
            throw new InvalidArgumentException('Campaign tidak menerima donasi.');
        }

        $phone = PhoneNumber::normalize($data['phone']) ?? throw new InvalidArgumentException('Nomor WhatsApp tidak valid.');
        $isAnonymous = (bool) $data['is_anonymous'];
        $name = trim((string) ($data['donor_name'] ?? ''));

        $donation = Donation::create([
            'campaign_id' => $campaign?->id,
            'order_id' => $this->generateOrderId(),
            // Real name is always stored for admin; public sees "Hamba Allah" when anonymous.
            'donor_name' => $name !== '' ? $name : Donation::ANONYMOUS_NAME,
            'is_anonymous' => $isAnonymous || $name === '',
            'phone' => $phone,
            'amount' => (int) $data['amount'],
            'prayer' => filled($data['prayer'] ?? null) ? trim($data['prayer']) : null,
            'expires_at' => now()->addMinutes((int) config('alkiroma.donation.expiry_minutes')),
        ]);

        $donation->setRelation('campaign', $campaign);

        try {
            $snap = $this->gateway->createTransaction($donation);
        } catch (PaymentGatewayException $e) {
            $donation->delete();

            throw $e;
        }

        $donation->forceFill(['snap_token' => $snap->token])->save();

        return $donation;
    }

    public function generateOrderId(): string
    {
        do {
            $orderId = sprintf(
                '%s-%s-%s',
                config('alkiroma.order_prefix'),
                now()->format('ymd'),
                Str::upper(Str::random(8)),
            );
        } while (Donation::query()->where('order_id', $orderId)->exists());

        return $orderId;
    }
}
