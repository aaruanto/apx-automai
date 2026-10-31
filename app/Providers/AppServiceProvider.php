<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use App\Services\Sms\LogGateway;
use App\Services\Sms\SemaphoreGateway;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\UniSmsGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Which gateway SmsService is handed. Everything else about sending —
        // GSM-7 folding, number normalisation, segments, sms_logs, the
        // ok/failed shape — is the same whoever carries the message, so the
        // provider is a config value rather than a rewrite.
        //
        // An unknown value falls back to log rather than throwing. A typo in an
        // environment variable should stop messages going out, not take every
        // page down with it.
        $this->app->bind(SmsGateway::class, function () {
            return match (config('services.sms.driver')) {
                'semaphore' => new SemaphoreGateway(),
                'unisms'    => new UniSmsGateway(),
                default     => new LogGateway(),
            };
        });
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

        // The shop-wide theme, for pages that decide one before first paint.
        //
        // default_theme was a write-only setting: validated, stored, read back
        // into its own form, and consumed by nothing. Every page used
        // localStorage.getItem('apx-theme') || 'light', so an admin could set
        // Dark, be told it saved, and watch nothing happen.
        //
        // It is the FALLBACK only. A browser that has used the toggle has a
        // stored value, and that still wins — a shop-wide default must not
        // undo a choice someone has already made.
        //
        // Scoped to the views that paint a theme rather than shared globally:
        // this reads the settings table, and console commands and migrations
        // render no views but would still pay for it. Setting caches in-process,
        // so a request costs one query at most.
        View::composer([
            'auth.login',
            'auth.register',
            'auth.forgot-password',
            'auth.reset-password',
            'dashboard.customer-dashboard',
            'layouts.admin',
            // Standalone, so it is not covered by layouts.admin. Without it
            // $defaultTheme is undefined there and the page falls back to
            // light whatever the shop has chosen — the same bug as following
            // the operating system, just quieter.
            'notifications.index',
        ], function ($view) {
            // A fresh clone has no settings table until it migrates, and a
            // 500 on every page would be a poor way to learn that.
            try {
                $theme = Setting::get('default_theme', 'light');
            } catch (\Throwable $e) {
                $theme = 'light';
            }

            $view->with('defaultTheme', $theme === 'dark' ? 'dark' : 'light');
        });
    }
}
