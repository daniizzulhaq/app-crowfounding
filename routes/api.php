<?php

use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\DonorController;
use App\Http\Middleware\AuthenticateMobileDonor;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->name('api.')->group(function () {
    Route::get('/home', [ContentController::class, 'home']);
    Route::get('/settings', [ContentController::class, 'settings']);
    Route::get('/categories', [ContentController::class, 'categories']);
    Route::get('/campaigns', [ContentController::class, 'campaigns']);
    Route::get('/campaigns/{campaign}', [ContentController::class, 'campaign']);
    Route::get('/campaigns/{campaign}/donors', [ContentController::class, 'donors']);
    Route::get('/prayers', [ContentController::class, 'prayers']);
    Route::get('/distributions', [ContentController::class, 'distributions']);
    Route::get('/distributions/{distribution}', [ContentController::class, 'distribution']);
    Route::get('/reports', [ContentController::class, 'reports']);
    Route::get('/reports/{report}', [ContentController::class, 'report']);
    Route::get('/reports/{report}/donors', [ContentController::class, 'reportDonors']);
    Route::post('/donations', [DonationController::class, 'store'])->middleware('throttle:donation');
    Route::get('/donations/{orderId}', [DonationController::class, 'show'])
        ->middleware('signed')->name('donations.show');
    Route::post('/auth/otp', [DonorController::class, 'requestOtp'])->middleware('throttle:otp');
    Route::post('/auth/verify', [DonorController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::middleware(AuthenticateMobileDonor::class)->group(function () {
        Route::get('/me', [DonorController::class, 'me']);
        Route::get('/me/donations', [DonorController::class, 'donations']);
        Route::post('/auth/logout', [DonorController::class, 'logout']);
    });
});
