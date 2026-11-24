<?php

namespace App\Support;

final class ProgramImages
{
    public static function all(): array
    {
        return [
            ['file' => 'jumat-berkah.jpg', 'title' => 'Jumat Berkah', 'query' => 'Jumat', 'width' => 1600, 'height' => 800],
            ['file' => 'sedekah-subuh.jpg', 'title' => 'Sedekah Subuh', 'query' => 'Subuh', 'width' => 1600, 'height' => 800],
            ['file' => 'zakat.jpg', 'title' => 'Zakat & Fidyah', 'query' => 'Zakat', 'width' => 1536, 'height' => 1024],
            ['file' => 'santunan-yatim.jpg', 'title' => 'Santunan Anak Yatim', 'query' => 'Yatim', 'width' => 1599, 'height' => 1066],
        ];
    }

    public static function forSubject(string $subject): array
    {
        $subject = mb_strtolower($subject);
        $index = match (true) {
            str_contains($subject, 'zakat'), str_contains($subject, 'fitrah'), str_contains($subject, 'fidyah') => 2,
            str_contains($subject, 'subuh') => 1,
            str_contains($subject, 'yatim'), str_contains($subject, 'dhuafa'), str_contains($subject, 'pendidikan'),
            str_contains($subject, 'beasiswa'), str_contains($subject, 'santri') => 3,
            default => 0,
        };

        return self::all()[$index];
    }

    public static function url(array $image): string
    {
        return asset('images/programs/'.$image['file']);
    }

    public static function banners(): array
    {
        return array_map(fn (array $image) => [
            ...$image, 'image_url' => self::url($image),
            'url' => route('campaigns.index', ['q' => $image['query']]),
        ], self::all());
    }
}
