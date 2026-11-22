<x-layouts.app title="Riwayat dari HP lain" heading="Riwayat dari HP lain" header="back" :back="route('my-donations.index')" active="mine" :noindex="true">
    <x-page-intro eyebrow="TEMUKAN JEJAK KEBAIKANMU" title="Lihat riwayat donasi" description="Pernah berbagi dari HP lain? Hubungkan nomor WhatsApp untuk melihat kembali donasimu." icon="heart" />
    <div class="px-5">
        <p class="mt-1 text-sm text-stone">Pernah donasi dari HP lain? Masukkan nomor WhatsApp yang dipakai saat berdonasi. Kami kirim kode verifikasi supaya riwayat Anda tidak bisa dilihat orang lain.</p>

        @if (session('status'))
            <p class="mt-4 rounded-xl bg-jade-soft px-4 py-3 text-sm font-semibold text-jade" role="status">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('my-donations.otp') }}" class="mt-5" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <label for="phone" class="label">Nomor WhatsApp</label>
            <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0812 3456 7890" required maxlength="20"
                   value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror aria-describedby="phone-error" class="field money">
            @error('phone')
                <p id="phone-error" class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
            @enderror
            @if ($errors->has('throttle'))
                <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $errors->first('throttle') }}</p>
            @endif
            <button type="submit" class="btn btn-primary mt-4 w-full" :disabled="loading">
                <span x-text="loading ? 'Mengirim kode…' : 'Kirim kode verifikasi'">Kirim kode verifikasi</span>
            </button>
        </form>

        <p class="mt-6 flex items-start gap-2 text-xs text-stone">
            <x-glyph name="shield" class="size-4" />
            Nomor Anda hanya dipakai untuk verifikasi & bukti donasi, tidak pernah ditampilkan publik.
        </p>
    </div>
</x-layouts.app>
