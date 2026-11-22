@props(['distribution'])
@php $photo = $distribution->getFirstMedia('photos'); @endphp
<a href="{{ route('distributions.show', $distribution) }}" class="distribution-card card block overflow-hidden">
    @if ($photo)
        <x-cover :src="$distribution->photoUrl($photo)" :alt="$distribution->title" class="aspect-[2/1] w-full" />
    @else
        <div class="distribution-placeholder">
            <x-glyph name="gift" class="size-7" />
            <span>Amanahmu, sudah sampai.</span>
            <x-glyph name="check-circle" class="ml-auto size-5" />
        </div>
    @endif
    <div class="p-4">
        <p class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone">
            <span class="inline-flex items-center gap-1"><x-glyph name="calendar" class="size-3.5" />{{ $distribution->distributed_at->translatedFormat('d M Y') }}</span>
            <span class="inline-flex items-center gap-1"><x-glyph name="pin" class="size-3.5" />{{ $distribution->location }}</span>
        </p>
        <h3 class="line-clamp-2 text-[15px] leading-relaxed font-bold">{{ $distribution->title }}</h3>
        <div class="mt-3 flex items-center justify-between border-t border-line pt-3 text-xs">
            <p class="text-stone">Dana tersalurkan <span class="money ml-1 font-bold text-jade">{{ rupiah($distribution->amount) }}</span></p>
            <span class="text-jade"><x-glyph name="chevron-right" class="size-4" /></span>
        </div>
    </div>
</a>
