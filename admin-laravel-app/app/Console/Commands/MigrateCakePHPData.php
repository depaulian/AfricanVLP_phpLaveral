<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataValidationService;
use App\Services\DataBackupService;
use Illuminate\Support\Facades\Artisan;

class MigrateCakePHPData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migration:migrate-cakephp-data 
                            {--backup : Create backup before migration}
                            {--validate : Validate data after migration}
                            {--dry-run : Show what would be done without executing}
                            {--source=exports : Source directory for exported data}
                            {--connection=cakephp : CakePHP database connection}
                            {--skip-export : Skip export step (use existing export)}
                            {--skip-import : Skip import step (only export)}
                            {--tables=* : Specific tables to migrate}';

    /**
     * The console command description.
     */
    protected $description = 'Complete CakePHP to Laravel data migration workflow';

    protected DataValidationService $validationService;
    protected DataBackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(DataValidationService $validationService, DataBackupService $backupService)
    {
        parent::__construct();
        $this->validationService = $validationService;
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting CakePHP to Laravel Data Migration');
        $this->info(str_repeat('=', 60));

        $options = [
            'backup' => $this->option('backup'),
            'validate' => $this->option('validate'),
            'dry_run' => $this->option('dry-run'),
            'source' => $this->option('source'),
            'connection' => $this->option('connection'),
            'skip_export' => $this->option('skip-export'),
            'skip_import' => $this->option('skip-import'),
            'tables' => $this->option('tables'),
        ];

        if ($options['dry_run']) {
            $this->warn('🔍 DRY RUN MODE - No actual changes will be made');
        }

        $migrationSummary = [
            'start_time' => now(),
            'options' => $options,
            'steps' => [],
            'status' => 'in_progress',
        ];

        try {
            // Step 1: Pre-migration backup
            if ($options['backup'] && !$options['dry_run']) {
                $this->info("\n📦 Step 1: Creating pre-migration backup...");
                $backupResult = $this->createPreMigrationBackup();
                $migrationSummary['steps']['backup'] = $backupResult;
                
                if ($backupResult['status'] === 'failed') {
                    throw new \Exception('Pre-migration backup failed');
                }
            }

            // Step 2: Export CakePHP data
            if (!$options['skip_export']) {
                $this->info("\n📤 Step 2: Exporting CakePHP data...");
                $exportResult = $this->exportCakePHPData($options);
                $migrationSummary['steps']['export'] = $exportResult;
                
                if ($exportResult['status'] === 'failed') {
                    throw new \Exception('Data export failed');
                }
            }

            // Step 3: Import data into Laravel
            if (!$options['skip_import']) {
                $this->info("\n📥 Step 3: Importing data into Laravel...");
                $importResult = $this->importDataToLaravel($options);
                $migrationSummary['steps']['import'] = $importResult;
                
                if ($importResult['status'] === 'failed') {
                    throw new \Exception('Data import failed');
                }
            }

            // Step 4: Validate data integrity
            if ($options['validate'] && !$options['dry_run']) {
                $this->info("\n✅ Step 4: Validating data integrity...");
                $validationResult = $this->validateDataIntegrity();
                $migrationSummary['steps']['validation'] = $validationResult;
                
                if ($validationResult['overall_status'] === 'failed') {
                    $this->warn('⚠️  Data validation found issues - please review');
                }
            }

            $migrationSummary['status'] = 'completed';
            $migrationSummary['end_time'] = now();

        } catch (\Exception $e) {
            $migrationSummary['status'] = 'failed';
            $migrationSummary['error'] = $e->getMessage();
            $migrationSummary['end_time'] = now();

            $this->error("\n❌ Migration failed: " . $e->getMessage());
            
            // Create failure summary
            $this->createMigrationSummary($migrationSummary);
            
            return 1;
        }

        // Create success summary
        $this->createMigrationSummary($migrationSummary);
        $this->displayMigrationSummary($migrationSummary);

        return 0;
    }

    /**
     * Create pre-migration backup
     */
    protected function createPreMigrationBackup(): array
    {
        try {
            $backupName = 'pre_migration_' . now()->format('Y_m_d_H_i_s');
            $result = $this->backupService->createFullBackup($backupName);
            
            $this->info("✅ Backup created: {$backupName}");
            $this->info("   Records backed up: " . number_format($result['total_records']));
            $this->info("   Backup size: " . $this->formatBytes($result['backup_size']));
            
            return [
                'status' => 'completed',
                'backup_name' => $backupName,
                'total_records' => $result['total_records'],
                'backup_size' => $result['backup_size'],
            ];
            
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export CakePHP data
     */
    protected function exportCakePHPData(array $options): array
    {
        try {
            $command = 'migration:export-cakephp-data';
            $parameters = [
                '--connection' => $options['connection'],
                '--output' => $options['source'],
                '--format' => 'json',
                '--chunk' => 1000,
            ];

            if (!empty($options['tables'])) {
                $parameters['--tables'] = $options['tables'];
            }

            $exitCode = Artisan::call($command, $parameters);
            
            if ($exitCode === 0) {
                $this->info("✅ Data export completed successfully");
                return [
                    'status' => 'completed',
                    'exit_code' => $exitCode,
                ];
            } else {
                throw new \Exception('Export command returned non-zero exit code: ' . $exitCode);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Export failed: " . $e->getMessage());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import data to Laravel
     */
    protected function importDataToLaravel(array $options): array
    {
        try {
            $command = 'migration:import-cakephp-data';
            $parameters = [
                '--source' => $options['source'],
                '--validate' => true,
                '--chunk' => 500,
                '--skip-existing' => false,
            ];

            if ($options['dry_run']) {
                $parameters['--dry-run'] = true;
            }

            if (!empty($options['tables'])) {
                $parameters['--tables'] = $options['tables'];
            }

            $exitCode = Artisan::call($command, $parameters);
            
            if ($exitCode === 0) {
                $this->info("✅ Data import completed successfully");
                return [
                    'status' => 'completed',
                    'exit_code' => $exitCode,
                ];
            } else {
                $this->warn("⚠️  Import completed with warnings (exit code: {$exitCode})");
                return [
                    'status' => 'completed_with_warnings',
                    'exit_code' => $exitCode,
                ];
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Import failed: " . $e->getMessage());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate data integrity
     */
    protected function validateDataIntegrity(): array
    {
        try {
            $this->info("Running comprehensive data validation...");
            
            $validationResults = $this->validationService->validateDataIntegrity();
            
            $this->displayValidationResults($validationResults);
            
            return [
                'status' => 'completed',
                'overall_status' => $validationResults['overall_status'],
                'total_checks' => $validationResults['total_checks'],
                'passed_checks' => $validationResults['passed_checks'],
                'failed_checks' => $validationResults['failed_checks'],
                'warnings' => $validationResults['warnings'],
            ];
            
        } catch (\Exception $e) {
            $this->error("❌ Validation failed: " . $e->getMessage());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display validation results
     */
    protected function displayValidationResults(array $results): void
    {
        $this->info("\n📊 Validation Results:");
        $this->info("   Total checks: {$results['total_checks']}");
        $this->info("   Passed: {$results['passed_checks']}");
        
        if ($results['failed_checks'] > 0) {
            $this->error("   Failed: {$results['failed_checks']}");
        }
        
        if ($results['warnings'] > 0) {
            $this->warn("   Warnings: {$results['warnings']}");
        }

        $successRate = $results['total_checks'] > 0 
            ? round(($results['passed_checks'] / $results['total_checks']) * 100, 1)
            : 0;
        
        $this->info("   Success rate: {$successRate}%");

        // Show specific issues if any
        if ($results['failed_checks'] > 0 || $results['warnings'] > 0) {
            $this->warn("\n⚠️  Issues found during validation:");
            
            foreach ($results['checks'] as $checkName => $checkResult) {
                if ($checkResult['status'] === 'failed') {
                    $this->error("   ❌ {$checkName}: {$checkResult['message']}");
                } elseif ($checkResult['status'] === 'warning') {
                    $this->warn("   ⚠️  {$checkName}: {$checkResult['message']}");
                }
            }
        }
    }

    /**
     * Create migration summary
     */
    protected function createMigrationSummary(array $summary): void
    {
        $summaryPath = storage_path('app/migration_summary_' . now()->format('Y_m_d_H_i_s') . '.json');
        file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT));

        // Create readable summary
        $readableSummary = $this->createReadableMigrationSummary($summary);
        $readablePath = storage_path('app/migration_summary_' . now()->format('Y_m_d_H_i_s') . '.txt');
        file_put_contents($readablePath, $readableSummary);

        $this->info("\n📄 Migration summary saved to:");
        $this->info("   JSON: {$summaryPath}");
        $this->info("   Text: {$readablePath}");
    }

    /**
     * Display migration summary
     */
    protected function displayMigrationSummary(array $summary): void
    {
        $this->info("\n🎉 Migration Summary:");
        $this->info(str_repeat('=', 30));
        
        $duration = $summary['end_time']->diffInSeconds($summary['start_time']);
        $this->info("Status: " . ucfirst($summary['status']));
        $this->info("Duration: {$duration} seconds");
        
        foreach ($summary['steps'] as $step => $result) {
            $status = $result['status'] ?? 'unknown';
            $icon = $status === 'completed' ? '✅' : ($status === 'failed' ? '❌' : '⚠️');
            $this->info("{$icon} {$step}: " . ucfirst($status));
        }

        if ($summary['status'] === 'completed') {
            $this->info("\n🎊 Migration completed successfully!");
            $this->info("Your CakePHP data has been successfully migrated to Laravel.");
        }
    }

    /**
     * Create readable migration summary
     */
    protected function createReadableMigrationSummary(array $summary): string
    {
        $text = "CakePHP to Laravel Data Migration Summary\n";
        $text .= str_repeat('=', 50) . "\n\n";
        
        $text .= "Migration Status: " . ucfirst($summary['status']) . "\n";
        $text .= "Start Time: " . $summary['start_time']->toDateTimeString() . "\n";
        $text .= "End Time: " . $summary['end_time']->toDateTimeString() . "\n";
        
        $duration = $summary['end_time']->diffInSeconds($summary['start_time']);
        $text .= "Duration: {$duration} seconds\n\n";
        
        $text .= "Migration Options:\n";
        $text .= str_repeat('-', 20) . "\n";
        foreach ($summary['options'] as $option => $value) {
            $text .= sprintf("%-15s: %s\n", $option, is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
        }
        
        $text .= "\nStep Results:\n";
        $text .= str_repeat('-', 15) . "\n";
        foreach ($summary['steps'] as $step => $result) {
            $status = $result['status'] ?? 'unknown';
            $text .= sprintf("%-15s: %s\n", ucfirst($step), ucfirst($status));
            
            if (isset($result['error'])) {
                $text .= "  Error: {$result['error']}\n";
            }
        }
        
        if (isset($summary['error'])) {
            $text .= "\nError Details:\n";
            $text .= str_repeat('-', 15) . "\n";
            $text .= $summary['error'] . "\n";
        }
        
        return $text;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}