<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The scheduler trigger. Generous enough for a once-a-minute cron with
        // room for a retry, tight enough that a leaked token cannot be used to
        // hammer the app into sending mail. Keyed on IP so one misbehaving
        // caller cannot lock the real cron service out.
        RateLimiter::for('cron', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
