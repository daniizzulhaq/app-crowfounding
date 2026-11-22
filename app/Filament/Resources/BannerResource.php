<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Program';

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'banner';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Judul (teks alternatif)')
                ->required()
                ->maxLength(120)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('image_path')
                ->label('Gambar (rasio 2:1, mis. 960×480)')
                ->image()
                ->disk('public')
                ->directory('banners')
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('2:1')
                ->imageResizeTargetWidth('960')
                ->imageResizeTargetHeight('480')
                ->maxSize(2048)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Select::make('campaign_id')
                ->label('Tautkan ke campaign')
                ->relationship('campaign', 'title', fn (Builder $query) => $query->published())
                ->searchable()
                ->preload()
                ->live(),
            Forms\Components\TextInput::make('url')
                ->label('Atau tautan lain')
                ->url()
                ->maxLength(255)
                ->hidden(fn (Forms\Get $get) => filled($get('campaign_id'))),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('campaign:id,title'))
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('Gambar')->disk('public')->height(48),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable(),
                Tables\Columns\TextColumn::make('campaign.title')->label('Campaign')->limit(40)->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBanners::route('/'),
        ];
    }
}
