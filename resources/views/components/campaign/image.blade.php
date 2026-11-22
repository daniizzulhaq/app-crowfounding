@props(['campaign', 'eager' => false, 'conversion' => 'card'])
@php
    $photo = $campaign->coverUrl($conversion);
    $subject = mb_strtolower($campaign->title.' '.$campaign->category?->name);
    $artwork = match (true) {
        str_contains($subject, 'masjid'), str_contains($subject, 'musala'), str_contains($subject, 'dakwah') => 'mosque',
        str_contains($subject, 'kesehatan'), str_contains($subject, 'operasi'), str_contains($subject, 'gizi') => 'health',
        str_contains($subject, 'pendidikan'), str_contains($subject, 'beasiswa'), str_contains($subject, 'santri') => 'education',
        str_contains($subject, 'pangan'), str_contains($subject, 'beras'), str_contains($subject, 'sembako') => 'food-aid',
        str_contains($subject, 'yatim'), str_contains($subject, 'dhuafa') => 'children',
        default => 'volunteers',
    };
@endphp
<div {{ $attributes->class('campaign-image relative overflow-hidden bg-jade-soft') }}>
    <img src="{{ $photo ?? asset('images/crowdfunding/'.$artwork.'.jpg') }}"
         alt="{{ $photo ? $campaign->title : 'Foto ilustrasi: '.$campaign->title }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" class="h-full w-full object-cover">
    @unless ($photo)
        <span class="illustration-label">Foto ilustrasi</span>
    @endunless
</div>
