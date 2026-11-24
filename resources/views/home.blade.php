<x-layouts.app active="home">
    <div class="home-content">
        <section class="px-5 pt-2" aria-labelledby="hero-title">
            <div class="home-greeting">
                <p>Assalamu’alaikum, <strong>Sahabat baik</strong></p>
                <x-glyph name="sprout" class="size-4 text-jade" />
            </div>
            <p class="eyebrow mb-1 text-jade">KEBAIKAN DARI HATI</p>
            <h1 id="hero-title" class="program-hero-title">Kebaikan kecil. Harapan besar.</h1>
            @php $programImages = \App\Support\ProgramImages::banners(); @endphp
            <div class="program-gallery" x-data="carousel(4)" @pointerdown="pause()" @focusin="pause()" aria-label="Program Al Kirom">
                <div class="program-gallery-card">
                    <div x-ref="track" @scroll.debounce.80ms="onScroll()" class="program-hero-track no-scrollbar">
                        @foreach ($programImages as $program)
                            <a href="{{ $program['url'] }}" class="program-hero-slide" aria-label="Lihat campaign {{ $program['title'] }}">
                                <img src="{{ $program['image_url'] }}" alt="Program Al Kirom: {{ $program['title'] }}"
                                     width="{{ $program['width'] }}" height="{{ $program['height'] }}"
                                     loading="{{ $loop->first ? 'eager' : 'lazy' }}" @if ($loop->first) fetchpriority="high" @endif decoding="async">
                            </a>
                        @endforeach
                    </div>
                    <div class="program-gallery-caption">
                        <strong x-text="{{ Js::from(array_column($programImages, 'title')) }}[index]">{{ $programImages[0]['title'] }}</strong>
                        <a href="{{ $programImages[0]['url'] }}" :href="{{ Js::from(array_column($programImages, 'url')) }}[index]">Lihat campaign <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>
                <div class="program-gallery-dots" aria-label="Pilih program">
                    @foreach ($programImages as $program)
                        <button type="button" @click="pause(); go({{ $loop->index }})" aria-label="Tampilkan {{ $program['title'] }}" :aria-pressed="(index === {{ $loop->index }}).toString()">
                            <span :class="{ 'is-active': index === {{ $loop->index }} }" @class(['is-active' => $loop->first])></span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="home-trust">
                <span><x-glyph name="shield" /> Donasi aman</span>
                <span><x-glyph name="report" /> Laporan terbuka</span>
                <span><x-glyph name="hand-heart" /> Tepat manfaat</span>
            </div>
        </section>

        <section class="category-section px-5" aria-labelledby="category-title">
            <div class="section-heading">
                <h2 id="category-title">Mau berbagi untuk apa?</h2>
            </div>
            <nav aria-label="Kategori bantuan" class="category-grid">
                @php
                    $categoryStyles = [
                        'pendidikan' => ['icon' => 'education', 'color' => 'mint'],
                        'kesehatan' => ['icon' => 'health', 'color' => 'rose'],
                        'bencana-alam' => ['icon' => 'hand-heart', 'color' => 'amber'],
                        'pangan' => ['icon' => 'food', 'color' => 'peach'],
                        'masjid-dakwah' => ['icon' => 'book', 'color' => 'lavender'],
                        'yatim-dhuafa' => ['icon' => 'users', 'color' => 'blue'],
                    ];
                @endphp
                @foreach ($categories->take(5) as $category)
                    @php $style = $categoryStyles[$category->slug] ?? ['icon' => $category->icon ?: 'heart', 'color' => 'mint']; @endphp
                    <a href="{{ route('campaigns.index', ['kategori' => $category->slug]) }}" class="category-link">
                        <span class="category-icon {{ $style['color'] }}"><x-glyph :name="$style['icon']" class="size-6" /></span>
                        <span>{{ $category->name === 'Masjid & Dakwah' ? 'Wakaf & dakwah' : $category->name }}</span>
                    </a>
                @endforeach
                <a href="{{ route('campaigns.index') }}" class="category-link">
                    <span class="category-icon neutral"><x-glyph name="grid" class="size-6" /></span>
                    <span>Lihat semua</span>
                </a>
            </nav>
        </section>

        <section class="px-5" aria-labelledby="quick-title">
            <form action="{{ route('donations.choose') }}" method="get" class="quick-donate"
                  x-data="quickDonate(25000, {{ config('alkiroma.donation.min_amount') }})">
                <div class="quick-heading">
                    <span class="quick-icon"><x-glyph name="heart-handshake" class="size-6" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 id="quick-title">Sedekah cepat</h2>
                        <p>Niat baikmu, tak perlu menunggu.</p>
                    </div>
                    <span class="quick-badge">Bismillah</span>
                </div>
                <div class="grid grid-cols-4 gap-2" role="group" aria-label="Pilih nominal">
                    @foreach (config('alkiroma.donation.quick_amounts') as $amount)
                        <button type="button" class="quick-amount money" @click="pick({{ $amount }})"
                                :aria-pressed="(amount === {{ $amount }} && !custom).toString()">
                            <span>Rp</span>{{ number_format($amount, 0, ',', '.') }}
                        </button>
                    @endforeach
                </div>
                <div class="quick-custom-wrap" x-data="{ showCustom: false }">
                    <button type="button" class="quick-other" @click="showCustom = !showCustom; if(showCustom) $nextTick(() => $refs.customInput.focus())" :aria-expanded="showCustom.toString()" aria-controls="custom-amount-wrap">
                        <span>Atau masukkan nominal lain</span><x-glyph name="chevron-down" class="size-4" x-bind:class="showCustom && 'rotate-180'" />
                    </button>
                    <div id="custom-amount-wrap" x-show="showCustom" x-collapse x-cloak>
                        <label for="quick-custom" class="sr-only">Nominal lain</label>
                        <div class="relative mb-3">
                            <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-semibold text-stone">Rp</span>
                            <input id="quick-custom" x-ref="customInput" type="text" inputmode="numeric" placeholder="Isi nominal donasimu" autocomplete="off"
                                   :value="custom" @input="onCustom($event)" class="field money pl-11">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="nominal" :value="amount" value="25000">
                <button type="submit" class="btn btn-primary w-full" :disabled="!valid">
                    <x-glyph name="hand-heart" class="size-[18px]" />
                    <span x-text="valid ? 'Sedekah sekarang' : 'Minimal {{ rupiah(config('alkiroma.donation.min_amount')) }}'">Sedekah sekarang</span>
                </button>
                <a :href="'{{ route('donations.create') }}?nominal=' + amount" href="{{ route('donations.create', ['nominal' => 25000]) }}" class="quick-general">Langsung sedekah umum</a>
                <p class="quick-footnote"><x-glyph name="shield" class="size-3" /> Aman, mudah, dan penuh makna</p>
            </form>
        </section>

        @if ($featured->isNotEmpty())
            <section aria-labelledby="featured-title">
                <div class="px-5">
                    <p class="eyebrow mb-1 text-jade">PILIH KEBAIKANMU</p>
                    <x-section-title title="Bantu wujudkan harapan" :href="route('campaigns.index')" id="featured-title" />
                    <p class="section-description">Sedikit darimu, begitu berarti bagi mereka.</p>
                </div>
                <div class="campaign-rail">
                    @foreach ($featured as $campaign)
                        <x-campaign.card :campaign="$campaign" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="px-5" aria-labelledby="latest-title">
            <x-section-title title="Kebaikan yang menantimu" :href="route('campaigns.index')" id="latest-title" />
            <div class="campaign-list">
                @forelse ($latest as $campaign)
                    <x-campaign.row :campaign="$campaign" />
                @empty
                    <x-empty-state icon="heart" title="Belum ada campaign aktif" text="Program kebaikan berikutnya sedang disiapkan. Kamu tetap bisa berbagi melalui sedekah umum." class="card">
                        <x-slot:action><a href="{{ route('donations.create') }}" class="btn btn-primary">Sedekah umum</a></x-slot:action>
                    </x-empty-state>
                @endforelse
            </div>
            @if ($latest->isNotEmpty())
                <a href="{{ route('campaigns.index') }}" class="btn btn-outline mt-4 w-full">Temukan lebih banyak kebaikan</a>
            @endif
        </section>

        @if ($banners->isNotEmpty())
            <section class="px-5" aria-label="Sorotan program" x-data="carousel({{ $banners->count() }})" @pointerdown="pause()" @focusin="pause()">
                <div x-ref="track" @scroll.debounce.80ms="onScroll()" class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto rounded-3xl">
                    @foreach ($banners as $banner)
                        @php $link = $banner->link(); @endphp
                        <div class="w-full shrink-0 snap-center">
                            @if ($link)<a href="{{ $link }}" class="block">@endif
                                @if ($banner->imageUrl())
                                    <x-cover :src="$banner->imageUrl()" :alt="$banner->title" class="aspect-[2/1] w-full" />
                                @else
                                    <div class="program-banner">
                                        <span class="eyebrow">KEBAIKAN PILIHAN</span>
                                        <h3>{{ $banner->title }}</h3>
                                        <span class="inline-flex items-center gap-2 text-sm font-semibold"><x-glyph name="hand-heart" class="size-4" /> Yuk, ambil bagian</span>
                                        <x-glyph name="sprout" class="program-banner-icon" />
                                    </div>
                                @endif
                            @if ($link)</a>@endif
                        </div>
                    @endforeach
                </div>
                @if ($banners->count() > 1)
                    <div class="mt-3 flex justify-center gap-1">
                        @foreach ($banners as $banner)
                            <button type="button" @click="pause(); go({{ $loop->index }})" class="flex h-8 w-8 items-center justify-center" aria-label="Tampilkan sorotan {{ $loop->iteration }}">
                                <span class="block h-1.5 rounded-full transition-all" :class="index === {{ $loop->index }} ? 'w-6 bg-jade' : 'w-1.5 bg-line'"></span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="impact-section mx-5" aria-labelledby="impact-title">
            <div class="flex items-center gap-2 text-jade"><x-glyph name="heart-handshake" class="size-5" /><p class="eyebrow">BERSAMA, KITA BISA</p></div>
            <h2 id="impact-title">Kebaikanmu tumbuh<br>menjadi manfaat.</h2>
            <p>Setiap rupiah adalah amanah. Ikuti perjalanan donasimu hingga sampai kepada penerima.</p>
            <a href="{{ route('reports.index') }}" class="btn btn-outline mt-4"><x-glyph name="report" class="size-4" /> Lihat laporan kebaikan</a>
            <x-glyph name="sprout" class="impact-decoration" />
        </section>

        <section class="px-5" aria-labelledby="dist-title">
            <p class="eyebrow mb-1 text-jade">CERITA DARI LAPANGAN</p>
            <x-section-title title="Amanah yang tersampaikan" :href="route('distributions.index')" id="dist-title" />
            <div class="space-y-4">
                @forelse ($distributions->take(2) as $distribution)
                    <x-distribution.card :distribution="$distribution" />
                @empty
                    <x-empty-state icon="gift" title="Belum ada kabar penyaluran" text="Cerita dan laporan penyaluran donasimu akan hadir di sini." class="card" />
                @endforelse
            </div>
        </section>

            <section aria-labelledby="prayer-title">
                <div class="px-5">
                    <x-section-title title="Doa baik, dari hati" :href="route('prayers.index')" id="prayer-title" />
                    <p class="section-description">Semoga setiap harapan baik menemukan jalannya.</p>
                </div>
                <div class="prayer-rail">
                    @forelse ($prayers as $donation)
                        <x-prayer-card :donation="$donation" />
                    @empty
                        <p class="card p-5 text-sm text-stone">Doa dari donatur akan tampil di sini setelah donasi berhasil.</p>
                    @endforelse
                </div>
            </section>

        <x-site-footer />
    </div>
</x-layouts.app>
