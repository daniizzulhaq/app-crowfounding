<x-layouts.app title="Tentang kami" heading="Tentang kami" header="back">
    <x-page-intro eyebrow="KENAL LEBIH DEKAT" title="Dari hati. Untuk sesama." description="Kami percaya, kebaikan kecil yang dilakukan bersama bisa menghadirkan perubahan besar." icon="heart-handshake" />
    <div class="space-y-4 px-5">
        <section class="card p-5">
            <img src="{{ $settings->logoUrl() }}" alt="Logo {{ $settings->foundationName() }}" class="h-24 w-20 object-contain" width="80" height="96">
            <h2 class="mt-3 text-xl font-extrabold">{{ $settings->foundationName() }}</h2>
            <p class="mt-1 text-sm font-semibold text-jade">{{ $settings->get('tagline') }}</p>
            <p class="mt-3 text-[15px] leading-relaxed whitespace-pre-line">{{ $settings->get('about') }}</p>
        </section>

        <section class="card p-5">
            <h2 class="text-[15px] font-bold">Komitmen kami</h2>
            <ul class="mt-3 space-y-3 text-sm">
                <li class="flex gap-3"><x-glyph name="shield" class="text-jade" /> <span><span class="font-semibold">Berizin resmi.</span> Penggalangan dana berizin PUB{{ $settings->get('pub_permit_number') ? ' No. '.$settings->get('pub_permit_number') : '' }}.</span></li>
                <li class="flex gap-3"><x-glyph name="report" class="text-jade" /> <span><span class="font-semibold">Laporan terbuka.</span> Laporan keuangan bulanan bisa diunduh siapa saja.</span></li>
                <li class="flex gap-3"><x-glyph name="gift" class="text-jade" /> <span><span class="font-semibold">Penyaluran terdokumentasi.</span> Setiap penyaluran disertai foto, lokasi, dan penerima.</span></li>
                <li class="flex gap-3"><x-glyph name="wallet" class="text-jade" /> <span><span class="font-semibold">Biaya operasional terbatas.</span> Maksimal {{ config('alkiroma.operational_fee_max_percent') }}% sesuai ketentuan PUB{{ $settings->operationalFeePercent() ? ', saat ini '.$settings->operationalFeePercent().'%' : '' }}.</span></li>
            </ul>
        </section>

        @if ($settings->get('address') || $settings->get('phone') || $settings->get('email') || $settings->get('bank_accounts'))
            <section class="card p-5 text-sm">
                <h2 class="text-[15px] font-bold">Kontak</h2>
                <dl class="mt-3 space-y-2">
                    @if ($settings->get('address'))<div><dt class="text-stone">Alamat</dt><dd>{{ $settings->get('address') }}</dd></div>@endif
                    @if ($settings->get('phone'))<div><dt class="text-stone">Telepon</dt><dd>{{ $settings->get('phone') }}</dd></div>@endif
                    @if ($settings->get('email'))<div><dt class="text-stone">Email</dt><dd>{{ $settings->get('email') }}</dd></div>@endif
                    @if ($settings->get('bank_accounts'))<div><dt class="text-stone">Rekening yayasan</dt><dd class="money whitespace-pre-line">{{ $settings->get('bank_accounts') }}</dd></div>@endif
                </dl>
            </section>
        @endif
    </div>
</x-layouts.app>
