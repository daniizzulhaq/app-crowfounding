<x-layouts.app title="Pilih campaign" heading="Mau donasi ke mana?" header="back" active="donate" :back="route('home')">
    <x-page-intro eyebrow="LANGKAH PERTAMA KEBAIKAN" title="Ke mana kita berbagi?" description="Setiap pilihan adalah harapan baru. Temukan program yang ingin kamu dukung." icon="hand-heart" />
    <div class="pt-0">
        @if ($nominal)
            <p class="mx-4 mb-3 flex items-center gap-2 rounded-xl bg-jade-soft px-3 py-2.5 text-sm text-jade" role="status">
                <x-glyph name="wallet" class="size-4" />
                <span>Nominal dipilih: <span class="money font-bold">{{ rupiah($nominal) }}</span>. Pilih campaign tujuannya.</span>
            </p>
        @endif

        {{-- Search --}}
        <form method="get" action="{{ route('donations.choose') }}" role="search" class="relative mx-4">
            @if ($nominal)<input type="hidden" name="nominal" value="{{ $nominal }}">@endif
            @if ($filters['kategori'])<input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">@endif
            <label for="q-choose" class="sr-only">Cari campaign</label>
            <x-glyph name="search" class="pointer-events-none absolute top-1/2 left-3 size-[18px] -translate-y-1/2 text-stone" />
            <input id="q-choose" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari campaign tujuan donasi"
                   class="h-11 w-full rounded-xl border border-line bg-white pr-3 pl-9 text-sm placeholder:text-stone focus:border-jade focus:ring-2 focus:ring-jade/20 focus:outline-none">
        </form>

        {{-- Category chips --}}
        <nav aria-label="Kategori" class="filter-chips mt-3 px-4 pb-1">
            <a href="{{ route('donations.choose', array_filter(['q' => $filters['q'], 'nominal' => $nominal])) }}"
               class="chip" @if (! $filters['kategori']) aria-current="page" @endif>Semua</a>
            @foreach ($categories as $category)
                <a href="{{ route('donations.choose', array_filter(['q' => $filters['q'], 'kategori' => $category->slug, 'nominal' => $nominal])) }}"
                   class="chip" @if ($filters['kategori'] === $category->slug) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>

        <div class="mt-3 space-y-3 px-4">
            {{-- General fund option (only when not filtering) --}}
            @if (! $filters['q'] && ! $filters['kategori'])
                <a href="{{ route('donations.create', array_filter(['nominal' => $nominal])) }}"
                   class="card flex items-center gap-3 border-jade/30 p-3 hover:border-jade">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-jade/10 bg-jade-soft text-jade">
                        <x-glyph name="hand-coins" class="size-7" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold">Sedekah umum</span>
                        <span class="block text-xs text-stone">Disalurkan ke program yang paling membutuhkan</span>
                    </span>
                    <span class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-jade-soft px-3 text-sm font-semibold text-jade">Pilih</span>
                </a>
                <p class="pt-2 text-xs font-semibold tracking-wide text-stone uppercase">Atau pilih campaign</p>
            @endif

            <section x-data="loadMore(@js($campaigns->nextPageUrl()))">
                @if ($campaigns->isEmpty())
                    <x-empty-state icon="search" title="Campaign tidak ditemukan"
                                   text="Coba kata kunci lain, atau donasi lewat sedekah umum." class="card">
                        <x-slot:action>
                            <a href="{{ route('donations.choose', array_filter(['nominal' => $nominal])) }}" class="btn btn-outline">Lihat semua campaign</a>
                        </x-slot:action>
                    </x-empty-state>
                @else
                    <ul x-ref="list" class="space-y-3">
                        @include('donations._choose-items')
                    </ul>
                    <x-load-more :next="$campaigns->nextPageUrl()" />
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
