<?php

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Console\Scheduling\Schedule;

it('expires overdue pending donations only', function () {
    $overdue = Donation::factory()->create(['expires_at' => now()->subMinute()]);
    $fresh = Donation::factory()->create(['expires_at' => now()->addMinutes(30)]);
    $paid = Donation::factory()->paid()->create(['expires_at' => now()->subDay()]);

    $this->artisan('donations:expire')->assertSuccessful();

    expect($overdue->fresh()->status)->toBe(DonationStatus::Expired)
        ->and($fresh->fresh()->status)->toBe(DonationStatus::Pending)
        ->and($paid->fresh()->status)->toBe(DonationStatus::Paid);
});

it('completes campaigns past their deadline', function () {
    $past = Campaign::factory()->create(['deadline' => today()->subDay()]);
    $today = Campaign::factory()->create(['deadline' => today()]);
    $open = Campaign::factory()->create(['deadline' => null]);

    $this->artisan('campaigns:complete-expired')->assertSuccessful();

    expect($past->fresh()->status)->toBe(CampaignStatus::Completed)
        ->and($today->fresh()->status)->toBe(CampaignStatus::Active)
        ->and($open->fresh()->status)->toBe(CampaignStatus::Active);
});

it('registers the scheduled tasks', function () {
    $commands = collect(app(Schedule::class)->events())->map->command->implode("\n");

    expect($commands)
        ->toContain('donations:expire')
        ->toContain('campaigns:complete-expired')
        ->toContain('queue:work');
});
