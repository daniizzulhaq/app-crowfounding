@props(['donation', 'showCampaign' => true])
<article class="prayer-card card p-5">
    <div class="flex items-center gap-3">
        <div class="prayer-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($donation->display_name, 0, 1)) }}</div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold">{{ $donation->display_name }}</p>
            <p class="mt-0.5 text-xs text-stone">{{ $donation->paid_at?->diffForHumans() }}</p>
        </div>
        <x-glyph name="quote" class="size-6 text-jade/25" />
    </div>
    <p class="mt-4 text-sm leading-7 text-ink">“{{ $donation->prayer }}”</p>
    @if ($showCampaign && $donation->campaign)
        <a href="{{ route('campaigns.show', $donation->campaign) }}" class="mt-4 flex items-center gap-1.5 border-t border-line pt-3 text-xs font-semibold text-jade">
            <x-glyph name="heart" class="size-3.5" />{{ \Illuminate\Support\Str::limit($donation->campaign->title, 48) }}
        </a>
    @endif
</article>
