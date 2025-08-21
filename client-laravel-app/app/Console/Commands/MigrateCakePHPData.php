<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MigrateCakePHPData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migration:migrate-cakephp-data 
                            {--source=exports : Source directory for exported data}
                            {--validate : Validate data after migration}
                            {--dry-run : Show what would be done without executing}
                            {--tables=* : Specific tables to migrate}';

    /**
     * The console command description.
     */
    protected $description = 'Import CakePHP exported data into client Laravel application';

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting CakePHP Data Import for Client Application');
        $this->info(str_repeat('=', 60));

        $sourceDir = storage_path('app/' . $this->option('source'));
        $tables = $this->option('tables');
        $validate = $this->option('validate');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual changes will be made');
        }

        // Validate source directory
        if (!is_dir($sourceDir)) {
            $this->error("Source directory not found: {$sourceDir}");
            return 1;
        }

        // Load export summary
        $summaryFile = "{$sourceDir}/export_summary.json";
        if (!file_exists($summaryFile)) {
            $this->error("Export summary not found. Please run export from admin application first.");
            return 1;
        }

        $exportSummary = json_decode(file_get_contents($summaryFile), true);
        $this->info("Found export from: " . $exportSummary['export_date']);

        // Determine tables to import
        $tablesToImport = empty($tables) ? $this->importOrder : $tables;
        $availableTables = array_keys($exportSummary['tables']);
        $tablesToImport = array_intersect($tablesToImport, $availableTables);

        if (empty($tablesToImport)) {
            $this->error("No tables found to import.");
            return 1;
        }

        $this->info("Importing " . count($tablesToImport) . " tables...");

        $importSummary = [];
        $totalImported = 0;
        $totalErrors = 0;

        // Disable foreign key checks during import
        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        try {
            foreach ($tablesToImport as $table) {
                $this->info("\n📥 Importing table: {$table}");

                $result = $this->importTable(
                    $table,
                    $sourceDir,
                    $exportSummary['export_format'],
                    $dryRun
                );

                $importSummary[$table] = $result;
                $totalImported += $result['imported'];
                $totalErrors += $result['errors'];

                if ($result['errors'] > 0) {
                    $this->warn("⚠ {$table}: {$result['imported']} imported, {$result['errors']} errors");
                } else {
                    $this->info("✅ {$table}: {$result['imported']} records imported");
                }
            }
        } finally {
            // Re-enable foreign key checks
            if (!$dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        // Validate data if requested
        if ($validate && !$dryRun) {
            $this->info("\n✅ Validating imported data...");
            $this->validateImportedData();
        }

        // Create import summary
        $this->createImportSummary($sourceDir, $importSummary, $totalImported, $totalErrors, $dryRun);

        $this->info("\n" . str_repeat('=', 50));
        if ($dryRun) {
            $this->info("🔍 DRY RUN completed!");
            $this->info("Would import: {$totalImported} records");
        } else {
            $this->info("🎉 Import completed!");
            $this->info("Total records imported: {$totalImported}");
        }

        if ($totalErrors > 0) {
            $this->warn("⚠️  Total errors: {$totalErrors}");
        }

        $this->info(str_repeat('=', 50));

        return $totalErrors > 0 ? 1 : 0;
    }

    /**
     * Import a single table
     */
    protected function importTable(string $table, string $sourceDir, string $format, bool $dryRun): array
    {
        $imported = 0;
        $errors = 0;
        $errorDetails = [];

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
        $chunkSize = 500;
        $chunks = array_chunk($data, $chunkSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            foreach ($chunk as $recordIndex => $record) {
                try {
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
     * Validate imported data
     */
    protected function validateImportedData(): void
    {
        $validationResults = [];

        // Check for basic data integrity
        $tables = ['users', 'organizations', 'events', 'resources'];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count = DB::table($table)->count();
                $validationResults[$table] = $count;
                $this->info("  {$table}: {$count} records");
            }
        }

        // Check for foreign key integrity
        $this->validateForeignKeys();
    }

    /**
     * Validate foreign key relationships
     */
    protected function validateForeignKeys(): void
    {
        $this->info("  Checking foreign key relationships...");

        // Check organization-user relationships
        if (DB::getSchemaBuilder()->hasTable('organization_user')) {
            $invalidRelationships = DB::table('organization_user')
                ->leftJoin('users', 'organization_user.user_id', '=', 'users.id')
                ->leftJoin('organizations', 'organization_user.organization_id', '=', 'organizations.id')
                ->whereNull('users.id')
                ->orWhereNull('organizations.id')
                ->count();

            if ($invalidRelationships > 0) {
                $this->warn("  ⚠️  Found {$invalidRelationships} invalid organization-user relationships");
            } else {
                $this->info("  ✅ Organization-user relationships are valid");
            }
        }

        // Check resource relationships
        if (DB::getSchemaBuilder()->hasTable('resources')) {
            $invalidResources = DB::table('resources')
                ->leftJoin('organizations', 'resources.organization_id', '=', 'organizations.id')
                ->whereNull('organizations.id')
                ->count();

            if ($invalidResources > 0) {
                $this->warn("  ⚠️  Found {$invalidResources} resources with invalid organization references");
            } else {
                $this->info("  ✅ Resource-organization relationships are valid");
            }
        }
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
            'application' => 'client',
            'dry_run' => $dryRun,
            'total_imported' => $totalImported,
            'total_errors' => $totalErrors,
            'tables_processed' => count($summary),
            'tables' => $summary,
        ];

        $summaryFile = "{$sourceDir}/client_import_summary.json";
        file_put_contents($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT));

        // Create readable summary
        $readableSummary = $this->createReadableImportSummary($summaryData);
        $readableFile = "{$sourceDir}/client_import_summary.txt";
        file_put_contents($readableFile, $readableSummary);

        $this->info("\n📄 Import summary saved to:");
        $this->info("  JSON: {$summaryFile}");
        $this->info("  Text: {$readableFile}");
    }

    /**
     * Create readable import summary
     */
    protected function createReadableImportSummary(array $data): string
    {
        $summary = "CakePHP to Laravel Data Import Summary (Client Application)\n";
        $summary .= str_repeat('=', 60) . "\n\n";
        $summary .= "Import Date: {$data['import_date']}\n";
        $summary .= "Application: " . ucfirst($data['application']) . "\n";
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
}