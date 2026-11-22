<?php

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('settles a donation through the webhook without CSRF', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 100_000]);

    $this->postJson('/webhooks/midtrans', midtransPayload($donation))
        ->assertOk()
        ->assertJson(['status' => 'paid']);

    expect($donation->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($campaign->fresh()->collected_amount)->toBe(100_000);
});

it('ignores duplicate webhook deliveries', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 100_000]);
    $payload = midtransPayload($donation);

    $this->postJson('/webhooks/midtrans', $payload)->assertOk();
    $this->postJson('/webhooks/midtrans', $payload)->assertOk();
    $this->postJson('/webhooks/midtrans', $payload)->assertOk();

    expect($campaign->fresh()->collected_amount)->toBe(100_000)
        ->and($campaign->fresh()->donor_count)->toBe(1);
});

it('rejects forged notifications with 403', function () {
    $donation = Donation::factory()->create();

    $this->postJson('/webhooks/midtrans', midtransPayload($donation, overrides: ['signature_key' => 'forged']))
        ->assertForbidden();

    expect($donation->fresh()->status)->toBe(DonationStatus::Pending);
});

it('answers 200 for unknown orders so Midtrans stops retrying', function () {
    $ghost = new Donation(['order_id' => 'AKA-000000-GHOST000', 'amount' => 10_000]);

    $this->postJson('/webhooks/midtrans', midtransPayload($ghost))->assertOk();
});

it('a browser redirect to the status page never marks a donation paid', function () {
    $donation = Donation::factory()->create();

    $this->get(route('donations.status', $donation->order_id).'?transaction_status=settlement&status_code=200')
        ->assertOk()
        ->assertSee('Menunggu pembayaran');

    expect($donation->fresh()->status)->toBe(DonationStatus::Pending);
});
