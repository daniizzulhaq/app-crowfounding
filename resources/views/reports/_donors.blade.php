@foreach ($donors as $donation)
    <li class="flex items-center gap-3 py-3">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-jade-soft text-sm font-bold text-jade" aria-hidden="true">
            {{ mb_strtoupper(mb_substr($donation->display_name, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold">{{ $donation->display_name }}</p>
            <p class="truncate text-xs text-stone">
                {{ $donation->campaign?->title ?? 'Sedekah umum' }} · {{ $donation->paid_at?->translatedFormat('d M') }}
            </p>
        </div>
        <p class="money shrink-0 text-sm font-bold text-ink">{{ rupiah($donation->amount) }}</p>
    </li>
@endforeach
