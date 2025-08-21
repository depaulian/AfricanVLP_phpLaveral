<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Services\CacheService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind shared services
        $this->app->singleton(CacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL compatibility
        Schema::defaultStringLength(191);

        // Prevent running migrations from the client app when guard is enabled
        if (config('app.client_disable_migrations', true) && app()->runningInConsole()) {
            $argv = isset($_SERVER['argv']) ? implode(' ', $_SERVER['argv']) : '';
            if (preg_match('/\bartisan\b.*\bmigrate(?!:status)\b|\bartisan\b.*\bmigrate:[^\s]+/i', $argv)) {
                throw new RuntimeException('Migrations are disabled in the client application. Run schema migrations from the admin app or set CLIENT_DISABLE_MIGRATIONS=false to override.');
            }
        }
    }
}