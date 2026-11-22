<x-layouts.app title="Doa para donatur" heading="Doa para donatur" header="back" active="home">
    <x-page-intro eyebrow="DARI HATI YANG BAIK" title="Satu kebaikan, sejuta doa." description="Untaian doa dan harapan dari sahabat yang berbagi. Semoga Allah mengabulkan setiap niat baik." icon="heart" />
    <div class="px-5" x-data="loadMore(@js($prayers->nextPageUrl()))">
        @if ($prayers->isEmpty())
            <x-empty-state icon="heart" title="Belum ada doa" text="Doa dari donatur akan tampil di sini setelah donasi berhasil." class="card" />
        @else
            <div x-ref="list" class="space-y-3">
                @include('prayers._items')
            </div>
            <x-load-more :next="$prayers->nextPageUrl()" />
        @endif
    </div>
</x-layouts.app>
