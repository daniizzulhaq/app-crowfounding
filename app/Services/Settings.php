<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Key/value site settings, cached forever and flushed on save.
 */
class Settings
{
    public const CACHE_KEY = 'settings.all';

    public const DEFAULTS = [
        'foundation_name' => 'Alkiroma Amanah',
        'tagline' => 'Sedekah amanah, tersalurkan nyata.',
        'about' => 'Alkiroma Amanah adalah lembaga sosial yang menghimpun dan menyalurkan sedekah secara amanah dan transparan. Setiap rupiah tercatat, setiap penyaluran dilaporkan.',
        'logo_path' => null,
        'pub_permit_number' => '',
        'address' => '',
        'phone' => '',
        'whatsapp' => '',
        'email' => '',
        'bank_accounts' => '',
        'operational_fee_percent' => '0',
    ];

    private ?array $cache = null;

    public function all(): array
    {
        return $this->cache ??= array_merge(
            self::DEFAULTS,
            array_filter(
                Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all()),
                fn ($value) => $value !== null && $value !== '',
            ),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->cache = null;
    }

    public function foundationName(): string
    {
        return (string) $this->get('foundation_name');
    }

    public function operationalFeePercent(): int
    {
        $max = (int) config('alkiroma.operational_fee_max_percent');

        return max(0, min($max, (int) $this->get('operational_fee_percent', 0)));
    }

    public function logoUrl(): string
    {
        $path = $this->get('logo_path');

        return $path ? Storage::disk('public')->url($path) : asset('images/logo-al-kirom.jpg');
    }

    /** Absolute filesystem path for DomPDF. */
    public function logoPath(): string
    {
        $path = $this->get('logo_path');

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        return public_path('images/logo-al-kirom.jpg');
    }

    public function whatsappLink(): ?string
    {
        $number = PhoneNumber::normalize((string) $this->get('whatsapp'));

        return $number ? 'https://wa.me/'.$number : null;
    }
}
