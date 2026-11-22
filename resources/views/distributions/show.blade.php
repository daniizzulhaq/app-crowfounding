@php
    $cover = $photos->first();
    $shareUrl = route('distributions.show', $distribution);
@endphp
<x-layouts.app :title="$distribution->title" :description="$distribution->excerpt" heading="Kabar penyaluran" header="back"
               :back="route('distributions.index')" :image="$cover ? $distribution->photoUrl($cover, 'large') : null" active="distributions">
    <x-slot:actions>
        <x-share-button :url="$shareUrl" :text="$distribution->title.' — '.$settings->foundationName()" class="text-ink hover:bg-line/60" />
    </x-slot:actions>

    <article class="pb-6">
        {{-- Gallery --}}
        @if ($photos->isNotEmpty())
            <div x-data="carousel({{ $photos->count() }})" x-init="pause()" class="bg-ink">
                <div x-ref="track" @scroll.debounce.80ms="onScroll()" class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto"
                     aria-label="Galeri foto penyaluran" tabindex="0">
                    @foreach ($photos as $photo)
                        <img src="{{ $distribution->photoUrl($photo, 'large') }}" alt="Foto penyaluran {{ $loop->iteration }}"
                             class="aspect-[4/3] w-full shrink-0 snap-center object-cover" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    @endforeach
                </div>
                @if ($photos->count() > 1)
                    <p class="money py-1.5 text-center text-xs font-semibold text-white/85" aria-live="polite">
                        <span x-text="index + 1">1</span> / {{ $photos->count() }}
                    </p>
                @endif
            </div>
        @else
            <div class="mx-5 mt-5 flex min-h-44 flex-col items-center justify-center gap-3 rounded-3xl bg-jade-soft text-jade">
                <x-glyph name="gift" class="size-10" />
                <p class="text-sm font-semibold">Setiap amanah, membawa manfaat.</p>
                <span class="text-xs text-stone">Dokumentasi foto belum tersedia</span>
            </div>
        @endif

        <div class="bg-white px-4 pt-4 pb-5">
            <h1 class="text-xl leading-snug font-extrabold">{{ $distribution->title }}</h1>
            <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <div class="col-span-2 rounded-xl bg-jade-soft px-3 py-2.5">
                    <dt class="text-xs text-jade">Nominal disalurkan</dt>
                    <dd class="money text-lg font-extrabold text-jade">{{ rupiah($distribution->amount) }}</dd>
                </div>
                <div class="rounded-xl bg-paper px-3 py-2.5">
                    <dt class="flex items-center gap-1 text-xs text-stone"><x-glyph name="calendar" class="size-3.5" /> Tanggal</dt>
                    <dd class="font-semibold">{{ $distribution->distributed_at->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="rounded-xl bg-paper px-3 py-2.5">
                    <dt class="flex items-center gap-1 text-xs text-stone"><x-glyph name="pin" class="size-3.5" /> Lokasi</dt>
                    <dd class="font-semibold">{{ $distribution->location }}</dd>
                </div>
                <div class="col-span-2 rounded-xl bg-paper px-3 py-2.5">
                    <dt class="flex items-center gap-1 text-xs text-stone"><x-glyph name="users" class="size-3.5" /> Penerima manfaat</dt>
                    <dd class="font-semibold">{{ $distribution->recipient }}</dd>
                </div>
            </dl>
        </div>

        <div class="prose-story mt-2 bg-white px-4 py-5">{!! \App\Support\Html::clean($distribution->body) !!}</div>

        <div class="px-4 pt-4">
            @if ($distribution->campaign && $distribution->campaign->isPubliclyVisible())
                <a href="{{ route('campaigns.show', $distribution->campaign) }}" class="card flex items-center justify-between gap-3 p-4 hover:border-jade/40">
                    <span class="min-w-0">
                        <span class="block text-xs text-stone">Dari campaign</span>
                        <span class="line-clamp-2 text-sm font-bold">{{ $distribution->campaign->title }}</span>
                    </span>
                    <x-glyph name="chevron-right" class="text-jade" />
                </a>
            @else
                <p class="card p-4 text-sm"><span class="text-stone">Sumber dana:</span> <span class="font-semibold">Dana umum (sedekah tanpa campaign)</span></p>
            @endif
        </div>
    </article>
</x-layouts.app>
