<?php

namespace App\Filament\Resources;

use App\Actions\Reports\GenerateMonthlyReport;
use App\Actions\Reports\PublishMonthlyReport;
use App\Enums\ReportStatus;
use App\Filament\Resources\MonthlyReportResource\Pages;
use App\Models\MonthlyReport;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use RuntimeException;

class MonthlyReportResource extends Resource
{
    protected static ?string $model = MonthlyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan bulanan';

    protected static ?string $modelLabel = 'laporan bulanan';

    protected static ?string $pluralModelLabel = 'laporan bulanan';

    protected static ?string $slug = 'laporan-bulanan';

    public static function table(Table $table): Table
    {
        $money = fn (int $state) => rupiah($state);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('year')->orderByDesc('month'))
            ->columns([
                Tables\Columns\TextColumn::make('period_label')->label('Periode')->weight('semibold'),
                Tables\Columns\TextColumn::make('total_in')->label('Masuk')->formatStateUsing($money)->alignEnd(),
                Tables\Columns\TextColumn::make('total_operational')->label('Operasional')->formatStateUsing($money)->alignEnd(),
                Tables\Columns\TextColumn::make('total_distributed')->label('Disalurkan')->formatStateUsing($money)->alignEnd(),
                Tables\Columns\TextColumn::make('closing_balance')->label('Saldo akhir')->formatStateUsing($money)->alignEnd(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('generated_at')->label('Dibuat')->since()->toggleable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Pratinjau'),
                Tables\Actions\Action::make('regenerate')
                    ->label('Hitung ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (MonthlyReport $record) => ! $record->isPublished())
                    ->action(function (MonthlyReport $record, GenerateMonthlyReport $generate) {
                        $generate->handle($record->year, $record->month);
                        Notification::make()->title('Laporan dihitung ulang')->success()->send();
                    }),
                static::publishAction(Tables\Actions\Action::make('publish')),
                Tables\Actions\Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (MonthlyReport $record) => $record->isPublished())
                    ->url(fn (MonthlyReport $record) => route('reports.download', $record), shouldOpenInNewTab: true),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * @template T of \Filament\Actions\Action|Tables\Actions\Action
     *
     * @param  T  $action
     * @return T
     */
    public static function publishAction($action)
    {
        return $action
            ->label('Terbitkan')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan laporan?')
            ->modalDescription('Laporan yang sudah terbit tampil di website dan tidak dapat dihitung ulang.')
            ->visible(fn (?MonthlyReport $record) => $record && ! $record->isPublished())
            ->action(function (MonthlyReport $record, PublishMonthlyReport $publish, GenerateMonthlyReport $generate) {
                try {
                    // Recalculate right before publishing so numbers are final.
                    $record = $generate->handle($record->year, $record->month);
                    $publish->handle($record);
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Laporan '.$record->period_label.' terbit')->success()->send();
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $money = fn ($state) => rupiah((int) $state);

        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')->schema([
                Infolists\Components\TextEntry::make('period_label')->label('Periode'),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('donation_count')->label('Jumlah donasi')->numeric(locale: 'id'),
                Infolists\Components\TextEntry::make('opening_balance')->label('Saldo awal')->formatStateUsing($money),
                Infolists\Components\TextEntry::make('total_in')->label('Dana masuk')->formatStateUsing($money),
                Infolists\Components\TextEntry::make('total_operational')->label('Operasional')
                    ->formatStateUsing(fn ($state, MonthlyReport $record) => rupiah((int) $state).' ('.$record->operational_fee_percent.'%)'),
                Infolists\Components\TextEntry::make('total_distributed')->label('Disalurkan')->formatStateUsing($money),
                Infolists\Components\TextEntry::make('closing_balance')->label('Saldo akhir')->formatStateUsing($money)->weight('bold'),
            ])->columns(4),
            Infolists\Components\Section::make('Rincian per campaign')->schema([
                Infolists\Components\RepeatableEntry::make('breakdown')->hiddenLabel()->schema([
                    Infolists\Components\TextEntry::make('title')->label('Campaign')->columnSpan(2),
                    Infolists\Components\TextEntry::make('donations')->label('Donasi'),
                    Infolists\Components\TextEntry::make('in')->label('Masuk')->formatStateUsing($money),
                    Infolists\Components\TextEntry::make('distributed')->label('Disalurkan')->formatStateUsing($money),
                ])->columns(5)->placeholder('Tidak ada transaksi.'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlyReports::route('/'),
            'view' => Pages\ViewMonthlyReport::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = MonthlyReport::query()->where('status', ReportStatus::Draft)->count();

        return $drafts > 0 ? $drafts.' draf' : null;
    }
}
