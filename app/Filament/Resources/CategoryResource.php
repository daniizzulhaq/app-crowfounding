<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Support\MoneyInput;
use App\Models\Category;
use App\Services\DonationCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Program';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategori';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(60)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(80)
                ->alphaDash()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0),
            Forms\Components\Section::make('Perhitungan donasi')
                ->description('Berlaku untuk seluruh campaign di kategori ini, di website dan aplikasi mobile. Perubahan tidak mengubah rincian transaksi lama.')
                ->schema([
                    Forms\Components\Select::make('donation_type')->label('Jenis perhitungan')
                        ->options(DonationCalculator::TYPES)->default('nominal')->required()->live(),
                    Forms\Components\TextInput::make('unit_label')->label('Nama satuan')
                        ->placeholder('mushaf')->default('mushaf')->maxLength(40)
                        ->required(fn (Forms\Get $get) => $get('donation_type') === 'unit')
                        ->visible(fn (Forms\Get $get) => $get('donation_type') === 'unit'),
                    MoneyInput::make('unit_price')->label('Harga per satuan')->minValue(1)
                        ->maxValue(config('alkiroma.donation.max_amount'))
                        ->required(fn (Forms\Get $get) => $get('donation_type') === 'unit')
                        ->visible(fn (Forms\Get $get) => $get('donation_type') === 'unit'),
                    Forms\Components\TextInput::make('zakat_rate')->label('Tarif zakat (%)')
                        ->numeric()->default('2.50')->minValue(0.01)->maxValue(100)->step(0.01)->rules(['decimal:0,2'])
                        ->required(fn (Forms\Get $get) => in_array($get('donation_type'), ['zakat_income', 'zakat_mal']))
                        ->visible(fn (Forms\Get $get) => in_array($get('donation_type'), ['zakat_income', 'zakat_mal'])),
                    MoneyInput::make('zakat_nisab')->label('Nisab tahunan (rupiah)')->minValue(1)->maxValue(1000000000000)
                        ->helperText('Isi nilai nisab tahunan sesuai ketetapan lembaga yang digunakan. Untuk pendapatan bulanan, sistem membaginya dengan 12. Perbarui jika acuan nisab berubah; nilai ini tidak mengikuti harga emas secara otomatis.')
                        ->required(fn (Forms\Get $get) => in_array($get('donation_type'), ['zakat_income', 'zakat_mal']))
                        ->visible(fn (Forms\Get $get) => in_array($get('donation_type'), ['zakat_income', 'zakat_mal'])),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('donation_type')->label('Perhitungan')
                    ->formatStateUsing(fn ($state) => DonationCalculator::TYPES[$state] ?? 'Nominal bebas'),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\TextColumn::make('campaigns_count')->counts('campaigns')->label('Campaign')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
