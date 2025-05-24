<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Services\AnalyticsService;
use App\Services\JwtTokenService;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CleanupExpiredTokens;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register JwtTokenService first if needed
        $this->app->singleton(JwtTokenService::class, function ($app) {
            return new JwtTokenService();
        });

        // Register AnalyticsService with its dependency
        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService(
                $app->make(JwtTokenService::class)
            );
        });

        // Register the command in the container
        $this->app->singleton('command.jwt.cleanup', function ($app) {
            return new CleanupExpiredTokens();
        });

        // Add the command to Laravel's command list
        $this->commands([
            'command.jwt.cleanup',
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define API rate limiter
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register the scheduled task
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('jwt:cleanup-tokens')->daily();
        });
    }
}
