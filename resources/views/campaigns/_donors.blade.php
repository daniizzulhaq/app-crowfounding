@foreach ($donors as $donation)
    <li class="py-3">
        <div class="flex items-center gap-3">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-jade-soft text-sm font-bold text-jade" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($donation->display_name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold">{{ $donation->display_name }}</p>
                <p class="text-xs text-stone">
                    Berdonasi <span class="money font-semibold text-ink">{{ rupiah($donation->amount) }}</span>
                    · {{ $donation->paid_at?->diffForHumans() }}
                </p>
            </div>
        </div>
        @if (filled($donation->prayer) && ! $donation->prayer_hidden)
            <p class="mt-2 ml-12 rounded-xl bg-paper px-3 py-2 text-sm leading-relaxed">“{{ $donation->prayer }}”</p>
        @endif
    </li>
@endforeach
