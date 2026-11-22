@props(['next' => null, 'skeleton' => 'row'])
{{-- Works without JS (plain link); with JS it appends items & auto-loads on scroll. --}}
<div x-show="next" class="mt-2">
    <div x-ref="sentinel" aria-hidden="true"></div>
    <div x-show="loading" x-cloak class="space-y-4 py-2" aria-live="polite">
        <span class="sr-only">Memuat…</span>
        @for ($i = 0; $i < 2; $i++)
            <div class="flex gap-3">
                <div class="skeleton h-[84px] w-[112px] rounded-xl"></div>
                <div class="flex flex-1 flex-col gap-2 py-1">
                    <div class="skeleton h-4 w-11/12"></div>
                    <div class="skeleton h-4 w-2/3"></div>
                    <div class="skeleton mt-auto h-2 w-full"></div>
                </div>
            </div>
        @endfor
    </div>
    <p x-show="error" x-cloak class="py-2 text-center text-sm text-danger" role="alert">Gagal memuat. Coba lagi.</p>
    @if ($next)
        <a href="{{ $next }}" x-show="!loading" @click.prevent="load()" class="btn btn-outline w-full">Muat lagi</a>
    @endif
</div>
