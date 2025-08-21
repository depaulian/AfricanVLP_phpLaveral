<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CakePHPDataSeeder extends Seeder
{
    /**
     * Seeding order to respect foreign key constraints
     */
    protected array $seedingOrder = [
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
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting CakePHP data seeding...');
        
        // Check if export data exists
        $exportPath = storage_path('app/exports');
        if (!is_dir($exportPath)) {
            $this->command->error('Export directory not found. Please run the export command first.');
            return;
        }

        $summaryFile = "{$exportPath}/export_summary.json";
        if (!file_exists($summaryFile)) {
            $this->command->error('Export summary not found. Please run the export command first.');
            return;
        }

        $exportSummary = json_decode(file_get_contents($summaryFile), true);
        $this->command->info("Found export from: " . $exportSummary['export_date']);

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $totalSeeded = 0;
        $seedingSummary = [];

        try {
            foreach ($this->seedingOrder as $table) {
                if (!isset($exportSummary['tables'][$table])) {
                    continue;
                }

                $this->command->info("Seeding table: {$table}");
                
                $seededCount = $this->seedTable($table, $exportPath, $exportSummary['export_format']);
                $seedingSummary[$table] = $seededCount;
                $totalSeeded += $seededCount;

                $this->command->info("✓ Seeded {$seededCount} records in {$table}");
            }
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->command->info("\nSeeding completed!");
        $this->command->info("Total records seeded: {$totalSeeded}");

        // Create seeding summary
        $this->createSeedingSummary($exportPath, $seedingSummary, $totalSeeded);
    }

    /**
     * Seed a single table
     */
    protected function seedTable(string $table, string $exportPath, string $format): int
    {
        $dataFile = "{$exportPath}/{$table}.{$format}";
        
        if (!file_exists($dataFile)) {
            $this->command->warn("Data file not found for table: {$table}");
            return 0;
        }

        $data = $this->loadDataFile($dataFile, $format);
        
        if (empty($data)) {
            return 0;
        }

        $seededCount = 0;
        $chunkSize = 500;

        // Process data in chunks
        $chunks = array_chunk($data, $chunkSize);

        foreach ($chunks as $chunk) {
            $transformedChunk = [];

            foreach ($chunk as $record) {
                $transformedRecord = $this->transformRecord($table, $record);
                if ($transformedRecord) {
                    $transformedChunk[] = $transformedRecord;
                }
            }

            if (!empty($transformedChunk)) {
                try {
                    DB::table($table)->insert($transformedChunk);
                    $seededCount += count($transformedChunk);
                } catch (\Exception $e) {
                    $this->command->error("Error seeding chunk in {$table}: " . $e->getMessage());
                    
                    // Try inserting records one by one
                    foreach ($transformedChunk as $record) {
                        try {
                            DB::table($table)->insert($record);
                            $seededCount++;
                        } catch (\Exception $e) {
                            $this->command->warn("Skipped record in {$table}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $seededCount;
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
    protected function transformRecord(string $table, array $record): ?array
    {
        // Skip records with missing required fields
        if ($this->hasRequiredFieldsMissing($table, $record)) {
            return null;
        }

        $transformed = [];

        foreach ($record as $key => $value) {
            // Handle null values
            if (is_null($value) || $value === '') {
                $transformed[$key] = null;
                continue;
            }

            // Handle timestamps
            if (in_array($key, ['created_at', 'updated_at'])) {
                try {
                    $transformed[$key] = Carbon::parse($value)->toDateTimeString();
                } catch (\Exception $e) {
                    $transformed[$key] = now()->toDateTimeString();
                }
                continue;
            }

            // Handle boolean fields
            if ($this->isBooleanField($table, $key)) {
                $transformed[$key] = (bool) $value;
                continue;
            }

            // Handle JSON fields
            if ($this->isJsonField($table, $key)) {
                if (is_string($value) && $this->isJson($value)) {
                    $transformed[$key] = json_decode($value, true);
                } elseif (is_array($value)) {
                    $transformed[$key] = $value;
                } else {
                    $transformed[$key] = null;
                }
                continue;
            }

            // Handle numeric fields
            if ($this->isNumericField($table, $key)) {
                $transformed[$key] = is_numeric($value) ? $value : null;
                continue;
            }

            // Default: keep as string
            $transformed[$key] = (string) $value;
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
     * Check if record has missing required fields
     */
    protected function hasRequiredFieldsMissing(string $table, array $record): bool
    {
        $requiredFields = [
            'users' => ['name', 'email'],
            'organizations' => ['name'],
            'events' => ['title', 'start_date'],
            'resources' => ['title'],
            'news' => ['title'],
            'blog_posts' => ['title'],
        ];

        if (!isset($requiredFields[$table])) {
            return false;
        }

        foreach ($requiredFields[$table] as $field) {
            if (!isset($record[$field]) || empty($record[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if field is boolean
     */
    protected function isBooleanField(string $table, string $field): bool
    {
        $booleanFields = [
            'users' => ['email_verified', 'active', 'is_admin'],
            'organizations' => ['active', 'verified', 'featured'],
            'events' => ['active', 'featured', 'registration_required'],
            'resources' => ['active', 'featured', 'downloadable'],
            'news' => ['published', 'featured'],
            'blog_posts' => ['published', 'featured'],
        ];

        return isset($booleanFields[$table]) && in_array($field, $booleanFields[$table]);
    }

    /**
     * Check if field is JSON
     */
    protected function isJsonField(string $table, string $field): bool
    {
        $jsonFields = [
            'users' => ['preferences', 'volunteering_interests', 'skills', 'volunteering_history'],
            'organizations' => ['contact_info', 'settings', 'social_media'],
            'events' => ['metadata', 'registration_fields'],
            'resources' => ['metadata', 'tags'],
        ];

        return isset($jsonFields[$table]) && in_array($field, $jsonFields[$table]);
    }

    /**
     * Check if field is numeric
     */
    protected function isNumericField(string $table, string $field): bool
    {
        $numericFields = [
            'events' => ['max_participants', 'current_participants'],
            'resources' => ['download_count', 'view_count'],
            'news' => ['view_count'],
            'blog_posts' => ['view_count'],
        ];

        return isset($numericFields[$table]) && in_array($field, $numericFields[$table]);
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
     * Create seeding summary
     */
    protected function createSeedingSummary(string $exportPath, array $summary, int $totalSeeded): void
    {
        $summaryData = [
            'seeding_date' => now()->toISOString(),
            'total_seeded' => $totalSeeded,
            'tables_seeded' => count($summary),
            'tables' => $summary,
        ];

        $summaryFile = "{$exportPath}/seeding_summary.json";
        file_put_contents($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT));

        // Create readable summary
        $readableSummary = $this->createReadableSeedingSummary($summaryData);
        $readableFile = "{$exportPath}/seeding_summary.txt";
        file_put_contents($readableFile, $readableSummary);
    }

    /**
     * Create readable seeding summary
     */
    protected function createReadableSeedingSummary(array $data): string
    {
        $summary = "CakePHP Data Seeding Summary\n";
        $summary .= str_repeat('=', 35) . "\n\n";
        $summary .= "Seeding Date: {$data['seeding_date']}\n";
        $summary .= "Total Records Seeded: {$data['total_seeded']}\n";
        $summary .= "Tables Seeded: {$data['tables_seeded']}\n\n";

        $summary .= "Table Seeding Details:\n";
        $summary .= str_repeat('-', 25) . "\n";

        foreach ($data['tables'] as $table => $count) {
            $summary .= sprintf("%-25s: %d records\n", $table, $count);
        }

        return $summary;
    }
}