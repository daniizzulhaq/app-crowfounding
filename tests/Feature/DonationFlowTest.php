<?php

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function donationPayload(array $overrides = []): array
{
    return array_merge([
        'amount' => 50_000,
        'donor_name' => 'Fulanah',
        'is_anonymous' => false,
        'phone' => '0812 3456 7890',
        'prayer' => 'Semoga lancar',
    ], $overrides);
}

it('shows the donation form for a campaign and for the general fund', function () {
    $campaign = Campaign::factory()->create();

    $this->get(route('campaigns.donate', $campaign))->assertOk()->assertSee('Pilih nominal')->assertSee('snap.js', false);
    $this->get(route('donations.create', ['nominal' => 25000]))->assertOk()->assertSee('Sedekah umum')->assertSee('Bayar Rp25.000');
});

it('redirects from the form when the campaign is closed', function () {
    $campaign = Campaign::factory()->completed()->create();

    $this->get(route('campaigns.donate', $campaign))->assertRedirect(route('campaigns.show', $campaign));
});

it('creates a pending donation and returns a Snap token', function () {
    fakeSnap('tok-xyz');
    $campaign = Campaign::factory()->create();

    $response = $this->postJson(route('donations.store'), donationPayload(['campaign' => $campaign->slug]))
        ->assertCreated()
        ->assertJsonPath('snap_token', 'tok-xyz')
        ->assertJsonStructure(['order_id', 'snap_token', 'redirect_url', 'status_url']);

    $donation = Donation::firstWhere('order_id', $response->json('order_id'));
    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->phone)->toBe('6281234567890')
        ->and($donation->amount)->toBe(50_000)
        ->and($campaign->fresh()->collected_amount)->toBe(0);
});

it('accepts formatted amounts like "25.000"', function () {
    fakeSnap();

    $this->postJson(route('donations.store'), donationPayload(['amount' => '25.000']))->assertCreated();

    expect(Donation::first()->amount)->toBe(25_000);
});

it('validates the donation form', function (array $overrides, string $field) {
    fakeSnap();

    $this->postJson(route('donations.store'), donationPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'amount too small' => [['amount' => 5_000], 'amount'],
    'amount too big' => [['amount' => 200_000_000], 'amount'],
    'missing name when not anonymous' => [['donor_name' => ''], 'donor_name'],
    'invalid phone' => [['phone' => '12345'], 'phone'],
    'prayer too long' => [['prayer' => str_repeat('a', 501)], 'prayer'],
    'unknown campaign' => [['campaign' => 'tidak-ada'], 'campaign'],
    'honeypot filled' => [['website' => 'spam'], 'website'],
]);

it('allows anonymous donations without a name', function () {
    fakeSnap();

    $this->postJson(route('donations.store'), donationPayload(['donor_name' => '', 'is_anonymous' => true]))->assertCreated();

    expect(Donation::first())->display_name->toBe('Hamba Allah');
});

it('rejects donations to a closed campaign', function () {
    fakeSnap();
    $campaign = Campaign::factory()->completed()->create();

    $this->postJson(route('donations.store'), donationPayload(['campaign' => $campaign->slug]))->assertUnprocessable();
    expect(Donation::count())->toBe(0);
});

it('returns 503 when Midtrans is unavailable', function () {
    Http::fake(['*' => Http::response([], 500)]);

    $this->postJson(route('donations.store'), donationPayload())->assertStatus(503)->assertJsonPath('message', fn ($m) => str_contains($m, 'sibuk'));
});

it('rate limits the donation endpoint', function () {
    fakeSnap();

    foreach (range(1, 6) as $i) {
        $this->postJson(route('donations.store'), donationPayload())->assertCreated();
    }

    $this->postJson(route('donations.store'), donationPayload())->assertTooManyRequests();
});

it('shows each donation status from the database only', function (DonationStatus $status, string $text) {
    $donation = Donation::factory()->create(['status' => $status, 'paid_at' => $status === DonationStatus::Paid ? now() : null]);

    $this->get(route('donations.status', $donation->order_id))->assertOk()->assertSee($text);
    $this->getJson(route('donations.status.json', $donation->order_id))->assertJson(['status' => $status->value]);
})->with([
    [DonationStatus::Pending, 'Menunggu pembayaran'],
    [DonationStatus::Paid, 'Donasi berhasil'],
    [DonationStatus::Failed, 'Pembayaran gagal'],
    [DonationStatus::Expired, 'Waktu pembayaran habis'],
]);

it('shows a WhatsApp share button and thank-you card on success', function () {
    $donation = Donation::factory()->paid()->create();

    $this->get(route('donations.status', $donation->order_id))
        ->assertSee('Ajak teman lewat WhatsApp')
        ->assertSee('https://wa.me/?text=', false)
        ->assertDontSee($donation->phone);
});

it('returns 404 for malformed or unknown order ids', function () {
    $this->get('/donasi/bukan-order')->assertNotFound();
    $this->get('/donasi/AKA-261119-ABCDEFGH')->assertNotFound();
});
