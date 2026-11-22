<?php

namespace App\Filament\Resources\DistributionResource\Pages;

use App\Actions\Distributions\DistributionLedger;
use App\Actions\Distributions\InsufficientFundsException;
use App\Filament\Resources\DistributionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateDistribution extends CreateRecord
{
    protected static string $resource = DistributionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(DistributionLedger::class)->create(
                Arr::only($data, ['title', 'slug', 'distributed_at', 'location', 'recipient', 'body']),
                filled($data['campaign_id'] ?? null) ? (int) $data['campaign_id'] : null,
                (int) $data['amount'],
            );
        } catch (InsufficientFundsException $e) {
            Notification::make()->title('Saldo tidak cukup')->body($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
