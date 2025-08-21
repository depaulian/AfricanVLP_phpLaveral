<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use App\Models\UserAlumniOrganization;
use App\Observers\ProfileCacheObserver;
use App\Services\ProfileCacheService;
use App\Services\ProfileCacheInvalidationService;
use App\Services\ProfileQueryOptimizationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class ProfileCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache services as singletons
        $this->app->singleton(ProfileCacheService::class);
        $this->app->singleton(ProfileCacheInvalidationService::class);
        $this->app->singleton(ProfileQueryOptimizationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers for cache invalidation
        $this->registerObservers();
        
        // Register console commands
        $this->registerCommands();
        
        // Log service initialization
        Log::info('Profile cache services initialized');
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        try {
            // Register observers for all profile-related models
            User::observe(ProfileCacheObserver::class);
            UserProfile::observe(ProfileCacheObserver::class);
            UserSkill::observe(ProfileCacheObserver::class);
            UserVolunteeringInterest::observe(ProfileCacheObserver::class);
            UserVolunteeringHistory::observe(ProfileCacheObserver::class);
            UserDocument::observe(ProfileCacheObserver::class);
            UserAlumniOrganization::observe(ProfileCacheObserver::class);
            
            Log::info('Profile cache observers registered successfully');
        } catch (\Exception $e) {
            Log::error('Failed to register profile cache observers', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Register console commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\WarmProfileCache::class,
                \App\Console\Commands\ManageProfileCache::class,
            ]);
        }
    }
}