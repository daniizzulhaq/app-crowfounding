<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Actions\Donations\SyncDonationStatus;
use App\Enums\DonationStatus;
use App\Filament\Resources\DonationResource;
use App\Services\Payment\PaymentGatewayException;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('checkStatus')
                ->label('Cek status ke Midtrans')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->status !== DonationStatus::Paid)
                ->action(function (SyncDonationStatus $sync) {
                    try {
                        $this->record = $sync->handle($this->record);
                    } catch (PaymentGatewayException $e) {
                        Notification::make()->title('Gagal menghubungi Midtrans')->body($e->getMessage())->danger()->send();

                        return;
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('Status tidak dapat diverifikasi')->danger()->send();

                        return;
                    }

                    Notification::make()->title('Status: '.$this->record->status->getLabel())->success()->send();
                }),
        ];
    }
}
