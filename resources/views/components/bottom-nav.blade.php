@props(['active' => null])
@php
    $left = [
        ['key' => 'home', 'label' => 'Beranda', 'icon' => 'home', 'route' => 'home'],
        ['key' => 'distributions', 'label' => 'Penyaluran', 'icon' => 'gift', 'route' => 'distributions.index'],
    ];
    $right = [
        ['key' => 'reports', 'label' => 'Laporan', 'icon' => 'report', 'route' => 'reports.index'],
        ['key' => 'mine', 'label' => 'Donasi saya', 'icon' => 'user', 'route' => 'my-donations.index'],
    ];
@endphp
<nav aria-label="Navigasi utama" class="bottom-navigation fixed inset-x-0 bottom-0 z-40 mx-auto w-full max-w-[480px] pb-safe">
    <ul class="grid grid-cols-5 items-end">
        @foreach ($left as $item)
            <x-bottom-nav-item :item="$item" :active="$active === $item['key']" />
        @endforeach
        <li class="flex justify-center">
            <a href="{{ route('donations.choose') }}" @if ($active === 'donate') aria-current="page" @endif class="nav-donate">
                <span class="nav-donate-icon"><x-glyph name="hand-heart" class="size-6" /></span>
                <span>Donasi</span>
            </a>
        </li>
        @foreach ($right as $item)
            <x-bottom-nav-item :item="$item" :active="$active === $item['key']" />
        @endforeach
    </ul>
</nav>
