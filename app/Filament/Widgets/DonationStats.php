<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // One aggregate query for today + this month.
        $totals = Donation::query()
            ->paid()
            ->where('paid_at', '>=', now()->startOfMonth())
            ->selectRaw('SUM(amount) as month_total, COUNT(*) as month_count')
            ->selectRaw('SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END) as today_total', [today()])
            ->selectRaw('SUM(CASE WHEN paid_at >= ? THEN 1 ELSE 0 END) as today_count', [today()])
            ->first();

        return [
            Stat::make('Donasi hari ini', rupiah((int) $totals->today_total))
                ->description(((int) $totals->today_count).' transaksi berhasil')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Donasi bulan ini', rupiah((int) $totals->month_total))
                ->description(((int) $totals->month_count).' transaksi berhasil')
                ->icon('heroicon-o-calendar'),
            Stat::make('Campaign aktif', (string) Campaign::query()->active()->count())
                ->icon('heroicon-o-heart'),
            Stat::make('Donasi menunggu', (string) Donation::query()->pending()->count())
                ->description('Belum dibayar')
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
