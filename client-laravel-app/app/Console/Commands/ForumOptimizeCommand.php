<?php

namespace App\Console\Commands;

use App\Services\ForumPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForumOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'forum:optimize 
                            {action : The optimization action (indexes, analyze, metrics, all)}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize forum database performance';

    private ForumPerformanceService $performanceService;

    public function __construct(ForumPerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $force = $this->option('force');

        switch ($action) {
            case 'indexes':
                return $this->createIndexes($force);
            
            case 'analyze':
                return $this->analyzePerformance();
            
            case 'metrics':
                return $this->showMetrics();
            
            case 'all':
                return $this->optimizeAll($force);
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: indexes, analyze, metrics, all');
                return 1;
        }
    }

    /**
     * Create optimal database indexes
     */
    private function createIndexes(bool $force): int
    {
        if (!$force && !$this->confirm('Create database indexes for forum optimization?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Creating optimal database indexes...');
        
        try {
            $indexes = $this->performanceService->createOptimalIndexes();
            
            if (empty($indexes)) {
                $this->warn('No indexes were created. They may already exist.');
            } else {
                $this->info('✓ Created the following indexes:');
                foreach ($indexes as $index) {
                    $this->line("  - {$index}");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create indexes: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Analyze query performance
     */
    private function analyzePerformance(): int
    {
        $this->info('Analyzing forum query performance...');
        
        $analysis = $this->performanceService->analyzeQueryPerformance();
        
        $this->table(
            ['Query', 'Execution Time', 'Status'],
            collect($analysis)->map(function ($result, $name) {
                return [
                    $name,
                    $result['execution_time'] ?? 'N/A',
                    isset($result['error']) ? 'ERROR: ' . $result['error'] : 'OK'
                ];
            })->toArray()
        );
        
        // Show slow queries
        $slowQueries = collect($analysis)->filter(function ($result) {
            if (isset($result['error'])) {
                return false;
            }
            $time = (float) str_replace('ms', '', $result['execution_time']);
            return $time > 100; // Queries taking more than 100ms
        });
        
        if ($slowQueries->isNotEmpty()) {
            $this->newLine();
            $this->warn('Slow queries detected (>100ms):');
            foreach ($slowQueries as $name => $result) {
                $this->line("  - {$name}: {$result['execution_time']}");
            }
        }
        
        return 0;
    }

    /**
     * Show performance metrics
     */
    private function showMetrics(): int
    {
        $this->info('Forum Performance Metrics');
        $this->info('========================');
        
        $metrics = $this->performanceService->getPerformanceMetrics();
        
        // Cache statistics
        if (isset($metrics['cache_stats'])) {
            $this->info('Cache Statistics:');
            $cacheStats = $metrics['cache_stats'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Used Memory', $cacheStats['used_memory'] ?? 'N/A'],
                    ['Peak Memory', $cacheStats['used_memory_peak'] ?? 'N/A'],
                    ['Hit Rate', $cacheStats['hit_rate'] ?? 'N/A'],
                ]
            );
        }
        
        // Database statistics
        if (isset($metrics['database_stats']['table_counts'])) {
            $this->newLine();
            $this->info('Database Statistics:');
            $this->table(
                ['Table', 'Record Count'],
                collect($metrics['database_stats']['table_counts'])
                    ->map(fn($count, $table) => [$table, number_format($count)])
                    ->toArray()
            );
        }
        
        // Recommendations
        if (isset($metrics['recommendations']) && !empty($metrics['recommendations'])) {
            $this->newLine();
            $this->info('Performance Recommendations:');
            foreach ($metrics['recommendations'] as $recommendation) {
                $priority = strtoupper($recommendation['priority']);
                $type = strtoupper($recommendation['type']);
                $this->line("  [{$priority}] [{$type}] {$recommendation['message']}");
            }
        } else {
            $this->newLine();
            $this->info('✓ No performance issues detected');
        }
        
        return 0;
    }

    /**
     * Run all optimizations
     */
    private function optimizeAll(bool $force): int
    {
        if (!$force && !$this->confirm('Run all forum optimizations? This may take a while.')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Running comprehensive forum optimization...');
        
        // Step 1: Create indexes
        $this->info('Step 1: Creating database indexes...');
        if ($this->createIndexes(true) !== 0) {
            $this->error('Failed to create indexes');
            return 1;
        }
        
        // Step 2: Analyze tables
        $this->info('Step 2: Analyzing database tables...');
        $this->analyzeTables();
        
        // Step 3: Update table statistics
        $this->info('Step 3: Updating table statistics...');
        $this->updateTableStatistics();
        
        // Step 4: Preload popular content
        $this->info('Step 4: Preloading popular content...');
        $this->performanceService->preloadPopularContent();
        
        $this->info('✓ Forum optimization completed successfully');
        
        // Show final metrics
        $this->newLine();
        $this->showMetrics();
        
        return 0;
    }

    /**
     * Analyze database tables
     */
    private function analyzeTables(): void
    {
        $tables = [
            'forums',
            'forum_threads',
            'forum_posts',
            'forum_votes',
            'forum_user_reputation',
            'forum_badges',
            'forum_user_badges',
        ];
        
        foreach ($tables as $table) {
            try {
                if (DB::connection()->getDriverName() === 'mysql') {
                    DB::statement("ANALYZE TABLE {$table}");
                }
            } catch (\Exception $e) {
                $this->warn("Failed to analyze table {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Update table statistics
     */
    private function updateTableStatistics(): void
    {
        try {
            if (DB::connection()->getDriverName() === 'mysql') {
                // Update statistics for better query planning
                DB::statement('FLUSH TABLES');
                
                // Optimize tables
                $tables = [
                    'forums',
                    'forum_threads',
                    'forum_posts',
                    'forum_votes',
                ];
                
                foreach ($tables as $table) {
                    DB::statement("OPTIMIZE TABLE {$table}");
                }
            }
        } catch (\Exception $e) {
            $this->warn('Failed to update table statistics: ' . $e->getMessage());
        }
    }
}