<?php

use App\Actions\Distributions\DistributionLedger;
use App\Actions\Distributions\InsufficientFundsException;
use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;

function distributionAttributes(): array
{
    return [
        'title' => 'Penyaluran sembako',
        'distributed_at' => today()->toDateString(),
        'location' => 'Bogor',
        'recipient' => '20 keluarga',
        'body' => '<p>Isi</p>',
    ];
}

beforeEach(function () {
    $this->ledger = app(DistributionLedger::class);
    $this->campaign = Campaign::factory()->collected(1_000_000, 10)->create();
});

it('records a distribution and increases distributed_amount', function () {
    $distribution = $this->ledger->create(distributionAttributes(), $this->campaign->id, 400_000);

    expect($distribution->amount)->toBe(400_000)
        ->and($distribution->slug)->not->toBeEmpty()
        ->and($this->campaign->fresh()->distributed_amount)->toBe(400_000)
        ->and($this->campaign->fresh()->balance)->toBe(600_000);
});

it('refuses to distribute more than collected', function () {
    $this->ledger->create(distributionAttributes(), $this->campaign->id, 700_000);

    expect(fn () => $this->ledger->create(distributionAttributes(), $this->campaign->id, 300_001))
        ->toThrow(InsufficientFundsException::class);

    expect($this->campaign->fresh()->distributed_amount)->toBe(700_000)
        ->and(Distribution::count())->toBe(1);
});

it('adjusts totals on update, including moving to another campaign', function () {
    $other = Campaign::factory()->collected(500_000)->create();
    $distribution = $this->ledger->create(distributionAttributes(), $this->campaign->id, 400_000);

    // Increase within same campaign: old amount counts as free.
    $this->ledger->update($distribution, distributionAttributes(), $this->campaign->id, 1_000_000);
    expect($this->campaign->fresh()->distributed_amount)->toBe(1_000_000);

    // Move to another campaign.
    $this->ledger->update($distribution->fresh(), distributionAttributes(), $other->id, 500_000);
    expect($this->campaign->fresh()->distributed_amount)->toBe(0)
        ->and($other->fresh()->distributed_amount)->toBe(500_000);
});

it('rolls back an update that exceeds the balance', function () {
    $distribution = $this->ledger->create(distributionAttributes(), $this->campaign->id, 400_000);

    expect(fn () => $this->ledger->update($distribution, distributionAttributes(), $this->campaign->id, 1_000_001))
        ->toThrow(InsufficientFundsException::class);

    expect($this->campaign->fresh()->distributed_amount)->toBe(400_000)
        ->and($distribution->fresh()->amount)->toBe(400_000);
});

it('gives the money back when a distribution is deleted', function () {
    $distribution = $this->ledger->create(distributionAttributes(), $this->campaign->id, 400_000);

    $this->ledger->delete($distribution);

    expect($this->campaign->fresh()->distributed_amount)->toBe(0)
        ->and(Distribution::count())->toBe(0);
});

it('limits general fund distributions to paid general donations', function () {
    Donation::factory()->paid()->generalFund()->create(['amount' => 300_000]);
    Donation::factory()->generalFund()->create(['amount' => 900_000]); // pending: not counted

    $this->ledger->create(distributionAttributes(), null, 200_000);

    expect($this->ledger->available(null))->toBe(100_000);
    expect(fn () => $this->ledger->create(distributionAttributes(), null, 100_001))
        ->toThrow(InsufficientFundsException::class);
});
