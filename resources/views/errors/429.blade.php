<x-layouts.app title="Terlalu banyak percobaan" :noindex="true">
    <x-empty-state icon="clock" title="Terlalu banyak percobaan" text="Mohon tunggu beberapa menit, lalu coba lagi." class="pt-16">
        <x-slot:action><a href="{{ url()->previous() }}" class="btn btn-outline">Kembali</a></x-slot:action>
    </x-empty-state>
</x-layouts.app>
