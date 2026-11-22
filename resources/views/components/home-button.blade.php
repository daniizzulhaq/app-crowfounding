@props(['variant' => 'plain'])
<a href="{{ route('home') }}" aria-label="Kembali ke beranda" title="Beranda"
   @class([
       'inline-flex size-11 items-center justify-center rounded-full',
       'text-ink hover:bg-line/60' => $variant === 'plain',
       'bg-white/90 text-ink shadow-[var(--shadow-soft)] backdrop-blur' => $variant === 'floating',
   ])>
    <x-glyph name="home" class="size-[22px]" />
</a>
