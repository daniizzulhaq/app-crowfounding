<?php

namespace App\Filament\Resources;

use App\Actions\Distributions\DistributionLedger;
use App\Filament\Resources\DistributionResource\Pages;
use App\Filament\Support\MoneyInput;
use App\Models\Distribution;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DistributionResource extends Resource
{
    protected static ?string $model = Distribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Program';

    protected static ?string $navigationLabel = 'Berita penyaluran';

    protected static ?string $modelLabel = 'penyaluran';

    protected static ?string $pluralModelLabel = 'berita penyaluran';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Berita')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')->required()->maxLength(120)->alphaDash()->unique(ignoreRecord: true),
                    Forms\Components\RichEditor::make('body')
                        ->label('Isi berita')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo']),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('photos')
                        ->label('Foto penyaluran')
                        ->collection('photos')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->maxFiles(12)
                        ->maxSize(3072)
                        ->imageResizeTargetWidth('1600')
                        ->panelLayout('grid')
                        ->helperText('Maks. 12 foto, masing-masing 3 MB.'),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('Dana')->schema([
                    Forms\Components\Select::make('campaign_id')
                        ->label('Sumber dana')
                        ->relationship('campaign', 'title')
                        ->searchable()
                        ->preload()
                        ->placeholder('Dana umum (tanpa campaign)')
                        ->live()
                        ->helperText(fn (Forms\Get $get, ?Distribution $record) => 'Saldo tersedia: '.rupiah(
                            app(DistributionLedger::class)->availableFor(static::campaignIdFrom($get), $record)
                        )),
                    MoneyInput::make('amount')
                        ->label('Nominal disalurkan')
                        ->required()
                        ->minValue(1)
                        ->rule(fn (Forms\Get $get, ?Distribution $record) => static::balanceRule($get, $record)),
                    Forms\Components\DatePicker::make('distributed_at')
                        ->label('Tanggal penyaluran')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->default(today())
                        ->maxDate(today())
                        ->required(),
                    Forms\Components\TextInput::make('location')->label('Lokasi')->required()->maxLength(150),
                    Forms\Components\TextInput::make('recipient')->label('Penerima manfaat')->required()->maxLength(150)
                        ->placeholder('mis. 120 keluarga dhuafa'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    /**
     * UX validation; DistributionLedger re-checks inside a locked transaction.
     */
    protected static function balanceRule(Forms\Get $get, ?Distribution $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record) {
            $available = app(DistributionLedger::class)->availableFor(static::campaignIdFrom($get), $record);

            if ((int) $value > $available) {
                $fail('Nominal melebihi saldo yang tersedia ('.rupiah($available).').');
            }
        };
    }

    public static function campaignIdFrom(Forms\Get $get): ?int
    {
        return filled($get('campaign_id')) ? (int) $get('campaign_id') : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('distributed_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['campaign:id,title', 'media']))
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('photos')->collection('photos')->conversion('thumb')->label('')->limit(1)->height(40)->width(54),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->limit(45)->weight('semibold')
                    ->description(fn (Distribution $record) => $record->location),
                Tables\Columns\TextColumn::make('campaign.title')->label('Sumber dana')->limit(30)->placeholder('Dana umum'),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->sortable()->alignEnd()
                    ->formatStateUsing(fn (int $state) => rupiah($state)),
                Tables\Columns\TextColumn::make('distributed_at')->label('Tanggal')->date('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_id')->label('Campaign')->relationship('campaign', 'title')->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Lihat')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Distribution $record) => route('distributions.show', $record), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(fn (Distribution $record) => app(DistributionLedger::class)->delete($record) ?? true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistributions::route('/'),
            'create' => Pages\CreateDistribution::route('/create'),
            'edit' => Pages\EditDistribution::route('/{record}/edit'),
        ];
    }
}
