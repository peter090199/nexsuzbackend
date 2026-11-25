<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    // protected function configureRateLimiting(): void
    // {
    //     RateLimiter::for('api', function (Request $request) {
    //         return Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip());
    //     });
    // }

    
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(1000)->by($request->user()->id)
                : Limit::perMinute(100)->by($request->ip());
        });

        // Master-admin tools. Each has its OWN counter: numeric `throttle:N,M` limits share one counter per user across
        // every route that uses them, so a few optimize calls would otherwise block the reset (and vice versa).
        $perAdmin = fn (string $name, int $max, int $minutes) => RateLimiter::for($name, fn (Request $r) =>
            Limit::perMinutes($minutes, $max)->by($name . '|' . ($r->user()?->id ?: $r->ip())));
        // public "resend activation email": 3 per 10 minutes for each address + IP, so it cannot be used to spam someone
        RateLimiter::for('resend-activation', fn (Request $r) =>
            Limit::perMinutes(10, 3)->by('resend-activation|' . $r->ip() . '|' . strtolower((string) $r->input('email'))));
        $perAdmin('user-delete', 10, 5);
        $perAdmin('db-maintain', 10, 1);
        $perAdmin('db-backup', 5, 1);
        $perAdmin('db-reset', 3, 5);
        $perAdmin('email-config', 30, 1);
        $perAdmin('email-test-connection', 8, 1);
        $perAdmin('email-test-email', 5, 1);
        // Nexsuz Information: own counters, so the 3-per-minute test email is not eaten by ordinary page requests
        $perAdmin('business-info', 60, 1);
        $perAdmin('contact-mail-check', 6, 1);
        $perAdmin('contact-mail-test', 3, 1);
        $perAdmin('email-theme-preview', 60, 1);
        // Contact Inquiries: page requests are generous; sending email is tight (an admin cannot be used to flood someone)
        $perAdmin('inquiries', 120, 1);
        $perAdmin('inquiry-reply', 15, 10);
        // public contact form: 5 messages per minute for each visitor address
        RateLimiter::for('contact-message', fn (Request $r) => Limit::perMinute(5)->by('contact-message|' . $r->ip()));
    }
    
}
