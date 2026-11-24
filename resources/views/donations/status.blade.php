@php
    use App\Enums\DonationStatus;
    $status = $donation->status;
    $statusUrl = route('donations.status', $donation->order_id);
    $target = $donation->campaign?->title ?? 'Sedekah umum';
    $shareUrl = $donation->campaign ? route('campaigns.show', $donation->campaign) : route('home');
    $shareText = 'Alhamdulillah, saya baru saja berdonasi untuk "'.$target.'" lewat '.$settings->foundationName().'. Yuk ikut berbagi kebaikan!';
    $canResume = $status === DonationStatus::Pending && $donation->snap_token && $donation->expires_at?->isFuture();
    $ui = match ($status) {
        DonationStatus::Paid => ['icon' => 'check-circle', 'tone' => 'bg-jade-soft text-jade', 'title' => 'Donasi berhasil', 'text' => 'Jazakallahu khairan. Donasi Anda sudah kami terima.'],
        DonationStatus::Pending => ['icon' => 'hourglass', 'tone' => 'bg-marigold-soft text-ink', 'title' => 'Menunggu pembayaran', 'text' => 'Selesaikan pembayaran sebelum batas waktu. Halaman ini akan diperbarui otomatis.'],
        DonationStatus::Failed => ['icon' => 'x-circle', 'tone' => 'bg-danger-soft text-danger', 'title' => 'Pembayaran gagal', 'text' => 'Pembayaran tidak berhasil diproses. Anda bisa mencoba lagi.'],
        DonationStatus::Expired => ['icon' => 'clock', 'tone' => 'bg-paper text-stone', 'title' => 'Waktu pembayaran habis', 'text' => 'Batas waktu pembayaran sudah lewat. Silakan buat donasi baru.'],
    };
@endphp
<x-layouts.app title="Status donasi" heading="Status donasi" header="back" :nav="false" :noindex="true" :back="route('home')">
    @if ($canResume)
        <x-slot:head>
            <script src="{{ config('midtrans.snap_js') }}" data-client-key="{{ config('midtrans.client_key') }}" defer></script>
        </x-slot:head>
    @endif

    <div class="space-y-3 px-4 pt-4 pb-8" x-data="statusPoller(@js(route('donations.status.json', $donation->order_id)), @js($status->value))">
        <section class="card overflow-hidden text-center" aria-live="polite">
            <div class="px-5 pt-7 pb-5">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full {{ $ui['tone'] }}">
                    <x-glyph :name="$ui['icon']" class="size-8" />
                </div>
                <h1 class="mt-3 text-lg font-extrabold">{{ $ui['title'] }}</h1>
                <p class="mt-1 text-sm text-stone">{{ $ui['text'] }}</p>
                <p class="money mt-4 text-2xl font-extrabold text-jade">{{ rupiah($donation->amount) }}</p>
                <p class="text-sm text-stone">untuk <span class="font-semibold text-ink">{{ $target }}</span></p>
            </div>
            <dl class="receipt-details divide-y divide-line border-t border-line text-left text-sm">
                <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-stone">No. donasi</dt><dd class="money font-semibold">{{ $donation->order_id }}</dd></div>
                <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-stone">Atas nama</dt><dd class="font-semibold">{{ $donation->display_name }}</dd></div>
                @if ($donation->payment_method)
                    <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-stone">Metode</dt><dd class="font-semibold">{{ $donation->payment_method->getLabel() }}</dd></div>
                @endif
                @if ($status === DonationStatus::Paid)
                    <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-stone">Dibayar</dt><dd class="font-semibold">{{ $donation->paid_at?->translatedFormat('d M Y, H:i') }}</dd></div>
                @elseif ($status === DonationStatus::Pending && $donation->expires_at)
                    <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-stone">Bayar sebelum</dt><dd class="font-semibold">{{ $donation->expires_at->translatedFormat('d M Y, H:i') }}</dd></div>
                @endif
            </dl>
        </section>

        @if ($status === DonationStatus::Paid)
            {{-- Thank-you card --}}
            <section class="rounded-2xl bg-jade p-5 text-white">
                <div class="flex items-center gap-2">
                    <img src="{{ $settings->logoUrl() }}" alt="" class="size-8 rounded-lg bg-white p-0.5">
                    <p class="text-sm font-bold">{{ $settings->foundationName() }}</p>
                </div>
                <p class="mt-3 text-[15px] leading-relaxed">
                    Terima kasih, {{ $donation->is_anonymous ? 'Sahabat' : $donation->donor_name }}. Semoga Allah membalas kebaikan Anda dengan keberkahan yang berlipat.
                    Kami akan mengabarkan penyalurannya secara terbuka.
                </p>
            </section>
            <a href="https://wa.me/?text={{ rawurlencode($shareText."\n".$shareUrl) }}" target="_blank" rel="noopener" class="btn btn-primary w-full">
                <x-glyph name="whatsapp" /> Ajak teman lewat WhatsApp
            </a>
            <a href="{{ $shareUrl }}" class="btn btn-outline w-full">Kembali ke campaign</a>
        @elseif ($status === DonationStatus::Pending)
            @if ($canResume)
                <div x-data="snapResume(@js($donation->snap_token), @js($statusUrl))">
                    <button type="button" @click="pay()" class="btn btn-primary w-full">Lanjutkan pembayaran</button>
                </div>
            @endif
            <a href="{{ $statusUrl }}" class="btn btn-outline w-full"><x-glyph name="refresh" /> Cek status</a>
            <p class="text-center text-xs text-stone">Status diperbarui otomatis setelah pembayaran dikonfirmasi oleh sistem pembayaran.</p>
        @else
            <a href="{{ $donation->campaign ? route('campaigns.donate', $donation->campaign) : route('donations.create', ['nominal' => $donation->amount]) }}"
               class="btn btn-primary w-full">Coba donasi lagi</a>
            <a href="{{ route('home') }}" class="btn btn-outline w-full">Ke beranda</a>
        @endif
    </div>
</x-layouts.app>
