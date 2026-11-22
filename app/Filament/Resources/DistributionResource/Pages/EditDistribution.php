<?php

namespace App\Filament\Resources\DistributionResource\Pages;

use App\Actions\Distributions\DistributionLedger;
use App\Actions\Distributions\InsufficientFundsException;
use App\Filament\Resources\DistributionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditDistribution extends EditRecord
{
    protected static string $resource = DistributionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(DistributionLedger::class)->update(
                $record,
                Arr::only($data, ['title', 'slug', 'distributed_at', 'location', 'recipient', 'body']),
                filled($data['campaign_id'] ?? null) ? (int) $data['campaign_id'] : null,
                (int) $data['amount'],
            );
        } catch (InsufficientFundsException $e) {
            Notification::make()->title('Saldo tidak cukup')->body($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->using(fn () => app(DistributionLedger::class)->delete($this->record) ?? true),
        ];
    }
}
