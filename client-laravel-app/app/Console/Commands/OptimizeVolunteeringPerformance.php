<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VolunteeringCacheService;
use App\Services\VolunteeringPerformanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizeVolunteeringPerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteering:optimize 
                            {--cache : Warm up cache}
                            {--indexes : Analyze and optimize indexes}
                            {--cleanup : Clean up old cache entries}
                            {--stats : Show performance statistics}
                            {--all : Run all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize volunteering system performance';

    protected VolunteeringCacheService $cacheService;
    protected VolunteeringPerformanceService $performanceService;

    public function __construct(
        VolunteeringCacheService $cacheService,
        VolunteeringPerformanceService $performanceService
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting volunteering performance optimization...');

        if ($this->option('all')) {
            $this->warmUpCache();
            $this->optimizeIndexes();
            $this->cleanupCache();
            $this->showStats();
        } else {
            if ($this->option('cache')) {
                $this->warmUpCache();
            }

            if ($this->option('indexes')) {
                $this->optimizeIndexes();
            }

            if ($this->option('cleanup')) {
                $this->cleanupCache();
            }

            if ($this->option('stats')) {
                $this->showStats();
            }
        }

        $this->info('✅ Performance optimization completed!');
        return 0;
    }

    /**
     * Warm up cache with frequently accessed data
     */
    protected function warmUpCache(): void
    {
        $this->info('🔥 Warming up cache...');

        $bar = $this->output->createProgressBar(6);
        $bar->start();

        // Cache popular opportunities
        $this->cacheService->cachePopularOpportunities();
        $bar->advance();

        // Cache featured opportunities
        $this->cacheService->cacheFeaturedOpportunities();
        $bar->advance();

        // Cache categories with counts
        $this->cacheService->cacheCategoriesWithCounts();
        $bar->advance();

        // Preload popular data
        $this->performanceService->preloadPopularData();
        $bar->advance();

        // Warm up full cache
        $this->cacheService->warmUpCache();
        $bar->advance();

        $bar->finish();
        $this->newLine();
        $this->info('✅ Cache warmed up successfully');
    }

    /**
     * Optimize database indexes
     */
    protected function optimizeIndexes(): void
    {
        $this->info('🔧 Analyzing and optimizing indexes...');

        try {
            // Analyze table statistics
            $tables = [
                'volunteering_opportunities',
                'volunteer_applications',
                'volunteer_assignments',
                'volunteer_time_logs',
                'user_volunteering_interests',
                'user_skills'
            ];

            $bar = $this->output->createProgressBar(count($tables));
            $bar->start();

            foreach ($tables as $table) {
                // Analyze table
                DB::statement("ANALYZE TABLE {$table}");
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Check for unused indexes
            $this->checkUnusedIndexes();

            // Suggest missing indexes
            $this->suggestMissingIndexes();

            $this->info('✅ Index optimization completed');

        } catch (\Exception $e) {
            $this->error('❌ Index optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Check for unused indexes
     */
    protected function checkUnusedIndexes(): void
    {
        try {
            $unusedIndexes = DB::select("
                SELECT 
                    t.table_name,
                    t.index_name,
                    t.cardinality
                FROM information_schema.statistics t
                WHERE t.table_schema = DATABASE()
                AND t.table_name LIKE '%volunteer%'
                AND t.cardinality = 0
                AND t.index_name != 'PRIMARY'
            ");

            if (!empty($unusedIndexes)) {
                $this->warn('⚠️  Found potentially unused indexes:');
                foreach ($unusedIndexes as $index) {
                    $this->line("  - {$index->table_name}.{$index->index_name}");
                }
            } else {
                $this->info('✅ No unused indexes found');
            }

        } catch (\Exception $e) {
            $this->warn('Could not check unused indexes: ' . $e->getMessage());
        }
    }

    /**
     * Suggest missing indexes based on common queries
     */
    protected function suggestMissingIndexes(): void
    {
        $this->info('🔍 Analyzing query patterns for missing indexes...');

        // This would typically analyze slow query log
        // For now, we'll check if our performance indexes exist
        $expectedIndexes = [
            'volunteering_opportunities' => [
                'idx_status_deadline',
                'idx_category_status',
                'idx_city_status',
                'idx_fulltext_search'
            ],
            'volunteer_applications' => [
                'idx_user_status',
                'idx_opportunity_status'
            ]
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            $existingIndexes = DB::select("
                SHOW INDEX FROM {$table}
            ");

            $existingNames = collect($existingIndexes)->pluck('Key_name')->toArray();

            foreach ($indexes as $expectedIndex) {
                if (!in_array($expectedIndex, $existingNames)) {
                    $this->warn("⚠️  Missing recommended index: {$table}.{$expectedIndex}");
                }
            }
        }
    }

    /**
     * Clean up old cache entries
     */
    protected function cleanupCache(): void
    {
        $this->info('🧹 Cleaning up cache...');

        try {
            // Clear old opportunity list caches
            $this->cacheService->clearListCaches();

            // Clear expired cache entries
            if (config('cache.default') === 'redis') {
                $this->info('Cleaning up Redis cache...');
                
                // This would typically use Redis SCAN to find and delete expired keys
                // For safety, we'll just clear specific patterns
                $patterns = [
                    'opportunity_list:*',
                    'user_applications:*',
                    'opportunity_stats:*'
                ];

                foreach ($patterns as $pattern) {
                    $keys = \Illuminate\Support\Facades\Redis::keys($pattern);
                    if (!empty($keys)) {
                        \Illuminate\Support\Facades\Redis::del($keys);
                        $this->info("Cleared " . count($keys) . " keys matching {$pattern}");
                    }
                }
            }

            $this->info('✅ Cache cleanup completed');

        } catch (\Exception $e) {
            $this->error('❌ Cache cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Show performance statistics
     */
    protected function showStats(): void
    {
        $this->info('📊 Performance Statistics');
        $this->line('========================');

        try {
            $metrics = $this->performanceService->getPerformanceMetrics();

            // Cache statistics
            if (isset($metrics['cache_stats'])) {
                $this->info('Cache Statistics:');
                foreach ($metrics['cache_stats'] as $key => $value) {
                    $this->line("  {$key}: {$value}");
                }
                $this->newLine();
            }

            // Database statistics
            if (isset($metrics['database_stats']['table_sizes'])) {
                $this->info('Table Sizes (MB):');
                foreach ($metrics['database_stats']['table_sizes'] as $table => $size) {
                    $this->line("  {$table}: {$size} MB");
                }
                $this->newLine();
            }

            // Show opportunity counts
            $this->showOpportunityCounts();

        } catch (\Exception $e) {
            $this->error('❌ Could not fetch statistics: ' . $e->getMessage());
        }
    }

    /**
     * Show opportunity counts and statistics
     */
    protected function showOpportunityCounts(): void
    {
        try {
            $stats = DB::select("
                SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(CASE WHEN max_volunteers > 0 THEN (current_volunteers / max_volunteers) * 100 ELSE 0 END) as avg_fill_rate
                FROM volunteering_opportunities 
                GROUP BY status
            ");

            $this->info('Opportunity Statistics:');
            foreach ($stats as $stat) {
                $fillRate = round($stat->avg_fill_rate, 1);
                $this->line("  {$stat->status}: {$stat->count} opportunities (avg fill rate: {$fillRate}%)");
            }

            // Application statistics
            $appStats = DB::select("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM volunteer_applications 
                GROUP BY status
            ");

            $this->newLine();
            $this->info('Application Statistics:');
            foreach ($appStats as $stat) {
                $this->line("  {$stat->status}: {$stat->count} applications");
            }

        } catch (\Exception $e) {
            $this->warn('Could not fetch opportunity statistics: ' . $e->getMessage());
        }
    }
}