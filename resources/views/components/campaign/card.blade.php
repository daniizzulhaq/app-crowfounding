@props(['campaign'])
<a href="{{ route('campaigns.show', $campaign) }}" class="campaign-card card flex shrink-0 snap-start flex-col overflow-hidden">
    <div class="relative aspect-[16/10]">
        <x-campaign.image :campaign="$campaign" class="h-full w-full" />
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="mb-2 text-xs font-semibold text-jade">{{ $campaign->category?->name ?? 'Kebaikan bersama' }}</p>
        <p class="campaign-organizer"><x-glyph name="badge" class="size-3.5" /> {{ $settings->foundationName() }}</p>
        <h3 class="mt-2 mb-4 line-clamp-2 min-h-11 text-[15px] leading-[1.5] font-bold">{{ $campaign->title }}</h3>
        <div class="mt-auto">
            <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                <p class="money text-[15px] font-extrabold text-jade">{{ rupiah($campaign->collected_amount) }}</p>
                <p class="money text-xs font-semibold text-stone">{{ $campaign->progress_percent }}%</p>
            </div>
            <x-progress :value="$campaign->progress_percent" size="sm" />
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-stone">
                <p><span class="money font-bold text-ink">{{ number_format($campaign->donor_count, 0, ',', '.') }}</span> donasi</p>
                <p class="inline-flex items-center gap-1"><x-glyph name="clock" class="size-3" />
                    {{ $campaign->days_left !== null ? $campaign->days_left.' hari lagi' : 'Terus berjalan' }}
                </p>
            </div>
        </div>
    </div>
</a>
