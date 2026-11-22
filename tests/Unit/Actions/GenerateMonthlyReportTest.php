<?php

use App\Actions\Reports\GenerateMonthlyReport;
use App\Actions\Reports\PublishMonthlyReport;
use App\Enums\ReportStatus;
use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;
use App\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow('2026-11-15 10:00:00');
    app(Settings::class)->set(['operational_fee_percent' => 5]);
});

afterEach(fn () => Carbon::setTestNow());

it('summarizes a month with operational fee and breakdown', function () {
    $campaign = Campaign::factory()->create(['title' => 'Sumur bor']);

    // Before October: opening balance source.
    Donation::factory()->paid()->create(['campaign_id' => $campaign->id, 'amount' => 1_000_000, 'paid_at' => '2026-09-10 08:00:00']);

    // October.
    Donation::factory()->paid()->create(['campaign_id' => $campaign->id, 'amount' => 2_000_000, 'paid_at' => '2026-10-05 08:00:00']);
    Donation::factory()->paid()->generalFund()->create(['amount' => 500_000, 'paid_at' => '2026-10-31 23:59:00']);
    Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 9_000_000]); // pending: ignored
    Distribution::factory()->create(['campaign_id' => $campaign->id, 'amount' => 800_000, 'distributed_at' => '2026-10-20']);

    // November (outside period).
    Donation::factory()->paid()->create(['campaign_id' => $campaign->id, 'amount' => 7_000_000, 'paid_at' => '2026-11-01 00:00:01']);

    $report = app(GenerateMonthlyReport::class)->handle(2026, 10);

    // opening = 1.000.000 - 5% (50.000) = 950.000
    expect($report->opening_balance)->toBe(950_000)
        ->and($report->total_in)->toBe(2_500_000)
        ->and($report->total_operational)->toBe(125_000)
        ->and($report->total_distributed)->toBe(800_000)
        ->and($report->closing_balance)->toBe(950_000 + 2_500_000 - 125_000 - 800_000)
        ->and($report->donation_count)->toBe(2)
        ->and($report->status)->toBe(ReportStatus::Draft)
        ->and(collect($report->breakdown)->firstWhere('title', 'Sumur bor'))
        ->toMatchArray(['in' => 2_000_000, 'distributed' => 800_000, 'donations' => 1]);
});

it('uses the previous report closing balance as opening balance', function () {
    $september = app(GenerateMonthlyReport::class)->handle(2026, 9);
    $september->forceFill(['closing_balance' => 123_456])->save();

    expect(app(GenerateMonthlyReport::class)->handle(2026, 10)->opening_balance)->toBe(123_456);
});

it('publishes a PDF and blocks regenerating a published report', function () {
    Storage::fake('local');

    $report = app(GenerateMonthlyReport::class)->handle(2026, 10);
    app(PublishMonthlyReport::class)->handle($report);

    expect($report->fresh()->status)->toBe(ReportStatus::Published);
    Storage::disk('local')->assertExists($report->fresh()->pdf_path);

    expect(fn () => app(GenerateMonthlyReport::class)->handle(2026, 10))->toThrow(RuntimeException::class);
});
