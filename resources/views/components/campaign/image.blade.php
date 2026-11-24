@props(['campaign', 'eager' => false, 'conversion' => 'card'])
@php
    $photo = $campaign->coverUrl($conversion);
@endphp
<div {{ $attributes->class('campaign-image relative overflow-hidden bg-jade-soft') }}>
    <img src="{{ $photo ?? $campaign->illustrationUrl() }}"
         alt="{{ $photo ? $campaign->title : 'Foto ilustrasi: '.$campaign->title }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" class="h-full w-full object-cover">
    @unless ($photo)
        <span class="illustration-label">Foto ilustrasi</span>
    @endunless
</div>
