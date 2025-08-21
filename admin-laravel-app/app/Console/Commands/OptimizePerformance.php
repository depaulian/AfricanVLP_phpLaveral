<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:optimize-performance 
                            {--cache : Optimize cache settings}
                            {--database : Optimize database queries and indexes}
                            {--config : Optimize configuration caching}
                            {--routes : Optimize route caching}
                            {--views : Optimize view caching}
                            {--all : Run all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize application performance through various strategies';

    private CacheService $cacheService;
    private QueryOptimizationService $queryOptimizationService;

    public function __construct(CacheService $cacheService, QueryOptimizationService $queryOptimizationService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->queryOptimizationService = $queryOptimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting performance optimization...');

        $runAll = $this->option('all');

        if ($runAll || $this->option('config')) {
            $this->optimizeConfig();
        }

        if ($runAll || $this->option('routes')) {
            $this->optimizeRoutes();
        }

        if ($runAll || $this->option('views')) {
            $this->optimizeViews();
        }

        if ($runAll || $this->option('cache')) {
            $this->optimizeCache();
        }

        if ($runAll || $this->option('database')) {
            $this->optimizeDatabase();
        }

        $this->info('✅ Performance optimization completed!');
        
        return Command::SUCCESS;
    }

    /**
     * Optimize configuration caching.
     */
    private function optimizeConfig(): void
    {
        $this->info('📝 Optimizing configuration...');

        try {
            // Clear existing config cache
            Artisan::call('config:clear');
            $this->line('   - Configuration cache cleared');

            // Cache configuration
            Artisan::call('config:cache');
            $this->line('   - Configuration cached');

            $this->info('✅ Configuration optimization completed');
        } catch (\Exception $e) {
            $this->error('❌ Configuration optimization failed: ' . $e->getMessage());
            Log::error('Configuration optimization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimize route caching.
     */
    private function optimizeRoutes(): void
    {
        $this->info('🛣️  Optimizing routes...');

        try {
            // Clear existing route cache
            Artisan::call('route:clear');
            $this->line('   - Route cache cleared');

            // Cache routes
            Artisan::call('route:cache');
            $this->line('   - Routes cached');

            $this->info('✅ Route optimization completed');
        } catch (\Exception $e) {
            $this->error('❌ Route optimization failed: ' . $e->getMessage());
            Log::error('Route optimization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimize view caching.
     */
    private function optimizeViews(): void
    {
        $this->info('👁️  Optimizing views...');

        try {
            // Clear existing view cache
            Artisan::call('view:clear');
            $this->line('   - View cache cleared');

            // Cache views
            Artisan::call('view:cache');
            $this->line('   - Views cached');

            $this->info('✅ View optimization completed');
        } catch (\Exception $e) {
            $this->error('❌ View optimization failed: ' . $e->getMessage());
            Log::error('View optimization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimize application cache.
     */
    private function optimizeCache(): void
    {
        $this->info('💾 Optimizing cache...');

        try {
            // Warm up cache
            $this->cacheService->warmUp();
            $this->line('   - Cache warmed up');

            // Get cache statistics
            $stats = $this->cacheService->getStats();
            $this->line('   - Cache driver: ' . $stats['driver']);
            
            if (isset($stats['memory_usage'])) {
                $this->line('   - Memory usage: ' . $stats['memory_usage']);
            }
            
            if (isset($stats['hit_rate'])) {
                $this->line('   - Hit rate: ' . $stats['hit_rate']);
            }

            $this->info('✅ Cache optimization completed');
        } catch (\Exception $e) {
            $this->error('❌ Cache optimization failed: ' . $e->getMessage());
            Log::error('Cache optimization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimize database performance.
     */
    private function optimizeDatabase(): void
    {
        $this->info('🗄️  Optimizing database...');

        try {
            // Create optimization indexes
            $indexes = $this->queryOptimizationService->createOptimizationIndexes();
            foreach ($indexes as $index) {
                $this->line('   - ' . $index);
            }

            // Get performance statistics
            $stats = $this->queryOptimizationService->getPerformanceStats();
            $this->line('   - Database driver: ' . $stats['connection']['driver']);
            $this->line('   - Database name: ' . $stats['connection']['database']);

            if (isset($stats['tables'])) {
                $this->line('   - Tables analyzed: ' . count($stats['tables']));
                
                // Show top 3 largest tables
                $topTables = array_slice($stats['tables'], 0, 3);
                foreach ($topTables as $table) {
                    $this->line('     • ' . $table['table'] . ': ' . $table['total_size'] . ' (' . number_format($table['rows']) . ' rows)');
                }
            }

            $this->info('✅ Database optimization completed');
        } catch (\Exception $e) {
            $this->error('❌ Database optimization failed: ' . $e->getMessage());
            Log::error('Database optimization failed', ['error' => $e->getMessage()]);
        }
    }
}