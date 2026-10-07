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
     * The path to your application's "home" route.
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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payphone-tool.generate', function (Request $request) {
            $ip = $request->ip() ?: 'unknown';
            $sessionId = $request->session()->getId() ?: 'unknown';
            $perMinute = max(1, (int) config('payments.payphone.tool.links_per_minute', 10));
            $perHour = max(1, (int) config('payments.payphone.tool.links_per_hour', 60));
            $globalPerMinute = min(20, $perMinute);

            return [
                Limit::perMinute($perMinute)->by('payphone-tool:generate:minute:ip:' . $ip),
                Limit::perMinute($perMinute)->by('payphone-tool:generate:minute:session:' . $sessionId),
                Limit::perHour($perHour)->by('payphone-tool:generate:hour:ip:' . $ip),
                Limit::perHour($perHour)->by('payphone-tool:generate:hour:session:' . $sessionId),
                Limit::perMinute($globalPerMinute)->by('payphone-tool:generate:minute:global'),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
