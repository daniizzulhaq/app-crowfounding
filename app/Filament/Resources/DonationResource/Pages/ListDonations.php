<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Enums\DonationStatus;
use App\Exports\DonationsExport;
use App\Filament\Resources\DonationResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (DonationsExport $export) => $export->download($this->getFilteredSortedTableQuery())),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'paid' => Tab::make('Berhasil')->modifyQueryUsing(fn (Builder $q) => $q->where('status', DonationStatus::Paid)),
            'pending' => Tab::make('Menunggu')->modifyQueryUsing(fn (Builder $q) => $q->where('status', DonationStatus::Pending)),
            'failed' => Tab::make('Gagal / kedaluwarsa')->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', [DonationStatus::Failed, DonationStatus::Expired])),
        ];
    }
}
