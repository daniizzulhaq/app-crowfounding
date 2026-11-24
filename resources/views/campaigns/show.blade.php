<x-layouts.app :title="$campaign->title" :description="$campaign->summary ?? $campaign->story"
               :image="$campaign->coverUrl('card') ?? $campaign->programCoverUrl()" header="none" :nav="false">
    @php
        $shareUrl = route('campaigns.show', $campaign);
        $shareText = 'Yuk ikut berdonasi untuk "'.$campaign->title.'" bersama '.$settings->foundationName().'.';
        $accepts = $campaign->acceptsDonations();
    @endphp

    {{-- Cover + floating actions --}}
    <div class="relative">
        <x-campaign.image :campaign="$campaign" :eager="true" class="campaign-detail-cover aspect-[4/3] w-full" />
        <div class="absolute inset-x-0 top-0 flex items-center justify-between p-3">
            <x-back-button :fallback="route('campaigns.index')" variant="floating" />
            <div class="flex items-center gap-2">
                <x-home-button variant="floating" />
                <x-share-button :url="$shareUrl" :text="$shareText"
                                class="bg-white/90 text-ink shadow-[var(--shadow-soft)] backdrop-blur" />
            </div>
        </div>
    </div>

    @if (session('notice'))
        <p class="mx-4 mt-3 rounded-xl bg-marigold-soft px-4 py-3 text-sm font-semibold text-ink" role="status">{{ session('notice') }}</p>
    @endif

    {{-- Summary --}}
    <section @class(['campaign-detail-summary bg-white px-5 pt-6 pb-5', 'campaign-detail-summary--program' => ! $campaign->coverUrl()])>
        <a href="{{ route('campaigns.index', ['kategori' => $campaign->category?->slug]) }}"
           class="inline-flex rounded-full bg-jade-soft px-3 py-1 text-xs font-semibold text-jade">{{ $campaign->category?->name }}</a>
        <h1 class="mt-2 text-xl leading-snug font-extrabold text-ink">{{ $campaign->title }}</h1>

        <div class="mt-4">
            <p class="text-xs text-stone">Terkumpul</p>
            <p class="money text-2xl font-extrabold text-jade">{{ rupiah($campaign->collected_amount) }}</p>
            <p class="text-sm text-stone">dari target <span class="money font-semibold text-ink">{{ rupiah($campaign->target_amount) }}</span></p>
        </div>
        <x-progress :value="$campaign->progress_percent" size="lg" class="mt-3" />

        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-paper px-2 py-2.5">
                <dt class="text-[11px] text-stone">Donatur</dt>
                <dd class="money text-base font-bold">{{ number_format($campaign->donor_count, 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-xl bg-paper px-2 py-2.5">
                <dt class="text-[11px] text-stone">Sisa hari</dt>
                <dd class="money text-base font-bold">
                    @if ($campaign->status === \App\Enums\CampaignStatus::Completed) Selesai
                    @elseif ($campaign->days_left === null) ∞
                    @else {{ $campaign->days_left }}
                    @endif
                </dd>
            </div>
            <div class="rounded-xl bg-paper px-2 py-2.5">
                <dt class="text-[11px] text-stone">Tersalurkan</dt>
                <dd class="money text-base font-bold">{{ \App\Support\Rupiah::short($campaign->distributed_amount) }}</dd>
            </div>
        </dl>
        <div class="campaign-detail-organizer">
            <img src="{{ $settings->logoUrl() }}" alt="Logo Al Kirom Depok" width="34" height="39">
            <div><p>Penggalang dana</p><strong>{{ $settings->foundationName() }} <x-glyph name="badge" class="size-4 text-jade" /></strong></div>
        </div>
    </section>

    {{-- Tabs --}}
    <section class="mt-2 bg-white" x-data="{ tab: 'cerita' }">
        <div role="tablist" aria-label="Informasi campaign" class="sticky top-0 z-20 grid grid-cols-3 border-b border-line bg-white">
            @foreach (['cerita' => 'Cerita', 'penyaluran' => 'Penyaluran', 'donatur' => 'Donatur & Doa'] as $key => $label)
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                        :aria-selected="(tab === '{{ $key }}').toString()" :tabindex="tab === '{{ $key }}' ? 0 : -1"
                        @click="tab = '{{ $key }}'"
                        class="relative min-h-12 px-2 text-sm font-semibold"
                        :class="tab === '{{ $key }}' ? 'text-jade' : 'text-stone'">
                    {{ $label }}
                    <span class="absolute inset-x-4 bottom-0 h-0.5 rounded-full" :class="tab === '{{ $key }}' ? 'bg-jade' : 'bg-transparent'"></span>
                </button>
            @endforeach
        </div>

        {{-- Cerita --}}
        <div id="panel-cerita" role="tabpanel" aria-labelledby="tab-cerita" x-show="tab === 'cerita'" class="px-4 py-5"
             x-data="{ expanded: false }">
            <div x-show="expanded" x-collapse.min.220px class="relative">
                {{-- Admin rich-editor HTML, sanitized as defense in depth. --}}
                <div class="prose-story">{!! \App\Support\Html::clean($campaign->story) !!}</div>
                <div x-show="!expanded" class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-white to-transparent"></div>
            </div>
            <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()"
                    class="btn btn-ghost mt-2 w-full">
                <span x-text="expanded ? 'Tutup cerita' : 'Baca selengkapnya'">Baca selengkapnya</span>
                <x-glyph name="chevron-down" class="size-4 transition-transform" x-bind:class="expanded && 'rotate-180'" />
            </button>
        </div>

        {{-- Penyaluran --}}
        <div id="panel-penyaluran" role="tabpanel" aria-labelledby="tab-penyaluran" x-show="tab === 'penyaluran'" x-cloak class="px-4 py-5">
            <div class="mb-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-jade-soft px-3 py-2.5">
                    <p class="text-xs text-jade">Tersalurkan</p>
                    <p class="money font-bold text-jade">{{ rupiah($campaign->distributed_amount) }}</p>
                </div>
                <div class="rounded-xl bg-paper px-3 py-2.5">
                    <p class="text-xs text-stone">Saldo belum disalurkan</p>
                    <p class="money font-bold">{{ rupiah($campaign->balance) }}</p>
                </div>
            </div>
            <div class="space-y-3">
                @forelse ($distributions as $distribution)
                    <x-distribution.card :distribution="$distribution" />
                @empty
                    <x-empty-state icon="gift" title="Belum ada penyaluran" text="Kabar penyaluran dana campaign ini akan kami unggah di sini." />
                @endforelse
            </div>
            @if ($distributions->count() >= 5)
                <a href="{{ route('distributions.index', ['campaign' => $campaign->slug]) }}" class="btn btn-outline mt-3 w-full">Lihat semua penyaluran</a>
            @endif
        </div>

        {{-- Donatur & Doa --}}
        <div id="panel-donatur" role="tabpanel" aria-labelledby="tab-donatur" x-show="tab === 'donatur'" x-cloak class="px-4 py-3"
             x-data="loadMore(@js($donors->nextPageUrl() ? route('campaigns.donors', [$campaign, 'p' => 2]) : null))">
            @if ($donors->isEmpty())
                <x-empty-state icon="users" title="Jadilah donatur pertama" text="Kebaikan Anda bisa jadi pembuka jalan bagi yang lain." />
            @else
                <p class="py-2 text-sm text-stone"><span class="money font-semibold text-ink">{{ number_format($campaign->donor_count, 0, ',', '.') }}</span> donasi · <span class="money font-semibold text-ink">{{ $prayerCount }}</span> doa</p>
                <ul x-ref="list" class="divide-y divide-line">
                    @include('campaigns._donors')
                </ul>
                <x-load-more :next="$donors->nextPageUrl() ? route('campaigns.donors', [$campaign, 'p' => 2]) : null" />
            @endif
        </div>
    </section>

    <x-slot:sticky>
        <div class="flex items-center gap-3">
            <x-share-button :url="$shareUrl" :text="$shareText" variant="button" class="w-auto px-4" aria-label="Bagikan campaign">
                <span class="sr-only">Bagikan</span>
            </x-share-button>
            @if ($accepts)
                <a href="{{ route('campaigns.donate', $campaign) }}" class="btn btn-primary flex-1">Donasi sekarang</a>
            @else
                <span class="btn flex-1 bg-line text-stone" aria-disabled="true">Donasi sudah ditutup</span>
            @endif
        </div>
    </x-slot:sticky>
</x-layouts.app>
