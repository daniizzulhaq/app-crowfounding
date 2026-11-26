@php
    $min = (int) config('alkiroma.donation.min_amount');
    $max = (int) config('alkiroma.donation.max_amount');
    $target = $campaign?->title ?? 'Sedekah umum';
    $donationConfig = \App\Services\DonationCalculator::config($campaign?->category);
@endphp
<x-layouts.app title="Donasi" heading="Isi donasi" header="back" :nav="false" :noindex="true"
               :back="route('donations.choose')">
    <x-slot:head>
        <script src="{{ config('midtrans.snap_js') }}" data-client-key="{{ config('midtrans.client_key') }}" async></script>
    </x-slot:head>

    <x-page-intro eyebrow="BISMILLAH, MULAI KEBAIKANMU" title="Satu niat baik. Banyak senyum." description="Lengkapi donasimu dengan mudah. Semoga menjadi kebaikan yang terus mengalir." icon="hand-heart" />
    <p class="donation-reassurance"><x-glyph name="shield" class="size-4" /> Pembayaran aman melalui Midtrans</p>

    <form id="donation-form" method="post" action="{{ route('donations.store') }}" novalidate
          x-data="donationForm(@js([
              'action' => route('donations.store'),
              'campaign' => $campaign?->slug,
              'initial' => $initial,
              'presets' => $presets,
              'min' => $min,
              'max' => $max,
              'donationConfig' => $donationConfig,
              'quoteUrl' => route('donations.quote'),
          ]))"
          @submit.prevent="submit()" class="donation-form space-y-4 px-5">
        @csrf
        @if ($campaign)<input type="hidden" name="campaign" value="{{ $campaign->slug }}">@endif

        {{-- Target --}}
        <div class="card flex items-center gap-3 p-3">
            @if ($campaign)
                <x-campaign.image :campaign="$campaign" conversion="thumb" class="size-16 shrink-0 rounded-xl" />
            @else
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-jade-soft text-jade"><x-glyph name="hand-heart" class="size-7" /></span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-xs text-stone">Anda berdonasi untuk</p>
                <p class="line-clamp-2 text-sm font-bold">{{ $target }}</p>
            </div>
            {{-- Back to step 1 keeping the chosen amount --}}
            <a :href="'{{ route('donations.choose') }}' + (amount ? '?nominal=' + amount : '')" href="{{ route('donations.choose') }}"
               class="inline-flex min-h-11 shrink-0 items-center rounded-xl px-2 text-sm font-semibold text-jade">Ganti</a>
        </div>

        @if (session('error'))
            <p class="rounded-xl bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" role="alert">{{ session('error') }}</p>
        @endif

        {{-- 1. Nominal --}}
        <fieldset class="card p-4">
            <legend class="sr-only">Nominal donasi</legend>
            <x-donation-step number="1" :title="$donationConfig['type'] === 'unit' ? 'Pilih jumlah' : (str_starts_with($donationConfig['type'], 'zakat_') ? 'Hitung atau bayar zakat' : 'Pilih nominal')" step="1" />
            @if ($donationConfig['type'] === 'unit')
                <p class="mb-3 text-sm text-stone">Harga per {{ $donationConfig['unit_label'] }}: <strong class="text-jade">{{ rupiah($donationConfig['unit_price']) }}</strong></p>
                @unless ($donationConfig['ready'])<p class="text-sm text-danger">Harga satuan belum diatur oleh admin.</p>@endunless
                <label class="label" for="quantity">Jumlah {{ $donationConfig['unit_label'] }}</label>
                <input id="quantity" type="number" min="1" max="100000" step="1" :value="quantity" @input="updateUnit($event.target.value)" class="field" inputmode="numeric" :aria-invalid="(!!errors.quantity).toString()">
                <p x-show="errors.quantity" x-text="errors.quantity" class="mt-1 text-sm text-danger" role="alert"></p>
                <label class="label mt-3" for="unit-amount">Atau masukkan nominal kelipatan harga satuan</label>
                <input id="unit-amount" type="text" inputmode="numeric" :value="custom" @input="onUnitAmount($event)" class="field money" :aria-invalid="(!!errors.amount).toString()">
                <p class="mt-3 text-lg font-bold text-jade" x-text="formattedTotal"></p>
            @else
            @if (str_starts_with($donationConfig['type'], 'zakat_'))
                <div class="mb-4 flex flex-wrap gap-3">
                    <label class="flex min-h-11 items-center gap-2"><input type="radio" x-model="mode" value="direct" @change="invalidateCalculation()"> Bayar langsung</label>
                    <label class="flex min-h-11 items-center gap-2"><input type="radio" x-model="mode" value="calculate" @change="invalidateCalculation()"> Hitung zakat</label>
                </div>
                <div x-show="calculatingZakat" x-cloak class="space-y-3">
                    <p class="text-sm font-semibold">{{ $donationConfig['label'] }}</p>
                    <p class="text-xs text-stone">Tarif {{ $donationConfig['zakat_rate'] }}%. Nisab tahunan {{ rupiah($donationConfig['zakat_nisab']) }} sesuai pengaturan lembaga.</p>
                    @unless ($donationConfig['ready'])<p class="text-sm text-danger">Kalkulator belum tersedia. Admin perlu mengatur nisab dan tarif terlebih dahulu.</p>@endunless
                    @if ($donationConfig['type'] === 'zakat_income')
                        <label class="label" for="zakat-period">Periode pendapatan</label>
                        <select id="zakat-period" class="field" x-model="calculationInput.period" @change="invalidateCalculation()"><option value="monthly">Per bulan</option><option value="annual">Per tahun</option></select>
                        <p class="text-xs text-stone">Isi pendapatan dan bonus untuk periode yang sama.</p>
                        @php($zakatFields = ['income' => 'Pendapatan (Rp)', 'bonus' => 'Bonus / THR / pendapatan lain (Rp)'])
                    @else
                        @php($zakatFields = ['cash' => 'Uang tunai dan tabungan (Rp)', 'gold' => 'Nilai emas / logam mulia simpanan (Rp)', 'investments' => 'Investasi / surat berharga (Rp)', 'other' => 'Harta simpanan lainnya (Rp)', 'debts' => 'Utang jatuh tempo yang dapat dikurangkan (Rp)'])
                        <p class="text-xs text-stone">Isi harta yang memenuhi ketentuan zakat. Masukkan setiap harta satu kali, tanpa menghitungnya ulang pada kolom lain.</p>
                    @endif
                    @foreach ($zakatFields as $key => $label)
                        <label class="label" for="zakat-{{ $key }}">{{ $label }}</label>
                        <input id="zakat-{{ $key }}" type="text" inputmode="numeric" placeholder="0" x-model="calculationInput.{{ $key }}" @input="invalidateCalculation()" class="field money">
                    @endforeach
                    @if ($donationConfig['type'] === 'zakat_mal')
                        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" x-model="calculationInput.haul" @change="invalidateCalculation()"> Harta telah memenuhi haul satu tahun hijriah.</label>
                    @endif
                    <button type="button" class="btn btn-outline w-full" @click="calculate()" :disabled="calculating || !program.ready" x-text="calculating ? 'Menghitung…' : 'Hitung zakat'"></button>
                    <div x-show="quote" class="rounded-xl bg-jade-soft p-3" aria-live="polite">
                        <p class="text-lg font-bold text-jade" x-text="formattedTotal"></p><p class="text-sm" x-text="quote?.message"></p>
                    </div>
                </div>
            @endif
            <div x-show="!calculatingZakat">
            <div class="grid grid-cols-3 gap-2" role="group" aria-label="Nominal cepat">
                @foreach ($presets as $preset)
                    <button type="button" class="chip money px-0" @click="pick({{ $preset }})"
                            :aria-pressed="(amount === {{ $preset }} && !custom).toString()">{{ \App\Support\Rupiah::short($preset) }}</button>
                @endforeach
            </div>
            <label for="amount" class="label mt-3">Nominal lain</label>
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 font-semibold text-stone">Rp</span>
                <input id="amount" name="amount" type="text" inputmode="numeric" autocomplete="off" placeholder="0"
                       :value="custom" @input="onCustom($event)" value="{{ old('amount') }}"
                       :aria-invalid="(!!errors.amount).toString()" aria-describedby="amount-help amount-error"
                       class="field money pl-11 text-lg font-bold">
            </div>
            <p id="amount-help" class="mt-1.5 text-xs text-stone">Minimal {{ rupiah($min) }}</p>
            </div>
            @endif
            <p id="amount-error" x-show="errors.amount" x-text="errors.amount" x-cloak class="mt-1 text-sm font-semibold text-danger" role="alert"></p>
        </fieldset>

        {{-- 2. Nama --}}
        <fieldset class="card p-4">
            <legend class="sr-only">Data donatur</legend>
            <x-donation-step number="2" title="Nama Anda" step="2" />
            <label for="donor_name" class="sr-only">Nama lengkap</label>
            <input id="donor_name" name="donor_name" type="text" autocomplete="name" maxlength="60" placeholder="Nama lengkap"
                   x-model="name" :disabled="anonymous" :aria-invalid="(!!errors.donor_name).toString()" aria-describedby="name-error"
                   class="field disabled:bg-paper disabled:text-stone">
            <p id="name-error" x-show="errors.donor_name" x-text="errors.donor_name" x-cloak class="mt-1 text-sm font-semibold text-danger" role="alert"></p>

            <label class="mt-3 flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl bg-paper px-3">
                <span class="flex items-center gap-2 text-sm">
                    <x-glyph name="eye-off" class="size-4 text-stone" />
                    <span>
                        <span class="font-semibold">Sembunyikan nama</span>
                        <span class="block text-xs text-stone">Tampil sebagai “Hamba Allah”</span>
                    </span>
                </span>
                <input type="checkbox" name="is_anonymous" value="1" x-model="anonymous" class="peer sr-only" role="switch" :aria-checked="anonymous.toString()">
                <span class="relative h-7 w-12 shrink-0 rounded-full bg-line transition-colors peer-checked:bg-jade peer-focus-visible:ring-2 peer-focus-visible:ring-jade peer-focus-visible:ring-offset-2
                             after:absolute after:top-1 after:left-1 after:size-5 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5" aria-hidden="true"></span>
            </label>
        </fieldset>

        {{-- 3. WhatsApp --}}
        <fieldset class="card p-4">
            <legend class="sr-only">Nomor WhatsApp</legend>
            <x-donation-step number="3" title="Nomor WhatsApp" step="3" />
            <label for="phone" class="sr-only">Nomor WhatsApp</label>
            <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0812 3456 7890" maxlength="20"
                   x-model="phone" :aria-invalid="(!!errors.phone).toString()" aria-describedby="phone-help phone-error" class="field money">
            <p id="phone-help" class="mt-1.5 flex items-start gap-1.5 text-xs text-stone">
                <x-glyph name="shield" class="size-4" /> Untuk bukti donasi & riwayat. Tidak pernah ditampilkan publik.
            </p>
            <p id="phone-error" x-show="errors.phone" x-text="errors.phone" x-cloak class="mt-1 text-sm font-semibold text-danger" role="alert"></p>
        </fieldset>

        {{-- 4. Doa --}}
        <fieldset class="card p-4">
            <legend class="sr-only">Doa</legend>
            <x-donation-step number="4" title="Doa terbaik (opsional)" step="4" />
            <label for="prayer" class="sr-only">Tulis doa</label>
            <textarea id="prayer" name="prayer" rows="3" maxlength="500" placeholder="Tulis doa atau harapan Anda…"
                      x-model="prayer" aria-describedby="prayer-count" class="field py-3"></textarea>
            <p id="prayer-count" class="mt-1 text-right text-xs text-stone"><span x-text="prayer.length">0</span>/500</p>
        </fieldset>

        {{-- Honeypot (hidden from humans) --}}
        <div class="hidden" aria-hidden="true">
            <label>Website <input x-ref="hp" type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <p x-show="message" x-text="message" x-cloak class="rounded-xl bg-danger-soft px-4 py-3 text-sm font-semibold text-danger" role="alert"></p>

        <p class="pb-32 text-center text-xs text-stone">
            Dengan berdonasi, Anda menyetujui <a href="{{ route('pages.terms') }}" class="font-semibold text-jade underline">syarat & ketentuan</a>.
            Pembayaran diproses aman oleh Midtrans.
        </p>

        {{-- Sticky pay bar (inside the form so it shares Alpine state) --}}
        <div class="sticky-action fixed inset-x-0 bottom-0 z-40 mx-auto w-full max-w-[480px] bg-white px-5 pt-4 pb-safe shadow-[var(--shadow-bar)]">
            <button type="submit" class="btn btn-primary money w-full" :disabled="loading || calculating || (program.type === 'unit' &amp;&amp; !program.ready) || (calculatingZakat &amp;&amp; (!quote || !quote.eligible))" :aria-busy="loading.toString()">
                <svg x-show="loading" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                <span x-text="loading ? 'Memproses…' : payLabel">{{ $initial ? 'Bayar '.rupiah($initial) : 'Donasi sekarang' }}</span>
            </button>
        </div>
    </form>
</x-layouts.app>
