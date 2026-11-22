# Alkiroma Amanah

Website donasi milik satu yayasan (berizin PUB). Halaman publik mobile-first berupa Blade + Tailwind v4 + Alpine. Panel admin memakai Filament 3, pembayaran memakai Midtrans Snap.

## Menjalankan lokal

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# atur DB_* di .env (MySQL), lalu:
php artisan migrate --seed
php artisan storage:link
php artisan serve
php artisan queue:work      # terminal lain (notifikasi WA)
php artisan schedule:work   # terminal lain (kedaluwarsa donasi, campaign selesai)
```

Admin hasil seed: `admin@alkiromaamanah.test` / `password`. **Ganti sebelum online**, atau buat admin baru dengan `php artisan app:create-admin`.

Test: `./vendor/bin/pest` (SQLite in-memory; butuh ekstensi `pdo_sqlite`).

## Alur uang (penting)

- `collected_amount` dan `donor_count` hanya berubah di `App\Actions\Donations\ApplyPaymentNotification`, yang dipanggil oleh webhook `/webhooks/midtrans` dan tombol admin "Cek status ke Midtrans".
- Signature diverifikasi dengan `sha512(order_id + status_code + gross_amount + server_key)`. Proses berjalan dalam DB transaction + `lockForUpdate`, dan bersifat idempoten.
- Penyaluran memakai `App\Actions\Distributions\DistributionLedger`. Total penyaluran tidak boleh melebihi dana terkumpul (per campaign atau dana umum).
- Semua nominal disimpan sebagai `unsignedBigInteger` rupiah, tanpa float.

## Checklist deploy (cPanel / VPS)

1. PHP ≥ 8.3 dengan ekstensi `pdo_mysql`, `gd`, `exif`, `intl`, `mbstring`, `zip`, `fileinfo`, `bcmath`.
2. Arahkan document root ke `public/`. Di cPanel, symlink `public_html` → `public`.
3. Isi `.env` produksi:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://alkiromaamanah.(domain)`
   - DB, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=true`
   - `WHATSAPP_DRIVER=fonnte` + `WHATSAPP_TOKEN` (atau tetap `log`)
   - `SESSION_SECURE_COOKIE=true`
4. Jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build      # atau unggah folder public/build hasil build lokal
   php artisan migrate --force
   php artisan storage:link
   php artisan app:create-admin
   php artisan optimize          # config, route, view, event cache
   php artisan filament:optimize
   ```
5. Pasang cron setiap menit: `* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1`. Scheduler juga menjalankan `queue:work --stop-when-empty`. Di VPS, sebaiknya pakai Supervisor untuk `queue:work`.
6. Di dashboard Midtrans, set Payment Notification URL ke `https://(domain)/webhooks/midtrans`. Finish URL tidak wajib, karena halaman status hanya membaca database.
7. Pasang HTTPS (AutoSSL/Let's Encrypt). Pastikan `storage/` dan `bootstrap/cache/` bisa ditulis web server.
8. Login ke `/admin` → **Pengaturan yayasan**: isi nomor izin PUB, alamat, kontak, rekening, logo, dan biaya operasional (≤ 10%).
9. Uji di sandbox dulu: donasi → bayar → webhook → status berhasil → angka campaign bertambah satu kali.
10. Backup database harian (cPanel Backup / `mysqldump` via cron).
