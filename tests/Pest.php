<?php

use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Build a Midtrans notification payload with a valid (or forced) signature.
 *
 * @return array<string, mixed>
 */
function midtransPayload(Donation $donation, string $transactionStatus = 'settlement', array $overrides = []): array
{
    $payload = array_merge([
        'transaction_time' => now()->format('Y-m-d H:i:s'),
        'transaction_status' => $transactionStatus,
        'transaction_id' => (string) Str::uuid(),
        'status_code' => $transactionStatus === 'settlement' ? '200' : '201',
        'payment_type' => 'qris',
        'order_id' => $donation->order_id,
        'gross_amount' => $donation->amount.'.00',
        'fraud_status' => 'accept',
        'settlement_time' => now()->format('Y-m-d H:i:s'),
    ], $overrides);

    $payload['signature_key'] ??= hash(
        'sha512',
        $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'),
    );

    return $payload;
}

function fakeSnap(string $token = 'snap-token-123'): void
{
    Http::fake([
        '*/snap/v1/transactions' => Http::response([
            'token' => $token,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/'.$token,
        ], 201),
    ]);
}

function admin(): User
{
    return User::factory()->admin()->create();
}
