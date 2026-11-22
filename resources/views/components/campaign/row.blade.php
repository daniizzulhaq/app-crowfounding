@props(['campaign'])
<a href="{{ route('campaigns.show', $campaign) }}" class="campaign-row">
    <x-campaign.image :campaign="$campaign" conversion="thumb" class="h-[116px] w-[112px] shrink-0 rounded-2xl" />
    <div class="flex min-w-0 flex-1 flex-col py-0.5">
        <p class="mb-1 text-xs font-semibold text-jade">{{ $campaign->category?->name ?? 'Kebaikan bersama' }}</p>
        <h3 class="line-clamp-2 text-sm leading-[1.5] font-bold">{{ $campaign->title }}</h3>
        <p class="money mt-2 mb-2 text-sm font-extrabold text-jade">{{ rupiah($campaign->collected_amount) }}</p>
        <x-progress :value="$campaign->progress_percent" size="sm" class="mt-auto" />
        <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-xs text-stone">
            <span>{{ number_format($campaign->donor_count, 0, ',', '.') }} donasi</span>
            @if ($campaign->status === \App\Enums\CampaignStatus::Completed)
                <span class="font-semibold text-jade">Selesai</span>
            @elseif ($campaign->days_left !== null)
                <span>{{ $campaign->days_left }} hari lagi</span>
            @else
                <span>Terus berjalan</span>
            @endif
        </div>
    </div>
</a>
