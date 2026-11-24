@props(['campaign', 'eager' => false, 'conversion' => 'card'])
@php
    $photo = $campaign->coverUrl($conversion);
    $program = $photo ? null : $campaign->programImage();
@endphp
<div {{ $attributes->class(['campaign-image relative overflow-hidden', 'bg-jade-soft' => (bool) $photo, 'campaign-program-image bg-[#031326]' => ! $photo]) }}
     @if ($program) style="aspect-ratio: {{ $program['width'] }} / {{ $program['height'] }}" @endif>
    <img src="{{ $photo ?? $campaign->programCoverUrl() }}"
         alt="{{ $photo ? $campaign->title : 'Program Al Kirom: '.$program['title'] }}"
         loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
         @class(['h-full w-full', 'object-cover' => (bool) $photo, 'object-contain' => ! $photo])>
</div>
