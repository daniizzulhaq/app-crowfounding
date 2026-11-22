@props(['src' => null, 'alt' => '', 'icon' => 'heart', 'eager' => false])
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async"
         {{ $attributes->class('bg-line object-cover') }}>
@else
    {{-- Placeholder when no photo uploaded yet --}}
    <div role="img" aria-label="{{ $alt }}" {{ $attributes->class('flex items-center justify-center bg-jade-soft text-jade/50') }}>
        <x-glyph :name="$icon" class="size-1/4 max-h-12 max-w-12 min-h-6 min-w-6" />
    </div>
@endif
