@props(['search' => ''])
<header class="app-header sticky top-0 z-30">
    <div class="brand-bar">
        <a href="{{ route('home') }}" class="brand-lockup" aria-label="{{ $settings->foundationName() }} — Beranda">
            <img src="{{ $settings->logoUrl() }}" alt="Logo Al Kirom Depok" class="brand-logo" width="48" height="52">
            <span>
                <span class="brand-name">Al Kirom<span class="brand-period">.</span></span>
                <span class="brand-tagline">Jembatan kebaikan kita</span>
                <span class="sr-only">{{ $settings->foundationName() }}</span>
            </span>
        </a>
        <a href="{{ route('pages.about') }}" class="header-about" aria-label="Tentang Al Kirom">
            <x-glyph name="hand-heart" class="size-5" />
            <span>Berbagi<br><strong>sepenuh hati</strong></span>
        </a>
    </div>
    <form action="{{ route('campaigns.index') }}" method="get" role="search" class="header-search">
        <label for="q-header" class="sr-only">Cari campaign</label>
        <x-glyph name="search" class="size-[18px] text-stone" />
        <input id="q-header" type="search" name="q" value="{{ $search }}" placeholder="Cari kesempatan untuk berbagi" autocomplete="off">
        <button type="submit" aria-label="Cari campaign"><x-glyph name="sliders" class="size-[18px]" /></button>
    </form>
</header>
