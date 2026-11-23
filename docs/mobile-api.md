# API mobile v1

Web dan Flutter membaca/menulis database yang sama. Panel admin, saldo, OTP, pembayaran, webhook, dan laporan menggunakan implementasi web yang sudah ada.

## Memperbarui hosting alkiromberbagi.com

API produksi dan alamat bawaan Flutter: `https://alkiromberbagi.com/api/v1`.

1. Unggah perubahan backend ke aplikasi Laravel yang sudah berjalan di hosting, termasuk `routes/api.php`, `bootstrap/app.php`, controller API, middleware, model token, support serializer, konfigurasi CORS, provider, scheduler, dan migrasi baru.
2. Pertahankan `.env` hosting beserta `APP_KEY`, koneksi database, dan kredensial pembayaran/WhatsApp yang sudah digunakan. Pastikan `APP_URL=https://alkiromberbagi.com`, `APP_ENV=production`, dan `APP_DEBUG=false`. Jangan mengganti `.env` hosting dengan `.env` komputer atau `.env.example`.
3. Dari direktori Laravel di hosting, jalankan:

   ```bash
   php artisan config:clear
   php artisan migrate --force
   php artisan optimize
   ```

4. Pastikan public storage sudah tertaut (`php artisan storage:link` jika belum) dan cron/queue web tetap aktif. Migrasi menambahkan tabel token mobile ke database web yang sama; tidak perlu membuat database baru atau menjalankan seeder.
5. Buka `https://alkiromberbagi.com/api/v1/settings` dan `https://alkiromberbagi.com/api/v1/campaigns`; keduanya harus memberikan JSON. Setelah itu jalankan/build Flutter sesuai `../app-mobile-crowfoundig/README.md`.

Flutter Android/iOS langsung menghubungi hosting melalui internet. Tidak perlu menjalankan backend lokal atau memakai jaringan Wi-Fi yang sama dengan komputer.

Kirim `Accept: application/json` dan `Content-Type: application/json`. Error API selalu JSON.

## Endpoint

| Metode | Path | Keterangan |
| --- | --- | --- |
| GET | `/home` | Pengaturan publik, kategori, banner, campaign, penyaluran, doa |
| GET | `/settings` | Identitas, logo, kontak, tautan legal, batas/preset donasi |
| GET | `/categories` | Kategori |
| GET | `/campaigns` | Filter `q`, `kategori` (slug), `urut`, `accepting=1`, `page` |
| GET | `/campaigns/{slug}` | Detail termasuk `story_html` |
| GET | `/campaigns/{slug}/donors` | Donatur lunas, nama publik, doa yang tidak disembunyikan |
| GET | `/prayers` | Doa publik dari donasi lunas |
| GET | `/distributions` | Filter `campaign` (slug), `kategori` (slug), `page` |
| GET | `/distributions/{slug}` | Detail dan foto penyaluran |
| GET | `/reports` | Laporan terbit |
| GET | `/reports/{YYYY-MM}` | Rekap saldo dan URL PDF |
| GET | `/reports/{YYYY-MM}/donors` | Donatur publik periode laporan |
| POST | `/donations` | Buat donasi pending dan pembayaran Midtrans |
| GET | `/donations/{orderId}?expires=...&signature=...` | Bukti privat melalui URL bertanda tangan |
| POST | `/auth/otp` | Kirim kode WhatsApp |
| POST | `/auth/verify` | Tukarkan kode dengan bearer token |
| GET | `/me` | Ringkasan donor; perlu bearer token |
| GET | `/me/donations` | Donasi web/mobile nomor terverifikasi; perlu bearer token |
| POST | `/auth/logout` | Cabut bearer token aktif |

Nilai `urut`: `terbaru`, `segera-berakhir`, `terkumpul-terbanyak`. Daftar berpaginasi memakai `page` mulai 1, dengan bentuk:

```json
{"data": [], "meta": {"current_page": 1, "last_page": 1, "per_page": 10, "total": 0}}
```

Objek tunggal memakai `{"data": {...}}`. Nominal berupa integer rupiah. Pastikan `APP_URL`, disk public, dan `php artisan storage:link` sesuai agar URL gambar dapat diakses.

## Donasi

```http
POST /api/v1/donations
Content-Type: application/json

{
  "campaign": "slug-campaign",
  "amount": 25000,
  "donor_name": "Fulan",
  "is_anonymous": false,
  "phone": "081234567890",
  "prayer": "Semoga bermanfaat"
}
```

Untuk sedekah umum, hilangkan `campaign` atau isi `null`. Nama boleh kosong ketika anonim. Aturan nominal dan panjang input sama dengan web.

Respons `201` memuat `data.order_id`, `amount`, `status`, `target_label`, tanggal, `payment_url`, dan `status_url`. Flutter menyimpan bukti di penyimpanan aman perangkat dan membuka Midtrans melalui browser. Setelah kembali, aplikasi memeriksa server untuk status terbaru.

`status_url` adalah informasi privat. Simpan URL lengkap termasuk tanda tangannya. Order ID saja tidak dapat dipakai untuk mengambil bukti API. URL berlaku 7 hari dan diperbarui pada respons yang sah; riwayat OTP dapat menghasilkan URL baru setelah kedaluwarsa.

Saldo hanya berubah melalui hasil Midtrans yang diverifikasi pipeline web. Redirect dari browser tidak dianggap bukti pembayaran. Server key dan payload Midtrans tidak diberikan kepada aplikasi.

## OTP dan riwayat lintas perangkat

1. `POST /auth/otp` dengan `{"phone":"081234567890"}`.
2. `POST /auth/verify` dengan `{"phone":"081234567890","code":"123456"}`.
3. Simpan `data.token` secara aman. Kirim `Authorization: Bearer <token>` ke endpoint privat.
4. Token mengikuti `alkiroma.otp.session_minutes` (bawaan 30 menit). Kode sekali pakai, maksimal 5 percobaan, kedaluwarsa 5 menit. Respons `401` berarti verifikasi ulang.
5. Logout mencabut token aktif tanpa mencabut sesi web/perangkat lain.

Token tersimpan sebagai hash SHA-256, tanpa akses admin. Nama anonim, nomor kontak, Snap token, dan payload gateway tidak muncul di daftar publik. Respons bukti/sesi/riwayat memakai `Cache-Control: no-store`.

Driver WhatsApp `log` menulis pesan ke log lokal. Pengiriman nyata memerlukan `WHATSAPP_DRIVER=fonnte`, token provider, dan queue worker yang sudah digunakan web.

## Error, CORS, dan pengujian

`422` validasi (`message`, `errors`), `401` sesi, `403` tanda tangan bukti, `404` konten tidak tersedia/publik, `429` pembatasan, `503` gangguan gateway.

API dibatasi 120 permintaan/menit per IP; donasi dan OTP memakai pembatasan tambahan yang sama dengan web. Pratinjau Flutter web memakai origin dari `MOBILE_WEB_ORIGINS`, bawaan `http://localhost:8080,http://127.0.0.1:8080`. Android/iOS tidak memakai CORS. API tidak memakai cookie/CSRF. Webhook tetap memakai signature Midtrans.

```bash
php vendor/bin/pest tests/Feature/MobileApiTest.php
php artisan route:list --path=api
```

Referensi: [routing Laravel](https://laravel.com/docs/12.x/routing), [error JSON Laravel](https://laravel.com/docs/12.x/errors).
