@props(['url', 'text', 'variant' => 'icon'])
<div x-data="share(@js($url), @js($text))" class="contents">
    @if ($variant === 'icon')
        <button type="button" @click="share()" aria-label="Bagikan"
                {{ $attributes->class('inline-flex size-11 items-center justify-center rounded-full') }}>
            <x-glyph name="share" class="size-5" />
        </button>
    @else
        <button type="button" @click="share()" {{ $attributes->class('btn btn-outline') }}>
            <x-glyph name="share" /> {{ $slot->isEmpty() ? 'Bagikan' : $slot }}
        </button>
    @endif

    {{-- Bottom sheet fallback (desktop / no Web Share API) --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="share-title"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-ink/40" x-show="open" x-transition.opacity @click="open = false"></div>
            <div class="absolute inset-x-0 bottom-0 mx-auto w-full max-w-[480px] rounded-t-3xl bg-white px-4 pt-3 pb-safe"
                 x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-full"
                 x-trap.noscroll="open">
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-line" aria-hidden="true"></div>
                <h2 id="share-title" class="mb-3 text-base font-bold">Bagikan kebaikan</h2>
                <div class="grid gap-2 pb-2">
                    <a :href="waUrl" target="_blank" rel="noopener" class="btn btn-primary w-full">
                        <x-glyph name="whatsapp" /> Bagikan ke WhatsApp
                    </a>
                    <button type="button" @click="copy()" class="btn btn-outline w-full">
                        <x-glyph name="copy" /> <span x-text="copied ? 'Tautan tersalin' : 'Salin tautan'">Salin tautan</span>
                    </button>
                    <button type="button" @click="open = false" class="btn btn-ghost w-full">Tutup</button>
                </div>
            </div>
        </div>
    </template>
</div>
