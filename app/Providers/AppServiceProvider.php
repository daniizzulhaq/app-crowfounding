<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Queries\HomePageData;
use App\Services\Payment\MidtransGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Settings;
use App\Services\WhatsApp\FonnteWhatsAppSender;
use App\Services\WhatsApp\LogWhatsAppSender;
use App\Services\WhatsApp\WhatsAppSender;
use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        $this->app->bind(PaymentGateway::class, fn () => MidtransGateway::fromConfig());

        $this->app->bind(WhatsAppSender::class, fn () => match (config('services.whatsapp.driver')) {
            'fonnte' => new FonnteWhatsAppSender((string) config('services.whatsapp.token')),
            default => new LogWhatsAppSender,
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Carbon::setLocale('id');

        // Shared lazily: Settings only hits cache/DB when a view actually reads it.
        View::composer('*', fn ($view) => $view->with('settings', $this->app->make(Settings::class)));

        foreach ([Banner::class, Campaign::class, Category::class, Distribution::class] as $model) {
            $model::saved(fn () => HomePageData::forget());
            $model::deleted(fn () => HomePageData::forget());
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by('api:'.$request->ip()));

        RateLimiter::for('donation', fn (Request $request) => [
            Limit::perMinute(6)->by('donation:ip:'.$request->ip()),
            Limit::perHour(30)->by('donation:ip-hour:'.$request->ip()),
        ]);

        RateLimiter::for('otp', function (Request $request) {
            $phone = PhoneNumber::normalize((string) $request->input('phone')) ?? 'invalid';

            return [
                Limit::perMinutes(10, 3)->by('otp:phone:'.$phone),
                Limit::perHour(10)->by('otp:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinutes(10, 10)->by('otp-verify:'.$request->ip()));

        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
