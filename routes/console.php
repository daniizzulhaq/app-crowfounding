<?php

use App\Models\OtpCode;
use Illuminate\Support\Facades\Schedule;

// Fallback if a webhook is lost; runs before expiring so late payments are caught.
Schedule::command('donations:sync-pending')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('donations:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('campaigns:complete-expired')->dailyAt('00:05')->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [OtpCode::class]])->daily();

// Shared hosting (cPanel) without Supervisor: drain the database queue every minute.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
