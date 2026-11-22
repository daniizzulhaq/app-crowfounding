<?php

use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('seeds one test campaign with zero balances and no example transactions', function () {
    Storage::fake('local');

    $this->seed();

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->sole()->is_admin)->toBeTrue()
        ->and(Category::query()->count())->toBe(1)
        ->and(Campaign::query()->count())->toBe(1)
        ->and(Banner::query()->count())->toBe(1)
        ->and(Donation::query()->count())->toBe(0)
        ->and(Distribution::query()->count())->toBe(0)
        ->and(MonthlyReport::query()->count())->toBe(1);

    $campaign = Campaign::query()->sole();

    expect($campaign->collected_amount)->toBe(0)
        ->and($campaign->distributed_amount)->toBe(0)
        ->and($campaign->donor_count)->toBe(0)
        ->and($campaign->target_amount)->toBe(1_000_000)
        ->and($campaign->acceptsDonations())->toBeTrue()
        ->and($campaign->isPubliclyVisible())->toBeTrue()
        ->and(Banner::query()->sole()->campaign_id)->toBe($campaign->id);

    $report = MonthlyReport::query()->sole();

    foreach (['opening_balance', 'total_in', 'total_operational', 'total_distributed', 'closing_balance', 'donation_count'] as $field) {
        expect($report->{$field})->toBe(0);
    }

    $this->get('/')->assertOk()->assertSee($campaign->title);
    $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Rp0');
    $this->get(route('reports.show', $report))->assertOk();
    $this->get(route('reports.download', $report))->assertOk()->assertHeader('content-type', 'application/pdf');
});
