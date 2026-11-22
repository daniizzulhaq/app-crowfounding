<?php

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('updates a pending donation from Midtrans when the webhook never arrived', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 50_000]);
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($donation))]);

    $this->getJson(route('donations.status.json', $donation->order_id))->assertJson(['status' => 'paid']);

    expect($donation->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($campaign->fresh()->collected_amount)->toBe(50_000);
});

it('shows success on the status page right after Midtrans settles', function () {
    $donation = Donation::factory()->create(['amount' => 50_000]);
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($donation))]);

    $this->get(route('donations.status', $donation->order_id))->assertOk()->assertSee('Donasi berhasil');
});

it('throttles Midtrans checks per donation', function () {
    $donation = Donation::factory()->create();
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($donation, 'pending', ['status_code' => '201']))]);

    foreach (range(1, 5) as $i) {
        $this->getJson(route('donations.status.json', $donation->order_id))->assertJson(['status' => 'pending']);
    }

    Http::assertSentCount(1);
});

it('does not trust a forged status response', function () {
    $donation = Donation::factory()->create();
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($donation, overrides: ['signature_key' => 'forged']))]);

    $this->getJson(route('donations.status.json', $donation->order_id))->assertJson(['status' => 'pending']);
});

it('keeps working when Midtrans is down', function () {
    $donation = Donation::factory()->create();
    Http::fake(['*' => Http::response([], 500)]);

    $this->get(route('donations.status', $donation->order_id))->assertOk()->assertSee('Menunggu pembayaran');
});

it('never calls Midtrans for donations that are already final', function () {
    $donation = Donation::factory()->paid()->create();
    Http::fake();

    $this->getJson(route('donations.status.json', $donation->order_id))->assertJson(['status' => 'paid']);

    Http::assertNothingSent();
});

it('syncs recent pending donations from the scheduler', function () {
    $campaign = Campaign::factory()->create();
    $settled = Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 30_000]);
    $old = Donation::factory()->create(['created_at' => now()->subDays(3)]);
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($settled))]);

    $this->artisan('donations:sync-pending')->assertSuccessful();

    expect($settled->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($old->fresh()->status)->toBe(DonationStatus::Pending)
        ->and($campaign->fresh()->collected_amount)->toBe(30_000);
});
