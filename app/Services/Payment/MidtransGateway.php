<?php

namespace App\Services\Payment;

use App\Models\Donation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MidtransGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $serverKey,
        private readonly string $snapUrl,
        private readonly string $apiUrl,
        private readonly int $expiryMinutes,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            serverKey: (string) config('midtrans.server_key'),
            snapUrl: (string) config('midtrans.snap_url'),
            apiUrl: (string) config('midtrans.api_url'),
            expiryMinutes: (int) config('alkiroma.donation.expiry_minutes'),
        );
    }

    public function createTransaction(Donation $donation): SnapTransaction
    {
        $donation->loadMissing('campaign:id,title,slug');

        $payload = [
            'transaction_details' => [
                'order_id' => $donation->order_id,
                'gross_amount' => $donation->amount,
            ],
            'item_details' => [[
                'id' => $donation->campaign?->slug ? Str::limit($donation->campaign->slug, 50, '') : 'sedekah-umum',
                'price' => $donation->amount,
                'quantity' => 1,
                'name' => Str::limit($donation->campaign?->title ?? 'Sedekah umum Alkiroma Amanah', 48, ''),
            ]],
            'customer_details' => [
                'first_name' => Str::limit($donation->donor_name, 50, ''),
                'phone' => $donation->phone,
            ],
            'expiry' => [
                'start_time' => $donation->created_at->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => $this->expiryMinutes,
            ],
            'callbacks' => [
                'finish' => route('donations.status', $donation->order_id),
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->timeout(15)
                ->post($this->snapUrl, $payload);
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('Tidak dapat terhubung ke Midtrans.', previous: $e);
        }

        if ($response->failed() || ! $response->json('token')) {
            throw new PaymentGatewayException('Midtrans menolak transaksi: '.$response->body());
        }

        return new SnapTransaction(
            token: (string) $response->json('token'),
            redirectUrl: (string) $response->json('redirect_url'),
        );
    }

    public function fetchStatus(string $orderId): array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->timeout(15)
                ->get($this->apiUrl.'/'.rawurlencode($orderId).'/status');
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('Tidak dapat terhubung ke Midtrans.', previous: $e);
        }

        if ($response->failed() || ! is_array($response->json())) {
            throw new PaymentGatewayException('Gagal mengambil status dari Midtrans.');
        }

        return $response->json();
    }

    public function hasValidSignature(array $payload): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                return false;
            }
        }

        if ($this->serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey);

        return hash_equals($expected, $payload['signature_key']);
    }
}
