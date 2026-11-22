<?php

use App\Actions\Donations\CreateDonation;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payment\PaymentGatewayException;
use Illuminate\Support\Facades\Http;

it('creates a pending donation with normalized phone and snap token', function () {
    fakeSnap('tok-1');
    $campaign = Campaign::factory()->create();

    $donation = app(CreateDonation::class)->handle([
        'donor_name' => 'Fulan',
        'is_anonymous' => false,
        'phone' => '0812-3456-7890',
        'amount' => 25_000,
        'prayer' => 'Semoga berkah',
    ], $campaign);

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->order_id)->toMatch('/^AKA-\d{6}-[A-Z0-9]{8}$/')
        ->and($donation->phone)->toBe('6281234567890')
        ->and($donation->snap_token)->toBe('tok-1')
        ->and($donation->expires_at->isFuture())->toBeTrue()
        ->and($campaign->fresh()->collected_amount)->toBe(0); // money only via webhook

    Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 25_000
        && $request['transaction_details']['order_id'] === $donation->order_id);
});

it('stores the real name but flags anonymous donors', function () {
    fakeSnap();

    $donation = app(CreateDonation::class)->handle([
        'donor_name' => 'Nama Asli', 'is_anonymous' => true, 'phone' => '081234567890', 'amount' => 10_000, 'prayer' => null,
    ]);

    expect($donation->donor_name)->toBe('Nama Asli')
        ->and($donation->is_anonymous)->toBeTrue()
        ->and($donation->display_name)->toBe('Hamba Allah')
        ->and($donation->campaign_id)->toBeNull();
});

it('removes the donation when Midtrans fails', function () {
    Http::fake(['*' => Http::response(['error_messages' => ['bad']], 500)]);

    expect(fn () => app(CreateDonation::class)->handle([
        'donor_name' => 'A', 'is_anonymous' => false, 'phone' => '081234567890', 'amount' => 10_000, 'prayer' => null,
    ]))->toThrow(PaymentGatewayException::class);

    expect(Donation::count())->toBe(0);
});

it('refuses campaigns that no longer accept donations', function () {
    fakeSnap();
    $campaign = Campaign::factory()->completed()->create();

    expect(fn () => app(CreateDonation::class)->handle([
        'donor_name' => 'A', 'is_anonymous' => false, 'phone' => '081234567890', 'amount' => 10_000, 'prayer' => null,
    ], $campaign))->toThrow(InvalidArgumentException::class);
});
