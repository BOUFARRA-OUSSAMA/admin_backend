<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Services\AnalyticsService;
use App\Services\JwtTokenService;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CleanupExpiredTokens;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\Log;
use PDO;
use App\Models\PersonalInfo;
use App\Observers\PersonalInfoObserver;
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
        
        // Custom PostgreSQL connector for Neon
        $this->app->singleton('db.connector.pgsql', function () {
            return new class extends PostgresConnector {
                public function connect(array $config)
                {
                    // Get the default options
                    $options = $this->getOptions($config);
                    
                    // Build the DSN string
                    $dsn = $this->getDsn($config);
                    
                    // Add Neon endpoint if specified
                    if (!empty($config['options']['endpoint'])) {
                        $endpoint = $config['options']['endpoint'];
                        $dsn .= ";options=endpoint=" . $endpoint;
                    }
                    
                    // Create and return the connection
                    return $this->createConnection($dsn, $config, $options);
                }
            };
        });
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

        // Whenever a new permission is created, assign it to admin
        Permission::created(function ($permission) {
            $adminRole = Role::where('code', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
            }
        });

        // Register AppointmentObserver for automatic reminder scheduling
        Appointment::observe(AppointmentObserver::class);

        // Add retry logic to handle connection issues (important for serverless DBs on Azure)
        $this->app->extend('db.connector.pgsql', function ($connector, $app) {
            return new class($connector) {
                protected $connector;
                
                public function __construct($connector) {
                    $this->connector = $connector;
                }
                
                public function connect(array $config) {
                    $maxAttempts = 3;
                    $attempt = 0;
                    $lastException = null;
                    
                    while ($attempt < $maxAttempts) {
                        try {
                            return $this->connector->connect($config);
                        } catch (\Exception $e) {
                            $lastException = $e;
                            $attempt++;
                            if ($attempt >= $maxAttempts) {
                                break;
                            }
                            // Exponential backoff - helps with Azure serverless cold starts
                            sleep(pow(2, $attempt - 1)); 
                        }
                    }
                    // Log additional diagnostic info for Azure
                    Log::error("Azure DB Connection failed after {$maxAttempts} attempts", [
                        'host' => $config['host'] ?? 'unknown',
                        'error' => $lastException ? $lastException->getMessage() : 'unknown error',
                        'error_code' => $lastException instanceof \PDOException ? $lastException->getCode() : 'N/A'
                    ]);
                    
                    throw $lastException;
                }
            };
        });

        PersonalInfo::observe(PersonalInfoObserver::class);
    }
     
}
