@props(['fallback' => null, 'variant' => 'plain'])
@php $fallback ??= route('home'); @endphp
<a href="{{ $fallback }}"
   x-data
   @click.prevent="(document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) ? history.back() : (location.href = '{{ $fallback }}')"
   aria-label="Kembali"
   @class([
       'inline-flex size-11 items-center justify-center rounded-full',
       'text-ink hover:bg-line/60' => $variant === 'plain',
       'bg-white/90 text-ink shadow-[var(--shadow-soft)] backdrop-blur' => $variant === 'floating',
   ])>
    <x-glyph name="back" class="size-6" />
</a>
