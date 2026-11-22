<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrayerResource\Pages;
use App\Models\Donation;
use App\Queries\HomePageData;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moderasi doa: hide/show prayers on paid donations.
 */
class PrayerResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $slug = 'moderasi-doa';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Donasi';

    protected static ?string $navigationLabel = 'Moderasi doa';

    protected static ?string $modelLabel = 'doa';

    protected static ?string $pluralModelLabel = 'doa';

    protected static ?int $navigationSort = 2;

    protected static bool $isGloballySearchable = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->paid()
            ->whereNotNull('prayer')
            ->where('prayer', '!=', '');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('campaign:id,title'))
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('donor_name')->label('Donatur')->searchable()
                    ->description(fn (Donation $record) => $record->is_anonymous ? 'Tampil: Hamba Allah' : null),
                Tables\Columns\TextColumn::make('prayer')->label('Doa')->wrap()->searchable()->lineClamp(3),
                Tables\Columns\TextColumn::make('campaign.title')->label('Campaign')->limit(30)->placeholder('Sedekah umum'),
                Tables\Columns\IconColumn::make('prayer_hidden')->label('Tampil')
                    ->state(fn (Donation $record) => ! $record->prayer_hidden)->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('prayer_hidden')->label('Disembunyikan'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Donation $record) => $record->prayer_hidden ? 'Tampilkan' : 'Sembunyikan')
                    ->icon(fn (Donation $record) => $record->prayer_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (Donation $record) => $record->prayer_hidden ? 'success' : 'danger')
                    ->action(function (Donation $record) {
                        $record->forceFill(['prayer_hidden' => ! $record->prayer_hidden])->save();
                        HomePageData::forget();
                        Notification::make()->title($record->prayer_hidden ? 'Doa disembunyikan' : 'Doa ditampilkan')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('hide')
                    ->label('Sembunyikan')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        Donation::query()->whereKey($records->modelKeys())->update(['prayer_hidden' => true]);
                        HomePageData::forget();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrayers::route('/'),
        ];
    }
}
