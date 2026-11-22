<?php

namespace App\Filament\Resources\MonthlyReportResource\Pages;

use App\Actions\Reports\PublishMonthlyReport;
use App\Filament\Resources\MonthlyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonthlyReport extends ViewRecord
{
    protected static string $resource = MonthlyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewPdf')
                ->label('Pratinjau PDF')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(fn (PublishMonthlyReport $publisher) => response()->streamDownload(
                    fn () => print ($publisher->renderPdf($this->record)),
                    'pratinjau-'.$this->record->pdfFileName(),
                    ['Content-Type' => 'application/pdf'],
                )),
            MonthlyReportResource::publishAction(Actions\Action::make('publish'))
                ->after(fn () => $this->refreshFormData(['status', 'published_at'])),
            Actions\Action::make('download')
                ->label('Unduh PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->record->isPublished())
                ->url(fn () => route('reports.download', $this->record), shouldOpenInNewTab: true),
        ];
    }
}
