<footer class="site-footer">
    <div class="footer-brand">
        <img src="{{ $settings->logoUrl() }}" alt="Logo Al Kirom Depok" width="58" height="64">
        <div><p class="footer-brand-name">Al Kirom<span>.</span></p><p>Bersama merawat harapan.</p></div>
    </div>
    <p class="mt-4 text-sm leading-7">{{ \Illuminate\Support\Str::limit($settings->get('about'), 220) }}</p>
    @if ($settings->get('pub_permit_number'))
        <p class="footer-permit"><x-glyph name="shield" class="size-4" /><span>Izin PUB: {{ $settings->get('pub_permit_number') }}</span></p>
    @endif
    @if ($wa = $settings->whatsappLink())
        <a href="{{ $wa }}" target="_blank" rel="noopener" class="footer-contact"><x-glyph name="whatsapp" class="size-5" /> Ada yang ingin ditanyakan? Hubungi kami</a>
    @endif
    <nav aria-label="Tautan kaki" class="footer-links">
        <a href="{{ route('pages.about') }}">Tentang kami</a>
        <a href="{{ route('pages.terms') }}">Syarat & ketentuan</a>
        <a href="{{ route('pages.privacy') }}">Privasi</a>
    </nav>
    <div class="footer-bottom">
        <p>© {{ now()->year }} {{ $settings->foundationName() }}</p>
        <p>Dari hati, untuk kebaikan yang berarti.</p>
    </div>
</footer>
