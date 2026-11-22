<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Services\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds (or rebuilds) a draft monthly report from paid donations & distributions.
 *
 * Balance formula (all integer rupiah):
 *   operational = floor(total_in * fee% / 100)
 *   closing     = opening + total_in - operational - total_distributed
 */
class GenerateMonthlyReport
{
    public function __construct(private readonly Settings $settings) {}

    public function handle(int $year, int $month): MonthlyReport
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $percent = $this->settings->operationalFeePercent();

        $existing = MonthlyReport::query()->where('year', $year)->where('month', $month)->first();

        if ($existing?->status === ReportStatus::Published) {
            throw new RuntimeException('Laporan '.$existing->period_label.' sudah terbit dan tidak dapat dibuat ulang.');
        }

        $incoming = Donation::query()
            ->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('campaign_id, SUM(amount) as total, COUNT(*) as donations')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy(fn ($row) => $row->campaign_id ?? 0);

        $outgoing = Distribution::query()
            ->whereBetween('distributed_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('campaign_id, SUM(amount) as total, COUNT(*) as items')
            ->groupBy('campaign_id')
            ->get()
            ->keyBy(fn ($row) => $row->campaign_id ?? 0);

        $campaignIds = $incoming->keys()->merge($outgoing->keys())->unique()->filter()->values();
        $titles = Campaign::query()->whereKey($campaignIds)->pluck('title', 'id');

        $breakdown = $incoming->keys()->merge($outgoing->keys())->unique()
            ->map(fn ($id) => [
                'campaign_id' => $id === 0 ? null : (int) $id,
                'title' => $id === 0 ? 'Dana umum (sedekah tanpa campaign)' : ($titles[$id] ?? 'Campaign #'.$id),
                'in' => (int) ($incoming[$id]->total ?? 0),
                'donations' => (int) ($incoming[$id]->donations ?? 0),
                'distributed' => (int) ($outgoing[$id]->total ?? 0),
            ])
            ->sortByDesc('in')
            ->values()
            ->all();

        $totalIn = (int) $incoming->sum('total');
        $totalDistributed = (int) $outgoing->sum('total');
        $operational = intdiv($totalIn * $percent, 100);
        $opening = $this->openingBalance($start, $percent);

        return DB::transaction(fn () => MonthlyReport::query()->updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'opening_balance' => $opening,
                'total_in' => $totalIn,
                'total_operational' => $operational,
                'total_distributed' => $totalDistributed,
                'closing_balance' => max(0, $opening + $totalIn - $operational - $totalDistributed),
                'donation_count' => (int) $incoming->sum('donations'),
                'operational_fee_percent' => $percent,
                'breakdown' => $breakdown,
                'generated_at' => now(),
            ],
        ));
    }

    /**
     * Previous month's closing balance when it exists; otherwise computed from history.
     */
    private function openingBalance(Carbon $start, int $percent): int
    {
        $previous = $start->copy()->subMonth();

        $previousReport = MonthlyReport::query()
            ->where('year', $previous->year)
            ->where('month', $previous->month)
            ->first();

        if ($previousReport !== null) {
            return $previousReport->closing_balance;
        }

        $inBefore = (int) Donation::query()->paid()->where('paid_at', '<', $start)->sum('amount');
        $outBefore = (int) Distribution::query()->where('distributed_at', '<', $start->toDateString())->sum('amount');

        return max(0, $inBefore - intdiv($inBefore * $percent, 100) - $outBefore);
    }
}
