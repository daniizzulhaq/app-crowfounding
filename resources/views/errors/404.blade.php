<x-layouts.app title="Halaman tidak ditemukan" :noindex="true">
    <x-empty-state icon="search" title="Halaman tidak ditemukan" text="Tautan mungkin sudah berubah atau campaign sudah tidak tersedia." class="pt-16">
        <x-slot:action><a href="{{ route('home') }}" class="btn btn-primary">Ke beranda</a></x-slot:action>
    </x-empty-state>
</x-layouts.app>
