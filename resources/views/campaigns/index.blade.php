<x-layouts.app title="Cari campaign" :search="$filters['q']" active="home">
    <x-page-intro eyebrow="TEMUKAN KEBAIKANMU" title="Bersama, wujudkan harapan." description="Pilih cerita yang menyentuh hatimu. Jadilah bagian dari perubahan, mulai hari ini." icon="hand-heart" />
    <div class="pt-0">
        {{-- Category chips --}}
        <nav aria-label="Kategori" class="filter-chips px-4 pb-1">
            <a href="{{ route('campaigns.index', array_filter(['q' => $filters['q'], 'urut' => $filters['urut']])) }}"
               class="chip" @if (! $filters['kategori']) aria-current="page" @endif>Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('campaigns.index', array_filter(['q' => $filters['q'], 'kategori' => $category->slug, 'urut' => $filters['urut']])) }}"
                   class="chip" @if ($filters['kategori'] === $category->slug) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>

        {{-- Sort --}}
        <form method="get" action="{{ route('campaigns.index') }}" class="campaign-sort mt-3 px-4">
            @if ($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            @if ($filters['kategori'])<input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">@endif
            <p class="text-sm text-stone" aria-live="polite">
                @if ($filters['q'])
                    Hasil untuk “<span class="font-semibold text-ink">{{ $filters['q'] }}</span>”
                @else
                    Semua campaign
                @endif
            </p>
            <label class="sr-only" for="urut">Urutkan</label>
            <select id="urut" name="urut" onchange="this.form.submit()"
                    class="h-11 rounded-xl border border-line bg-white pr-8 pl-3 text-sm font-semibold focus:border-jade focus:outline-none">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($filters['urut'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-outline">Terapkan</button></noscript>
        </form>

        <section class="mt-3 px-4" x-data="loadMore(@js($campaigns->nextPageUrl()))">
            @if ($campaigns->isEmpty())
                <x-empty-state icon="search" title="Campaign tidak ditemukan"
                               text="Coba kata kunci lain atau pilih kategori yang berbeda." class="card">
                    <x-slot:action>
                        <a href="{{ route('campaigns.index') }}" class="btn btn-outline">Lihat semua campaign</a>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div x-ref="list" class="campaign-list">
                    @include('campaigns._items')
                </div>
                <x-load-more :next="$campaigns->nextPageUrl()" />
            @endif
        </section>
    </div>
</x-layouts.app>
