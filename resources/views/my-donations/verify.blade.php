<x-layouts.app title="Verifikasi" heading="Verifikasi nomor" header="back" :back="route('my-donations.phone')" :nav="false" :noindex="true">
    <x-page-intro eyebrow="SELANGKAH LAGI" title="Masukkan kode" description="Verifikasi nomor WhatsApp untuk melihat riwayat donasimu dengan aman." icon="shield" />
    <div class="px-5">
        <p class="mt-1 text-sm text-stone">Kode {{ config('alkiroma.otp.length') }} angka sudah dikirim ke WhatsApp <span class="money font-semibold text-ink">{{ $maskedPhone }}</span>. Berlaku {{ config('alkiroma.otp.ttl_minutes') }} menit.</p>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-jade-soft px-4 py-3 text-sm font-semibold text-jade" role="status">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('my-donations.verify.submit') }}" class="mt-5" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <label for="code" class="label">Kode verifikasi</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="{{ config('alkiroma.otp.length') }}"
                   pattern="[0-9]*" required autofocus @error('code') aria-invalid="true" @enderror aria-describedby="code-error"
                   class="field money text-center text-2xl font-bold tracking-[0.5em]">
            @error('code')
                <p id="code-error" class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn btn-primary mt-4 w-full" :disabled="loading">
                <span x-text="loading ? 'Memeriksa…' : 'Verifikasi'">Verifikasi</span>
            </button>
        </form>

        <a href="{{ route('my-donations.phone') }}" class="btn btn-ghost mt-2 w-full">Ganti nomor / kirim ulang kode</a>
    </div>
</x-layouts.app>
