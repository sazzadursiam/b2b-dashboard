<?php

namespace App\Providers;

use App\Support\CurrentBusiness;
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
        // One instance per request lifecycle; reset between requests/jobs.
        $this->app->scoped(CurrentBusiness::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('analytics', function (Request $request) {
            return Limit::perMinute(200)->by(
                optional($request->user())->business_id ?: $request->ip()
            );
        });
    }
}
