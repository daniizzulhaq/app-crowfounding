# Pengaturan donasi web dan aplikasi Alkirom

Versi mobile yang mendukung kalkulator dan jumlah barang: **1.1.0+2** atau lebih baru. Versi terbaru **1.1.1+3** juga mendukung campaign tanpa target dana. Pengaturan berada di **Admin → Program → Kategori → Tambah/Edit → Perhitungan donasi**. Campaign mengikuti kategori yang dipilih. Kategori yang sudah ada tetap memakai nominal bebas sampai admin menggantinya.

## Pilihan jenis perhitungan

| Jenis | Pengaturan admin | Form donatur |
|---|---|---|
| Nominal bebas | Pilih jenis nominal bebas | Pilihan nominal atau isi sendiri |
| Berdasarkan jumlah barang | Nama satuan dan harga per satuan | Isi jumlah barang atau nominal kelipatan harga satuan |
| Zakat pendapatan | Tarif persen dan nisab tahunan rupiah | Bayar langsung atau hitung pendapatan bulanan/tahunan beserta bonus untuk periode yang sama |
| Zakat mal (harta simpanan) | Tarif persen dan nisab tahunan rupiah | Bayar langsung atau hitung harta simpanan dikurangi utang jatuh tempo yang memenuhi ketentuan, disertai konfirmasi haul |

Contoh wakaf Quran: pilih **Berdasarkan jumlah barang**, isi satuan **mushaf** dan harga **150.000**. Buat atau edit campaign Wakaf Quran dan pilih kategori tersebut. Donatur dapat memilih **2 mushaf** sehingga tagihannya **Rp300.000**, atau memasukkan Rp300.000 yang dihitung sebagai 2 mushaf. Nominal yang tidak menghasilkan jumlah barang utuh ditolak.

Untuk zakat, isikan nisab sesuai ketetapan/acuan lembaga yang digunakan; tidak ada harga emas lama yang ditanam sebagai angka bawaan. Nisab bulanan pendapatan diperoleh dari nisab tahunan dibagi 12, dibulatkan ke atas ke rupiah utuh. Tarif awal formulir admin adalah 2,50% dan dapat disesuaikan. Hasil pembayaran juga dibulatkan ke atas ke rupiah utuh. Nisab dan harga emas tidak diperbarui otomatis: admin perlu memperbarui nisab jika acuan berubah.

Kalkulator mal ini untuk harta simpanan yang dimasukkan dalam rupiah, bukan kalkulator semua jenis zakat seperti pertanian atau peternakan. Nilai emas diisi sebagai nilai rupiah, bukan berat gram. Harta yang sama jangan dimasukkan pada dua kolom. Jika belum mencapai nisab atau haul belum terpenuhi, hasilnya nol dan pembayaran melalui hasil kalkulator tidak diaktifkan. Pilihan **Bayar langsung** tersedia bagi donatur yang sudah menghitung kewajibannya sendiri.

## Campaign tanpa target dana

Pada **Admin → Program → Campaign → Tambah/Edit**, kolom **Target dana (opsional)** dapat dikosongkan. Untuk Waqaf Quran berkelanjutan:

- Pilih kategori Waqaf Quran yang harga per mushafnya sudah diatur.
- Kosongkan **Target dana (opsional)** dan **Batas waktu**.
- Pilih status **Aktif**, lalu simpan.

Harga Rp150.000 per mushaf diatur pada kategori, bukan di kolom target campaign. Donatur tetap dapat memilih jumlah mushaf sesuai keinginannya. Web dan aplikasi 1.1.1 menampilkan total uang dan jumlah mushaf dari donasi lunas, tanpa bar/persentase atau angka target Rp0. Campaign tetap menerima donasi selama aktif. Jika ingin memasang target dana, isi kolom tersebut; target yang diisi tetap divalidasi minimal Rp100.000. Target dan batas waktu merupakan pengaturan yang terpisah.

Migrasi tambahan `2026_11_25_000002_make_campaign_target_optional.php` mengizinkan kolom target bernilai kosong. Target campaign lama yang sudah diisi tidak diubah otomatis.

## Perubahan admin dan riwayat

Web dan mobile memakai pengaturan kategori dari database yang sama. Mobile mengambil ulang detail campaign ketika formulir donasi dibuka. Perubahan admin terlihat setelah halaman/formulir dibuka kembali; tidak memakai notifikasi langsung ke formulir yang sedang terbuka. Perubahan harga/perhitungan yang menyebabkan nominal berbeda akan ditolak saat pembayaran dibuat; donatur diminta memuat ulang formulir.

Server selalu menghitung ulang sebelum meminta pembayaran Midtrans. Harga atau jumlah zakat buatan client tidak dijadikan sumber kebenaran. Rincian tipe, jumlah barang, harga satuan, periode, tarif, dan nisab saat transaksi disimpan sebagai snapshot; mengganti harga di admin tidak mengubah bukti transaksi lama. Angka harta, utang, penghasilan, dan bonus donatur dipakai untuk perhitungan sementara dan tidak disimpan dalam transaksi.

Jumlah barang yang tampil di detail campaign dan angka admin menghitung **donasi berstatus lunas saja**, sehingga transaksi menunggu, gagal, atau kedaluwarsa tidak menambah jumlah barang. Kolom jumlah barang pada daftar/detail donasi tetap memperlihatkan jumlah pesanan beserta status pembayarannya.

## Mengaktifkan pada hosting dan HP

Migrasi sudah dijalankan pada database pengembangan lokal. Hosting belum diperbarui dari sesi implementasi ini.

1. Cadangkan database hosting seperti biasa dan unggah backend terbaru, termasuk kedua migrasi `2026_11_25_000001_add_donation_calculations.php` dan `2026_11_25_000002_make_campaign_target_optional.php`, `app/Services/DonationCalculator.php`, controller quote, model, resource admin, request/action donasi, route, serta Blade yang berubah. Paket `alkirom-web-update-1.1.1.zip` mencakup pembaruan 1.1.0 dan dukungan target opsional.
2. Unggah seluruh `public/build` hasil build terbaru agar JavaScript kalkulator dan CSS sesuai manifest. Untuk membuat ulang aset: `npm ci` lalu `npm run build`. Jika pemuatan konfigurasi bundler terbatas pada lingkungan agent, `npm run build -- --configLoader native` juga sudah diverifikasi.
3. Pertahankan `.env`, `APP_KEY`, kredensial Midtrans, dan database hosting. Dari direktori Laravel di hosting jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

4. Atur jenis perhitungan pada kategori yang diinginkan dan pilih kategori itu pada campaign. Jangan jalankan ulang seeder atau reset database untuk mengaktifkan fitur ini.
5. Pasang APK **Alkirom 1.1.1+3**. APK baru tetap dapat menampilkan donasi biasa jika backend lama belum mengirim pengaturan; kalkulator dan jumlah barang baru aktif setelah backend diperbarui.

Setelah backend dan APK ini terpasang, mengubah nama kategori, harga barang, tarif, atau nisab dari admin tidak memerlukan compile APK lagi. Menambah jenis formulir/kalkulator yang belum didukung atau mengubah desain layar Flutter tetap memerlukan pembaruan aplikasi.

## API

- `GET /api/v1/campaigns` dan `GET /api/v1/campaigns/{slug}` menambahkan `donation_config` serta `paid_units`.
- `POST /api/v1/donation-quote` dan `POST /donasi/hitung` memakai calculator server yang sama; respons memakai `Cache-Control: no-store`.
- Pembayaran menerima `expected_type`, `quantity`, `payment_mode` (`direct`/`calculate`), dan `calculation_input`, selain field donatur yang sudah ada. Tarif dan harga satuan selalu dibaca dari database.
- Bukti mobile menambahkan `quantity` dan `calculation`; URL bukti tetap bertanda tangan seperti sebelumnya.

Contoh request kalkulator pendapatan:

```json
{
  "campaign": "slug-campaign-zakat",
  "expected_type": "zakat_income",
  "payment_mode": "calculate",
  "calculation_input": {"period": "monthly", "income": 10000000, "bonus": 2000000}
}
```

## Acuan

- [Kalkulator zakat BAZNAS](https://www.baznas.go.id/kalkulatorzakat): pendapatan bulanan/tahunan, nisab, dan tarif.
- [Kalkulator mal Bimas Islam Kemenag](https://bimasislam.kemenag.go.id/tools/kalkulator-zakat): harta bersih, nisab, dan haul.
- [Contoh alur bayar/hitung Zakatpedia](https://zakatpedia.com/services/zakat-pendapatan).
