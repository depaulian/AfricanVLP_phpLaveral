<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ImportCakePHPData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migration:import-cakephp-data 
                            {--source=exports : Source directory containing exported data}
                            {--tables=* : Specific tables to import (optional)}
                            {--validate : Validate data before importing}
                            {--dry-run : Show what would be imported without actually importing}
                            {--chunk=500 : Chunk size for imports}
                            {--skip-existing : Skip records that already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Import CakePHP exported data into Laravel database';

    /**
     * Import order to respect foreign key constraints
     */
    protected array $importOrder = [
        'users',
        'organizations',
        'category_of_resources',
        'resource_types',
        'resources',
        'resource_files',
        'events',
        'news',
        'blog_posts',
        'notifications',
        'messages',
        'forums',
        'forum_posts',
        'organization_user',
        'tmp_organization_users',
        'event_participants',
        'volunteering_opportunities',
        'volunteering_applications',
        'geographic_data',
        'translations',
        'newsletters',
        'newsletter_subscriptions',
    ];

    /**
     * Validation rules for each table
     */
    protected array $validationRules = [
        'users' => [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'password' => 'required|string',
        ],
        'organizations' => [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
        ],
        'resources' => [
            'title' => 'required|string|max:255',
            'organization_id' => 'required|integer',
        ],
        'events' => [
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'organization_id' => 'required|integer',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting CakePHP data import...');
        
        $sourceDir = storage_path('app/' . $this->option('source'));
        $tables = $this->option('tables');
        $validate = $this->option('validate');
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $skipExisting = $this->option('skip-existing');
        
        // Validate source directory
        if (!is_dir($sourceDir)) {
            $this->error("Source directory not found: {$sourceDir}");
            return 1;
        }
        
        // Load export summary
        $summaryFile = "{$sourceDir}/export_summary.json";
        if (!file_exists($summaryFile)) {
            $this->error("Export summary not found. Please run export command first.");
            return 1;
        }
        
        $exportSummary = json_decode(file_get_contents($summaryFile), true);
        $this->info("Found export from: " . $exportSummary['export_date']);
        
        // Determine tables to import
        $tablesToImport = empty($tables) ? $this->importOrder : $tables;
        
        // Filter tables that exist in export
        $availableTables = array_keys($exportSummary['tables']);
        $tablesToImport = array_intersect($tablesToImport, $availableTables);
        
        if (empty($tablesToImport)) {
            $this->error("No tables found to import.");
            return 1;
        }
        
        $this->info("Importing " . count($tablesToImport) . " tables...");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be actually imported");
        }
        
        $importSummary = [];
        $totalImported = 0;
        $totalErrors = 0;
        
        // Disable foreign key checks during import
        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        try {
            foreach ($tablesToImport as $table) {
                $this->info("\nImporting table: {$table}");
                
                $result = $this->importTable(
                    $table, 
                    $sourceDir, 
                    $exportSummary['export_format'], 
                    $validate, 
                    $dryRun, 
                    $chunkSize, 
                    $skipExisting
                );
                
                $importSummary[$table] = $result;
                $totalImported += $result['imported'];
                $totalErrors += $result['errors'];
                
                if ($result['errors'] > 0) {
                    $this->warn("⚠ {$table}: {$result['imported']} imported, {$result['errors']} errors");
                } else {
                    $this->info("✓ {$table}: {$result['imported']} records imported");
                }
            }
        } finally {
            // Re-enable foreign key checks
            if (!$dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
        
        // Create import summary
        $this->createImportSummary($sourceDir, $importSummary, $totalImported, $totalErrors, $dryRun);
        
        $this->info("\n" . str_repeat('=', 50));
        if ($dryRun) {
            $this->info("DRY RUN completed!");
            $this->info("Would import: {$totalImported} records");
        } else {
            $this->info("Import completed!");
            $this->info("Total records imported: {$totalImported}");
        }
        
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }
        
        $this->info(str_repeat('=', 50));
        
        return $totalErrors > 0 ? 1 : 0;
    }
    
    /**
     * Import a single table
     */
    protected function importTable(
        string $table, 
        string $sourceDir, 
        string $format, 
        bool $validate, 
        bool $dryRun, 
        int $chunkSize, 
        bool $skipExisting
    ): array {
        $imported = 0;
        $errors = 0;
        $errorDetails = [];
        
        // Check if table exists in Laravel
        if (!Schema::hasTable($table)) {
            return [
                'imported' => 0,
                'errors' => 1,
                'error_details' => ["Table {$table} does not exist in Laravel database"]
            ];
        }
        
        // Load data file
        $dataFile = "{$sourceDir}/{$table}.{$format}";
        if (!file_exists($dataFile)) {
            return [
                'imported' => 0,
                'errors' => 1,
                'error_details' => ["Data file not found: {$dataFile}"]
            ];
        }
        
        $data = $this->loadDataFile($dataFile, $format);
        if (empty($data)) {
            return [
                'imported' => 0,
                'errors' => 0,
                'error_details' => []
            ];
        }
        
        // Process data in chunks
        $chunks = array_chunk($data, $chunkSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $this->info("Processing chunk " . ($chunkIndex + 1) . "/" . count($chunks));
            
            foreach ($chunk as $recordIndex => $record) {
                try {
                    // Validate record if requested
                    if ($validate && !$this->validateRecord($table, $record)) {
                        $errors++;
                        $errorDetails[] = "Validation failed for record {$recordIndex} in {$table}";
                        continue;
                    }
                    
                    // Check if record already exists
                    if ($skipExisting && $this->recordExists($table, $record)) {
                        continue;
                    }
                    
                    // Transform record for Laravel
                    $transformedRecord = $this->transformRecordForLaravel($table, $record);
                    
                    if (!$dryRun) {
                        // Insert record
                        DB::table($table)->insert($transformedRecord);
                    }
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors++;
                    $errorDetails[] = "Error importing record {$recordIndex} in {$table}: " . $e->getMessage();
                    
                    if ($errors > 100) {
                        $errorDetails[] = "Too many errors, stopping import for {$table}";
                        break 2;
                    }
                }
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'error_details' => $errorDetails
        ];
    }
    
    /**
     * Load data from file
     */
    protected function loadDataFile(string $filePath, string $format): array
    {
        switch ($format) {
            case 'json':
                return json_decode(file_get_contents($filePath), true) ?? [];
                
            case 'csv':
                return $this->loadCsvFile($filePath);
                
            case 'sql':
                // SQL files need to be executed, not loaded as data
                return [];
                
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }
    
    /**
     * Load CSV file
     */
    protected function loadCsvFile(string $filePath): array
    {
        $data = [];
        $headers = null;
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = $row;
                } else {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Validate record against rules
     */
    protected function validateRecord(string $table, array $record): bool
    {
        if (!isset($this->validationRules[$table])) {
            return true; // No validation rules defined
        }
        
        $validator = Validator::make($record, $this->validationRules[$table]);
        return !$validator->fails();
    }
    
    /**
     * Check if record already exists
     */
    protected function recordExists(string $table, array $record): bool
    {
        if (!isset($record['id'])) {
            return false;
        }
        
        return DB::table($table)->where('id', $record['id'])->exists();
    }
    
    /**
     * Transform record for Laravel compatibility
     */
    protected function transformRecordForLaravel(string $table, array $record): array
    {
        $transformed = [];
        
        foreach ($record as $key => $value) {
            // Skip null values for non-nullable fields
            if (is_null($value) && in_array($key, ['id', 'created_at', 'updated_at'])) {
                continue;
            }
            
            // Handle timestamps
            if (in_array($key, ['created_at', 'updated_at']) && !is_null($value)) {
                try {
                    $transformed[$key] = Carbon::parse($value)->toDateTimeString();
                } catch (\Exception $e) {
                    $transformed[$key] = now()->toDateTimeString();
                }
            } else {
                $transformed[$key] = $value;
            }
        }
        
        // Ensure required timestamps exist
        if (!isset($transformed['created_at'])) {
            $transformed['created_at'] = now()->toDateTimeString();
        }
        
        if (!isset($transformed['updated_at'])) {
            $transformed['updated_at'] = now()->toDateTimeString();
        }
        
        return $transformed;
    }
    
    /**
     * Create import summary
     */
    protected function createImportSummary(
        string $sourceDir, 
        array $summary, 
        int $totalImported, 
        int $totalErrors, 
        bool $dryRun
    ): void {
        $summaryData = [
            'import_date' => now()->toISOString(),
            'dry_run' => $dryRun,
            'total_imported' => $totalImported,
            'total_errors' => $totalErrors,
            'tables_processed' => count($summary),
            'tables' => $summary,
        ];
        
        $summaryFile = "{$sourceDir}/import_summary.json";
        file_put_contents($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT));
        
        // Create readable summary
        $readableSummary = $this->createReadableImportSummary($summaryData);
        $readableFile = "{$sourceDir}/import_summary.txt";
        file_put_contents($readableFile, $readableSummary);
        
        // Create error log if there were errors
        if ($totalErrors > 0) {
            $this->createErrorLog($sourceDir, $summary);
        }
    }
    
    /**
     * Create readable import summary
     */
    protected function createReadableImportSummary(array $data): string
    {
        $summary = "CakePHP to Laravel Data Import Summary\n";
        $summary .= str_repeat('=', 50) . "\n\n";
        $summary .= "Import Date: {$data['import_date']}\n";
        $summary .= "Dry Run: " . ($data['dry_run'] ? 'Yes' : 'No') . "\n";
        $summary .= "Total Records Imported: {$data['total_imported']}\n";
        $summary .= "Total Errors: {$data['total_errors']}\n";
        $summary .= "Tables Processed: {$data['tables_processed']}\n\n";
        
        $summary .= "Table Import Details:\n";
        $summary .= str_repeat('-', 30) . "\n";
        
        foreach ($data['tables'] as $table => $result) {
            $summary .= sprintf(
                "%-25s: %d imported, %d errors\n", 
                $table, 
                $result['imported'], 
                $result['errors']
            );
        }
        
        return $summary;
    }
    
    /**
     * Create error log
     */
    protected function createErrorLog(string $sourceDir, array $summary): void
    {
        $errorLog = "Import Error Log\n";
        $errorLog .= str_repeat('=', 30) . "\n\n";
        
        foreach ($summary as $table => $result) {
            if ($result['errors'] > 0 && isset($result['error_details'])) {
                $errorLog .= "Table: {$table}\n";
                $errorLog .= str_repeat('-', 20) . "\n";
                
                foreach ($result['error_details'] as $error) {
                    $errorLog .= "- {$error}\n";
                }
                
                $errorLog .= "\n";
            }
        }
        
        $errorFile = "{$sourceDir}/import_errors.log";
        file_put_contents($errorFile, $errorLog);
    }
}