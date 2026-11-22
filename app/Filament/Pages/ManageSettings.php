<?php

namespace App\Filament\Pages;

use App\Queries\HomePageData;
use App\Services\Settings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan yayasan';

    protected static ?string $title = 'Pengaturan yayasan';

    protected static ?string $slug = 'pengaturan';

    protected static string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Form $form): Form
    {
        $maxFee = (int) config('alkiroma.operational_fee_max_percent');

        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Identitas')->schema([
                    Forms\Components\TextInput::make('foundation_name')->label('Nama yayasan')->required()->maxLength(100),
                    Forms\Components\TextInput::make('tagline')->label('Tagline')->maxLength(120),
                    Forms\Components\Textarea::make('about')->label('Tentang yayasan')->rows(4)->maxLength(2000)->columnSpanFull(),
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logo (persegi, PNG/JPG)')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        // No SVG uploads: SVG can carry scripts when served from /storage.
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024)
                        ->helperText('Kosongkan untuk memakai logo bawaan Alkiroma Amanah.'),
                    Forms\Components\TextInput::make('pub_permit_number')->label('Nomor izin PUB')->maxLength(120),
                ])->columns(2),
                Forms\Components\Section::make('Kontak')->schema([
                    Forms\Components\Textarea::make('address')->label('Alamat')->rows(2)->maxLength(300)->columnSpanFull(),
                    Forms\Components\TextInput::make('phone')->label('Telepon')->tel()->maxLength(30),
                    Forms\Components\TextInput::make('whatsapp')->label('WhatsApp admin')->tel()->maxLength(20),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(120),
                    Forms\Components\Textarea::make('bank_accounts')->label('Rekening yayasan')->rows(3)->maxLength(500)
                        ->placeholder('BSI 1234567890 a.n. Yayasan Alkiroma Amanah')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('Keuangan')->schema([
                    Forms\Components\TextInput::make('operational_fee_percent')
                        ->label('Biaya operasional')
                        ->suffix('%')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue($maxFee)
                        ->required()
                        ->helperText("Maksimal {$maxFee}% sesuai ketentuan PUB. Dipakai saat membuat laporan bulanan."),
                ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan pengaturan')->submit('save'),
        ];
    }

    public function save(Settings $settings): void
    {
        $settings->set($this->form->getState());
        HomePageData::forget();

        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
