<?php

namespace App\Filament\Resources;

use App\Enums\CampaignStatus;
use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Support\MoneyInput;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Program';

    protected static ?string $modelLabel = 'campaign';

    protected static ?string $pluralModelLabel = 'campaign';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Konten')->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(120)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText('Alamat halaman: /campaign/slug'),
                    Forms\Components\Textarea::make('summary')
                        ->label('Ringkasan singkat')
                        ->rows(2)
                        ->maxLength(300)
                        ->helperText('Dipakai untuk deskripsi pencarian & bagikan.'),
                    Forms\Components\RichEditor::make('story')
                        ->label('Cerita')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link', 'attachFiles', 'undo', 'redo'])
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('campaign-story'),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('Publikasi')->schema([
                    Forms\Components\Select::make('status')
                        ->options(CampaignStatus::class)
                        ->default(CampaignStatus::Active)
                        ->required()
                        ->helperText('Aktif = langsung tayang di website.'),
                    Forms\Components\Toggle::make('is_featured')->label('Campaign pilihan (beranda)'),
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(60),
                        ]),
                ]),
                Forms\Components\Section::make('Target')->schema([
                    MoneyInput::make('target_amount')
                        ->label('Target dana')
                        ->required()
                        ->minValue(100_000),
                    Forms\Components\DatePicker::make('deadline')
                        ->label('Batas waktu')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->minDate(fn (string $operation) => $operation === 'create' ? today() : null)
                        ->helperText('Kosongkan bila tanpa batas waktu.'),
                ]),
                Forms\Components\Section::make('Foto sampul')->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('cover')
                        ->hiddenLabel()
                        ->collection('cover')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth('1280')
                        ->imageResizeTargetHeight('720')
                        ->maxSize(3072)
                        ->helperText('Rasio 16:9, maks. 3 MB.'),
                ]),
                Forms\Components\Section::make('Angka (otomatis)')->schema([
                    Forms\Components\Placeholder::make('collected')->label('Terkumpul')
                        ->content(fn (?Campaign $record) => rupiah($record?->collected_amount ?? 0)),
                    Forms\Components\Placeholder::make('distributed')->label('Tersalurkan')
                        ->content(fn (?Campaign $record) => rupiah($record?->distributed_amount ?? 0)),
                    Forms\Components\Placeholder::make('donors')->label('Jumlah donasi')
                        ->content(fn (?Campaign $record) => number_format($record?->donor_count ?? 0, 0, ',', '.')),
                ])->hiddenOn('create')->description('Hanya berubah dari pembayaran & penyaluran.'),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category:id,name', 'media'])->withCount(['donations', 'distributions']))
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->conversion('thumb')->label('')->height(40)->width(54),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->limit(45)->weight('semibold')
                    ->description(fn (Campaign $record) => $record->category?->name),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('collected_amount')->label('Terkumpul')->sortable()
                    ->formatStateUsing(fn (int $state) => rupiah($state))
                    ->description(fn (Campaign $record) => $record->progress_percent.'% dari '.rupiah($record->target_amount)),
                Tables\Columns\TextColumn::make('distributed_amount')->label('Tersalurkan')->sortable()
                    ->formatStateUsing(fn (int $state) => rupiah($state))->toggleable(),
                Tables\Columns\TextColumn::make('donor_count')->label('Donasi')->numeric(locale: 'id')->sortable(),
                Tables\Columns\TextColumn::make('deadline')->label('Deadline')->date('d M Y')->sortable()->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_featured')->label('Pilihan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CampaignStatus::class),
                Tables\Filters\SelectFilter::make('category_id')->label('Kategori')->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Campaign pilihan'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Lihat')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Campaign $record) => route('campaigns.show', $record), shouldOpenInNewTab: true)
                    ->visible(fn (Campaign $record) => $record->isPubliclyVisible()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
