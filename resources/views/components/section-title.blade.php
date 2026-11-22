@props(['title', 'href' => null, 'linkLabel' => 'Lihat semua'])
<div {{ $attributes->class('section-heading') }}>
    <h2>{{ $title }}</h2>
    @if ($href)
        <a href="{{ $href }}">{{ $linkLabel }}</a>
    @endif
</div>
