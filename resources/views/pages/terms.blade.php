<x-layouts.app title="Syarat & ketentuan" heading="Syarat & ketentuan" header="back">
    <x-page-intro eyebrow="BERBAGI DENGAN TENANG" title="Syarat & ketentuan" description="Informasi tentang donasi, pengelolaan dana, dan layanan yang kamu gunakan." icon="report" />
    <article class="prose-story card mx-4 mt-4 p-5">
        <h2>Ketentuan donasi di {{ $settings->foundationName() }}</h2>
        <ol>
            <li>Donasi bersifat sukarela dan tidak dapat ditarik kembali setelah pembayaran berhasil.</li>
            <li>Donasi disalurkan sesuai peruntukan campaign. Bila kebutuhan campaign sudah terpenuhi, kelebihan dana dialihkan ke program serupa yang paling membutuhkan.</li>
            <li>Sedekah tanpa campaign (dana umum) dikelola untuk program-program prioritas yayasan.</li>
            <li>Biaya operasional diambil maksimal {{ config('alkiroma.operational_fee_max_percent') }}% dari dana terhimpun sesuai ketentuan Pengumpulan Uang dan Barang (PUB), dan dilaporkan setiap bulan.</li>
            <li>Nomor WhatsApp dipakai untuk mengirim bukti donasi, kabar penyaluran, dan verifikasi riwayat donasi.</li>
            <li>Pembayaran diproses oleh Midtrans. Kami tidak menyimpan data kartu atau rekening Anda.</li>
            <li>Doa yang tidak pantas dapat disembunyikan oleh admin tanpa pemberitahuan.</li>
        </ol>
        <p>Pertanyaan? Hubungi kami melalui kontak di halaman <a href="{{ route('pages.about') }}">Tentang kami</a>.</p>
    </article>
</x-layouts.app>
