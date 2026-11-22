<x-layouts.app title="Donasi Saya" active="mine" :noindex="true">
    <x-page-intro eyebrow="JEJAK KEBAIKANMU" title="Berbagi meninggalkan arti." description="Terima kasih telah menjadi bagian dari harapan mereka. Pantau semua perjalanan donasimu di sini." icon="heart" />
    <div class="px-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">Donasi saya</h2>
                @if ($maskedPhone)
                    <p class="money text-sm text-stone">{{ $maskedPhone }}</p>
                @else
                    <p class="text-sm text-stone">Donasi yang dibuat dari HP ini</p>
                @endif
            </div>
            @if ($maskedPhone)
                <form method="post" action="{{ route('my-donations.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost -mr-2 px-3 text-sm"><x-glyph name="logout" class="size-4" /> Keluar</button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <p class="mt-3 rounded-xl bg-jade-soft px-4 py-3 text-sm font-semibold text-jade" role="status">{{ session('status') }}</p>
        @endif

        <div class="mt-3 rounded-2xl bg-jade px-4 py-3 text-white">
            <p class="text-xs text-white/85">Total donasi berhasil</p>
            <p class="money text-xl font-extrabold">{{ rupiah($totalPaid) }}</p>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($donations as $donation)
                <a href="{{ route('donations.status', $donation->order_id) }}" class="card block p-4 hover:border-jade/40">
                    <div class="flex items-start justify-between gap-3">
                        <p class="line-clamp-2 text-sm font-bold">{{ $donation->target_label }}</p>
                        <x-status-badge :status="$donation->status" />
                    </div>
                    <div class="mt-2 flex items-end justify-between text-xs text-stone">
                        <span>{{ $donation->created_at->translatedFormat('d M Y, H:i') }}<br><span class="money">{{ $donation->order_id }}</span></span>
                        <span class="money text-base font-bold text-ink">{{ rupiah($donation->amount) }}</span>
                    </div>
                </a>
            @empty
                <x-empty-state icon="heart" title="Belum ada donasi" text="Belum ada donasi dengan nomor ini. Yuk mulai berbagi hari ini." class="card">
                    <x-slot:action><a href="{{ route('donations.choose') }}" class="btn btn-primary">Donasi sekarang</a></x-slot:action>
                </x-empty-state>
            @endforelse
        </div>

        @if ($donations->hasPages())
            <div class="mt-4">{{ $donations->links('pagination::simple-tailwind') }}</div>
        @endif

        @unless ($maskedPhone)
            <a href="{{ route('my-donations.phone') }}" class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm hover:bg-paper">
                <span><span class="font-semibold">Pernah donasi dari HP lain?</span><br><span class="text-xs text-stone">Lihat semua riwayat lewat verifikasi WhatsApp</span></span>
                <x-glyph name="chevron-right" class="text-jade" />
            </a>
        @endunless
    </div>
</x-layouts.app>
