<x-layouts.app title="Donasi Saya" active="mine" :noindex="true">
    <x-page-intro eyebrow="JEJAK KEBAIKANMU" title="Terima kasih sudah peduli." description="Simpan cerita baikmu di sini. Riwayat donasi dari HP ini muncul otomatis setelah kamu berbagi." icon="heart" />
    <div class="px-5">
        <h2 class="text-lg font-extrabold">Donasi saya</h2>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-jade-soft px-4 py-3 text-sm font-semibold text-jade" role="status">{{ session('status') }}</p>
        @endif

        <x-empty-state icon="heart" title="Belum ada donasi dari HP ini" text="Setelah Anda berdonasi, riwayatnya langsung tersimpan di sini." class="card mt-4">
            <x-slot:action><a href="{{ route('donations.choose') }}" class="btn btn-primary">Donasi sekarang</a></x-slot:action>
        </x-empty-state>

        <a href="{{ route('my-donations.phone') }}" class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm hover:bg-paper">
            <span><span class="font-semibold">Pernah donasi dari HP lain?</span><br><span class="text-xs text-stone">Lihat riwayat lewat verifikasi WhatsApp</span></span>
            <x-glyph name="chevron-right" class="text-jade" />
        </a>
    </div>
</x-layouts.app>
