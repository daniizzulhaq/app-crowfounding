<?php

namespace App\Filament\Resources\MonthlyReportResource\Pages;

use App\Actions\Reports\GenerateMonthlyReport;
use App\Exports\PubQuarterlyExport;
use App\Filament\Resources\MonthlyReportResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use RuntimeException;

class ListMonthlyReports extends ListRecords
{
    protected static string $resource = MonthlyReportResource::class;

    protected function getHeaderActions(): array
    {
        $lastMonth = now()->subMonthNoOverflow();

        return [
            Actions\Action::make('generate')
                ->label('Buat laporan')
                ->icon('heroicon-o-calculator')
                ->form([
                    Forms\Components\Select::make('month')->label('Bulan')->required()->default($lastMonth->month)
                        ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => Carbon::create(2000, $m)->translatedFormat('F')])),
                    Forms\Components\Select::make('year')->label('Tahun')->required()->default($lastMonth->year)
                        ->options(collect(range(now()->year, now()->year - 5))->mapWithKeys(fn ($y) => [$y => $y])),
                ])
                ->action(function (array $data, GenerateMonthlyReport $generate) {
                    try {
                        $report = $generate->handle((int) $data['year'], (int) $data['month']);
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Draf laporan '.$report->period_label.' siap')->body('Periksa pratinjau lalu terbitkan.')->success()->send();
                    $this->redirect(MonthlyReportResource::getUrl('view', ['record' => $report]));
                }),
            Actions\Action::make('pub')
                ->label('Ekspor laporan PUB triwulan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('quarter')->label('Triwulan')->required()
                        ->default((int) ceil($lastMonth->month / 3))
                        ->options([1 => 'TW I (Jan–Mar)', 2 => 'TW II (Apr–Jun)', 3 => 'TW III (Jul–Sep)', 4 => 'TW IV (Okt–Des)']),
                    Forms\Components\Select::make('year')->label('Tahun')->required()->default($lastMonth->year)
                        ->options(collect(range(now()->year, now()->year - 5))->mapWithKeys(fn ($y) => [$y => $y])),
                ])
                ->action(fn (array $data, PubQuarterlyExport $export) => $export->download((int) $data['year'], (int) $data['quarter'])),
        ];
    }
}
