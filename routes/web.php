<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ChooseCampaignController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\MyDonationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Campaign
Route::get('/campaign', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaign/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/campaign/{campaign}/donatur', [CampaignController::class, 'donors'])->name('campaigns.donors');
Route::get('/campaign/{campaign}/donasi', [DonationController::class, 'create'])->name('campaigns.donate');

// Donasi
Route::get('/donasi/pilih', ChooseCampaignController::class)->name('donations.choose');
Route::get('/donasi', [DonationController::class, 'create'])->name('donations.create');
Route::post('/donasi', [DonationController::class, 'store'])->middleware('throttle:donation')->name('donations.store');
Route::get('/donasi/{orderId}', [DonationController::class, 'status'])->name('donations.status');
Route::get('/donasi/{orderId}/status', [DonationController::class, 'statusJson'])->name('donations.status.json');

// Webhook Midtrans (CSRF excluded in bootstrap/app.php)
Route::post('/webhooks/midtrans', MidtransWebhookController::class)->middleware('throttle:webhook')->name('webhooks.midtrans');

// Doa, Penyaluran, Laporan
Route::get('/doa', PrayerController::class)->name('prayers.index');
Route::get('/penyaluran', [DistributionController::class, 'index'])->name('distributions.index');
Route::get('/penyaluran/{distribution}', [DistributionController::class, 'show'])->name('distributions.show');
Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
Route::get('/laporan/{report}', [ReportController::class, 'show'])->name('reports.show');
Route::get('/laporan/{report}/unduh', [ReportController::class, 'download'])->name('reports.download');
Route::get('/laporan/{report}/donatur', [ReportController::class, 'donors'])->name('reports.donors');

// Donasi Saya (OTP WhatsApp)
Route::prefix('donasi-saya')->name('my-donations.')->controller(MyDonationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::view('/nomor', 'my-donations.phone')->name('phone');
    Route::post('/otp', 'requestOtp')->middleware('throttle:otp')->name('otp');
    Route::get('/verifikasi', 'verifyForm')->name('verify');
    Route::post('/verifikasi', 'verify')->middleware('throttle:otp-verify')->name('verify.submit');
    Route::post('/keluar', 'logout')->name('logout');
});

// Halaman statis & SEO
Route::get('/tentang', [PageController::class, 'about'])->name('pages.about');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
