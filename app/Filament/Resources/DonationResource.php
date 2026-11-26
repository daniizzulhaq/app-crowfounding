<?php

namespace App\Filament\Resources;

use App\Actions\Donations\SyncDonationStatus;
use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use App\Services\Payment\PaymentGatewayException;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Donasi';

    protected static ?string $modelLabel = 'donasi';

    protected static ?string $pluralModelLabel = 'donasi';

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pending = Donation::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('campaign:id,title'))
            ->columns([
                Tables\Columns\TextColumn::make('order_id')->label('Order ID')->searchable()->copyable()->fontFamily('mono')->size('sm'),
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('campaign.title')->label('Campaign')->limit(32)->placeholder('Sedekah umum'),
                Tables\Columns\TextColumn::make('donor_name')->label('Nama asli')->searchable()
                    ->description(fn (Donation $record) => $record->is_anonymous ? 'Tampil: Hamba Allah' : null),
                Tables\Columns\TextColumn::make('phone')->label('WhatsApp')->searchable()->copyable()
                    ->url(fn (Donation $record) => 'https://wa.me/'.$record->phone, shouldOpenInNewTab: true),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->sortable()->alignEnd()
                    ->formatStateUsing(fn (int $state) => rupiah($state))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing(fn ($state) => rupiah((int) $state))),
                Tables\Columns\TextColumn::make('payment_method')->label('Metode')->badge()->color('gray')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')->label('Jumlah barang')->placeholder('—')
                    ->formatStateUsing(fn ($state, Donation $record) => $state.' '.($record->calculation['unit_label'] ?? 'unit')),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('prayer')->label('Doa')->limit(40)->tooltip(fn (Donation $record) => $record->prayer)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')->label('Dibayar')->dateTime('d M Y H:i')->sortable()->placeholder('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(DonationStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('campaign_id')->label('Campaign')
                    ->relationship('campaign', 'title')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('general_fund')->label('Dana umum')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('campaign_id'),
                        false: fn (Builder $q) => $q->whereNotNull('campaign_id'),
                    ),
                Tables\Filters\SelectFilter::make('payment_method')->label('Metode')->options(PaymentMethod::class),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari tanggal')->native(false),
                        Forms\Components\DatePicker::make('until')->label('Sampai tanggal')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'Dari '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Sampai '.$data['until'] : null,
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::checkStatusAction(),
            ])
            ->bulkActions([]);
    }

    public static function checkStatusAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('checkStatus')
            ->label('Cek status ke Midtrans')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn (Donation $record) => $record->status !== DonationStatus::Paid)
            ->action(function (Donation $record, SyncDonationStatus $sync) {
                try {
                    $before = $record->status;
                    $record = $sync->handle($record);
                } catch (PaymentGatewayException $e) {
                    Notification::make()->title('Gagal menghubungi Midtrans')->body($e->getMessage())->danger()->send();

                    return;
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->title('Status tidak dapat diverifikasi')->body('Periksa server key Midtrans.')->danger()->send();

                    return;
                }

                Notification::make()
                    ->title($before === $record->status ? 'Status tidak berubah' : 'Status diperbarui')
                    ->body('Status sekarang: '.$record->status->getLabel())
                    ->success()
                    ->send();
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Donasi')->schema([
                Infolists\Components\TextEntry::make('order_id')->label('Order ID')->copyable()->fontFamily('mono'),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('amount')->label('Nominal')->formatStateUsing(fn (int $state) => rupiah($state))->weight('bold'),
                Infolists\Components\TextEntry::make('calculation.label')->label('Jenis donasi')->placeholder('Nominal bebas'),
                Infolists\Components\TextEntry::make('quantity')->label('Jumlah barang')->placeholder('—')
                    ->formatStateUsing(fn ($state, Donation $record) => $state.' '.($record->calculation['unit_label'] ?? 'unit')),
                Infolists\Components\TextEntry::make('calculation.unit_price')->label('Harga satuan saat transaksi')
                    ->formatStateUsing(fn ($state) => rupiah((int) $state))->placeholder('—'),
                Infolists\Components\TextEntry::make('campaign.title')->label('Campaign')->placeholder('Sedekah umum'),
                Infolists\Components\TextEntry::make('payment_method')->label('Metode')->placeholder('—'),
                Infolists\Components\TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                Infolists\Components\TextEntry::make('paid_at')->label('Dibayar')->dateTime('d M Y H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('expires_at')->label('Kedaluwarsa')->dateTime('d M Y H:i')->placeholder('—'),
            ])->columns(4),
            Infolists\Components\Section::make('Donatur')->schema([
                Infolists\Components\TextEntry::make('donor_name')->label('Nama asli'),
                Infolists\Components\IconEntry::make('is_anonymous')->label('Sembunyikan nama')->boolean(),
                Infolists\Components\TextEntry::make('phone')->label('WhatsApp')->copyable(),
                Infolists\Components\TextEntry::make('prayer')->label('Doa')->placeholder('—')->columnSpanFull(),
                Infolists\Components\IconEntry::make('prayer_hidden')->label('Doa disembunyikan')->boolean(),
            ])->columns(3),
            Infolists\Components\Section::make('Payload Midtrans')->schema([
                Infolists\Components\TextEntry::make('midtrans_payload')
                    ->hiddenLabel()
                    ->state(fn (Donation $record) => $record->midtrans_payload
                        ? json_encode($record->midtrans_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        : 'Belum ada notifikasi.')
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'whitespace-pre-wrap text-xs']),
            ])->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'view' => Pages\ViewDonation::route('/{record}'),
        ];
    }
}
