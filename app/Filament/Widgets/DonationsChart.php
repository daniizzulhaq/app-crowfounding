<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;

class DonationsChart extends ChartWidget
{
    protected static ?string $heading = 'Donasi berhasil — 30 hari terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $start = today()->subDays(29);

        $daily = Donation::query()
            ->paid()
            ->where('paid_at', '>=', $start)
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($date = $start->copy(); $date->lte(today()); $date->addDay()) {
            $labels[] = $date->translatedFormat('d M');
            $values[] = (int) ($daily[$date->toDateString()] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Donasi (Rp)',
                'data' => $values,
                'borderColor' => '#0E7A5A',
                'backgroundColor' => 'rgba(14, 122, 90, 0.12)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 2,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
