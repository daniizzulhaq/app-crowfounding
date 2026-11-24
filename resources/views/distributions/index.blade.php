<x-layouts.app title="Penyaluran" active="distributions">
    <x-page-intro eyebrow="KABAR KEBAIKAN" title="Amanahmu, sudah sampai." description="Lihat bagaimana kebaikanmu menghadirkan manfaat. Inilah cerita dan kabar penyaluran dari lapangan." icon="gift" />
    <div>
        <section class="px-4">
            <h2 class="text-lg font-extrabold">Kabar penyaluran</h2>
            <div class="mt-3 flex items-center gap-3 rounded-2xl bg-jade-soft px-5 py-4 text-jade">
                <x-glyph name="gift" class="size-6" />
                <div>
                    <p class="text-xs text-jade">Total telah disalurkan</p>
                    <p class="money text-lg font-extrabold">{{ rupiah($totalDistributed) }}</p>
                </div>
            </div>
        </section>

        @if ($campaign)
            <div class="mx-4 mt-3 flex items-center justify-between gap-2 rounded-xl bg-white px-3 py-2 text-sm">
                <span class="min-w-0 truncate">Campaign: <span class="font-semibold">{{ $campaign->title }}</span></span>
                <a href="{{ route('distributions.index') }}" class="inline-flex min-h-11 shrink-0 items-center gap-1 font-semibold text-jade">
                    <x-glyph name="x" class="size-4" /> Hapus filter
                </a>
            </div>
        @endif

        <nav aria-label="Filter kategori" class="filter-chips mt-3 px-4 pb-1">
            <a href="{{ route('distributions.index', array_filter(['campaign' => $campaign?->slug])) }}" class="chip" @if (! $categorySlug) aria-current="page" @endif>Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('distributions.index', array_filter(['campaign' => $campaign?->slug, 'kategori' => $category->slug])) }}"
                   class="chip" @if ($categorySlug === $category->slug) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>

        <section class="mt-3 px-4" x-data="loadMore(@js($distributions->nextPageUrl()))">
            @if ($distributions->isEmpty())
                <x-empty-state icon="gift" title="Belum ada kabar penyaluran" text="Kabar penyaluran untuk filter ini belum tersedia." class="card" />
            @else
                <div x-ref="list" class="space-y-3">
                    @include('distributions._items')
                </div>
                <x-load-more :next="$distributions->nextPageUrl()" />
            @endif
        </section>
    </div>
</x-layouts.app>
