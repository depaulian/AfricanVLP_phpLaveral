<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportCakePHPData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migration:export-cakephp-data 
                            {--connection=cakephp : Database connection to use}
                            {--tables=* : Specific tables to export (optional)}
                            {--output=exports : Output directory}
                            {--format=json : Export format (json, csv, sql)}
                            {--chunk=1000 : Chunk size for large tables}';

    /**
     * The console command description.
     */
    protected $description = 'Export data from CakePHP database for Laravel migration';

    /**
     * Tables to export with their Laravel equivalents
     */
    protected array $tableMapping = [
        'users' => 'users',
        'organizations' => 'organizations',
        'organization_users' => 'organization_user',
        'tmp_organization_users' => 'tmp_organization_users',
        'events' => 'events',
        'event_participants' => 'event_participants',
        'news' => 'news',
        'blog_posts' => 'blog_posts',
        'resources' => 'resources',
        'resource_files' => 'resource_files',
        'categories_of_resources' => 'category_of_resources',
        'resource_types' => 'resource_types',
        'notifications' => 'notifications',
        'messages' => 'messages',
        'forums' => 'forums',
        'forum_posts' => 'forum_posts',
        'volunteering_opportunities' => 'volunteering_opportunities',
        'volunteering_applications' => 'volunteering_applications',
        'geographic_data' => 'geographic_data',
        'translations' => 'translations',
        'newsletters' => 'newsletters',
        'newsletter_subscriptions' => 'newsletter_subscriptions',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting CakePHP data export...');
        
        $connection = $this->option('connection');
        $tables = $this->option('tables');
        $outputDir = $this->option('output');
        $format = $this->option('format');
        $chunkSize = (int) $this->option('chunk');
        
        // Validate connection
        if (!$this->validateConnection($connection)) {
            $this->error("Cannot connect to database: {$connection}");
            return 1;
        }
        
        // Create output directory
        $exportPath = storage_path("app/{$outputDir}");
        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0755, true);
        }
        
        // Determine tables to export
        $tablesToExport = empty($tables) ? array_keys($this->tableMapping) : $tables;
        
        $this->info("Exporting " . count($tablesToExport) . " tables...");
        
        $exportSummary = [];
        $totalRecords = 0;
        
        foreach ($tablesToExport as $table) {
            try {
                $this->info("Exporting table: {$table}");
                
                $recordCount = $this->exportTable($table, $connection, $exportPath, $format, $chunkSize);
                $exportSummary[$table] = $recordCount;
                $totalRecords += $recordCount;
                
                $this->info("✓ Exported {$recordCount} records from {$table}");
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to export {$table}: " . $e->getMessage());
                $exportSummary[$table] = 'ERROR: ' . $e->getMessage();
            }
        }
        
        // Create export summary
        $this->createExportSummary($exportPath, $exportSummary, $totalRecords);
        
        $this->info("\n" . str_repeat('=', 50));
        $this->info("Export completed!");
        $this->info("Total records exported: {$totalRecords}");
        $this->info("Export location: {$exportPath}");
        $this->info(str_repeat('=', 50));
        
        return 0;
    }
    
    /**
     * Validate database connection
     */
    protected function validateConnection(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Export a single table
     */
    protected function exportTable(string $table, string $connection, string $exportPath, string $format, int $chunkSize): int
    {
        $totalRecords = 0;
        $fileIndex = 1;
        
        // Get table structure first
        $this->exportTableStructure($table, $connection, $exportPath);
        
        // Export data in chunks
        DB::connection($connection)
            ->table($table)
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($table, $exportPath, $format, &$totalRecords, &$fileIndex) {
                $fileName = $this->getExportFileName($table, $format, $fileIndex);
                $filePath = "{$exportPath}/{$fileName}";
                
                switch ($format) {
                    case 'json':
                        $this->exportToJson($records, $filePath, $fileIndex === 1);
                        break;
                    case 'csv':
                        $this->exportToCsv($records, $filePath, $fileIndex === 1);
                        break;
                    case 'sql':
                        $this->exportToSql($records, $table, $filePath, $fileIndex === 1);
                        break;
                }
                
                $totalRecords += count($records);
                $fileIndex++;
            });
        
        return $totalRecords;
    }
    
    /**
     * Export table structure
     */
    protected function exportTableStructure(string $table, string $connection, string $exportPath): void
    {
        try {
            $columns = DB::connection($connection)
                ->select("DESCRIBE {$table}");
            
            $structure = [
                'table' => $table,
                'laravel_table' => $this->tableMapping[$table] ?? $table,
                'columns' => $columns,
                'exported_at' => now()->toISOString(),
            ];
            
            $structureFile = "{$exportPath}/{$table}_structure.json";
            file_put_contents($structureFile, json_encode($structure, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->warn("Could not export structure for {$table}: " . $e->getMessage());
        }
    }
    
    /**
     * Get export file name
     */
    protected function getExportFileName(string $table, string $format, int $index): string
    {
        $suffix = $index > 1 ? "_{$index}" : '';
        return "{$table}{$suffix}.{$format}";
    }
    
    /**
     * Export to JSON format
     */
    protected function exportToJson($records, string $filePath, bool $isFirst): void
    {
        $data = $records->toArray();
        
        // Transform data for Laravel compatibility
        $transformedData = array_map([$this, 'transformRecord'], $data);
        
        if ($isFirst) {
            file_put_contents($filePath, json_encode($transformedData, JSON_PRETTY_PRINT));
        } else {
            // Append to existing file
            $existingData = json_decode(file_get_contents($filePath), true);
            $mergedData = array_merge($existingData, $transformedData);
            file_put_contents($filePath, json_encode($mergedData, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Export to CSV format
     */
    protected function exportToCsv($records, string $filePath, bool $isFirst): void
    {
        $mode = $isFirst ? 'w' : 'a';
        $handle = fopen($filePath, $mode);
        
        if ($isFirst && !empty($records)) {
            // Write headers
            $headers = array_keys((array) $records->first());
            fputcsv($handle, $headers);
        }
        
        foreach ($records as $record) {
            $transformedRecord = $this->transformRecord((array) $record);
            fputcsv($handle, $transformedRecord);
        }
        
        fclose($handle);
    }
    
    /**
     * Export to SQL format
     */
    protected function exportToSql($records, string $table, string $filePath, bool $isFirst): void
    {
        $mode = $isFirst ? 'w' : 'a';
        $handle = fopen($filePath, $mode);
        
        if ($isFirst) {
            fwrite($handle, "-- Data export for table: {$table}\n");
            fwrite($handle, "-- Generated on: " . now()->toDateTimeString() . "\n\n");
        }
        
        $laravelTable = $this->tableMapping[$table] ?? $table;
        
        foreach ($records as $record) {
            $transformedRecord = $this->transformRecord((array) $record);
            $columns = array_keys($transformedRecord);
            $values = array_map(function ($value) {
                return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
            }, array_values($transformedRecord));
            
            $sql = "INSERT INTO `{$laravelTable}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            fwrite($handle, $sql);
        }
        
        fclose($handle);
    }
    
    /**
     * Transform record for Laravel compatibility
     */
    protected function transformRecord(array $record): array
    {
        $transformed = [];
        
        foreach ($record as $key => $value) {
            // Transform CakePHP conventions to Laravel conventions
            $newKey = $this->transformColumnName($key);
            $newValue = $this->transformColumnValue($key, $value);
            
            $transformed[$newKey] = $newValue;
        }
        
        return $transformed;
    }
    
    /**
     * Transform column names from CakePHP to Laravel conventions
     */
    protected function transformColumnName(string $columnName): string
    {
        // CakePHP uses 'created' and 'modified', Laravel uses 'created_at' and 'updated_at'
        $columnMapping = [
            'created' => 'created_at',
            'modified' => 'updated_at',
        ];
        
        return $columnMapping[$columnName] ?? $columnName;
    }
    
    /**
     * Transform column values for Laravel compatibility
     */
    protected function transformColumnValue(string $columnName, $value)
    {
        // Handle null values
        if (is_null($value)) {
            return null;
        }
        
        // Handle datetime fields
        if (in_array($columnName, ['created', 'modified']) || str_ends_with($columnName, '_at')) {
            try {
                return Carbon::parse($value)->toDateTimeString();
            } catch (\Exception $e) {
                return $value;
            }
        }
        
        // Handle boolean fields
        if (is_numeric($value) && in_array($value, [0, 1])) {
            $booleanFields = ['active', 'enabled', 'published', 'verified', 'deleted'];
            foreach ($booleanFields as $field) {
                if (str_contains($columnName, $field)) {
                    return (bool) $value;
                }
            }
        }
        
        // Handle JSON fields
        if (is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }
        
        return $value;
    }
    
    /**
     * Check if string is valid JSON
     */
    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Create export summary
     */
    protected function createExportSummary(string $exportPath, array $summary, int $totalRecords): void
    {
        $summaryData = [
            'export_date' => now()->toISOString(),
            'total_records' => $totalRecords,
            'tables_exported' => count($summary),
            'export_format' => $this->option('format'),
            'chunk_size' => $this->option('chunk'),
            'tables' => $summary,
            'laravel_table_mapping' => $this->tableMapping,
        ];
        
        $summaryFile = "{$exportPath}/export_summary.json";
        file_put_contents($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT));
        
        // Also create a readable summary
        $readableSummary = $this->createReadableSummary($summaryData);
        $readableFile = "{$exportPath}/export_summary.txt";
        file_put_contents($readableFile, $readableSummary);
    }
    
    /**
     * Create readable summary
     */
    protected function createReadableSummary(array $data): string
    {
        $summary = "CakePHP to Laravel Data Export Summary\n";
        $summary .= str_repeat('=', 50) . "\n\n";
        $summary .= "Export Date: {$data['export_date']}\n";
        $summary .= "Total Records: {$data['total_records']}\n";
        $summary .= "Tables Exported: {$data['tables_exported']}\n";
        $summary .= "Export Format: {$data['export_format']}\n";
        $summary .= "Chunk Size: {$data['chunk_size']}\n\n";
        
        $summary .= "Table Export Details:\n";
        $summary .= str_repeat('-', 30) . "\n";
        
        foreach ($data['tables'] as $table => $count) {
            $laravelTable = $data['laravel_table_mapping'][$table] ?? $table;
            $summary .= sprintf("%-25s -> %-25s (%s records)\n", $table, $laravelTable, $count);
        }
        
        $summary .= "\nTable Mapping (CakePHP -> Laravel):\n";
        $summary .= str_repeat('-', 40) . "\n";
        
        foreach ($data['laravel_table_mapping'] as $cakephp => $laravel) {
            $summary .= sprintf("%-25s -> %s\n", $cakephp, $laravel);
        }
        
        return $summary;
    }
}