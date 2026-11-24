@foreach ($campaigns as $campaign)
    <li class="card p-3">
        <div class="choose-campaign-main">
            <a href="{{ route('campaigns.show', $campaign) }}" class="shrink-0" aria-label="Lihat detail {{ $campaign->title }}">
                <x-campaign.image :campaign="$campaign" conversion="thumb" class="h-[100px] w-[104px] rounded-xl" />
            </a>
            <div class="flex min-w-0 flex-1 flex-col">
                <p class="text-[11px] font-semibold text-jade">{{ $campaign->category?->name }}</p>
                <h3 class="line-clamp-2 text-sm leading-5 font-bold">{{ $campaign->title }}</h3>
                <x-progress :value="$campaign->progress_percent" size="sm" class="mt-auto mb-1" />
                <p class="flex flex-wrap justify-between gap-2 text-xs text-stone">
                    <span class="money font-bold text-ink">{{ rupiah($campaign->collected_amount) }}</span>
                    <span>
                        @if ($campaign->days_left !== null)
                            <span class="money font-semibold text-ink">{{ $campaign->days_left }}</span> hari lagi
                        @else
                            Tanpa batas waktu
                        @endif
                    </span>
                </p>
            </div>
        </div>
        <a href="{{ route('campaigns.donate', array_filter(['campaign' => $campaign, 'nominal' => $nominal])) }}"
           class="btn btn-primary mt-3 w-full">Donasi ke campaign ini</a>
    </li>
@endforeach
