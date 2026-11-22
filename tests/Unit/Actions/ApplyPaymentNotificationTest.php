<?php

use App\Actions\Donations\ApplyPaymentNotification;
use App\Actions\Donations\InvalidSignatureException;
use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->campaign = Campaign::factory()->create();
    $this->donation = Donation::factory()->create(['campaign_id' => $this->campaign->id, 'amount' => 50_000]);
    $this->action = app(ApplyPaymentNotification::class);
});

it('marks a donation paid and adds money to the campaign on settlement', function () {
    $this->action->handle(midtransPayload($this->donation));

    $this->donation->refresh();
    expect($this->donation->status)->toBe(DonationStatus::Paid)
        ->and($this->donation->paid_at)->not->toBeNull()
        ->and($this->donation->payment_method)->toBe(PaymentMethod::Qris)
        ->and($this->campaign->fresh()->collected_amount)->toBe(50_000)
        ->and($this->campaign->fresh()->donor_count)->toBe(1);

    Queue::assertPushed(SendWhatsAppMessage::class, fn ($job) => $job->phone === $this->donation->phone);
});

it('is idempotent: duplicate notifications never add money twice', function () {
    $payload = midtransPayload($this->donation);

    $this->action->handle($payload);
    $this->action->handle($payload);
    $this->action->handle(midtransPayload($this->donation, 'settlement'));

    expect($this->campaign->fresh()->collected_amount)->toBe(50_000)
        ->and($this->campaign->fresh()->donor_count)->toBe(1);
    Queue::assertPushed(SendWhatsAppMessage::class, 1);
});

it('rejects an invalid signature without touching data', function () {
    $payload = midtransPayload($this->donation, overrides: ['signature_key' => str_repeat('a', 128)]);

    expect(fn () => $this->action->handle($payload))->toThrow(InvalidSignatureException::class);
    expect($this->donation->fresh()->status)->toBe(DonationStatus::Pending)
        ->and($this->campaign->fresh()->collected_amount)->toBe(0);
});

it('rejects a tampered gross amount even with matching signature recomputed by attacker without key', function () {
    // Attacker changes amount but cannot recompute signature without the server key.
    $payload = midtransPayload($this->donation);
    $payload['gross_amount'] = '5000000.00';

    expect(fn () => $this->action->handle($payload))->toThrow(InvalidSignatureException::class);
});

it('does not mark paid when gross_amount differs from the donation amount', function () {
    $payload = midtransPayload($this->donation, overrides: ['gross_amount' => '10000.00']);

    $this->action->handle($payload);

    expect($this->donation->fresh()->status)->toBe(DonationStatus::Pending)
        ->and($this->campaign->fresh()->collected_amount)->toBe(0);
});

it('marks pending donations as expired or failed', function (string $midtrans, DonationStatus $expected) {
    $this->action->handle(midtransPayload($this->donation, $midtrans, ['status_code' => '202']));

    expect($this->donation->fresh()->status)->toBe($expected);
})->with([
    ['expire', DonationStatus::Expired],
    ['cancel', DonationStatus::Failed],
    ['deny', DonationStatus::Failed],
]);

it('never downgrades a paid donation', function () {
    $this->action->handle(midtransPayload($this->donation));
    $this->action->handle(midtransPayload($this->donation, 'expire', ['status_code' => '202']));

    expect($this->donation->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($this->campaign->fresh()->collected_amount)->toBe(50_000);
});

it('accepts a late settlement after the donation was expired locally', function () {
    $this->donation->forceFill(['status' => DonationStatus::Expired])->save();

    $this->action->handle(midtransPayload($this->donation));

    expect($this->donation->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($this->campaign->fresh()->collected_amount)->toBe(50_000);
});

it('handles general fund donations without a campaign', function () {
    $donation = Donation::factory()->generalFund()->create(['amount' => 25_000]);

    $this->action->handle(midtransPayload($donation));

    expect($donation->fresh()->status)->toBe(DonationStatus::Paid);
});
