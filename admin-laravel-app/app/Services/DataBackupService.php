<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DataBackupService
{
    /**
     * Create a full database backup
     */
    public function createFullBackup(string $backupName = null): array
    {
        $backupName = $backupName ?: 'backup_' . now()->format('Y_m_d_H_i_s');
        $backupPath = "backups/{$backupName}";
        
        // Create backup directory
        Storage::makeDirectory($backupPath);
        
        $tables = $this->getAllTables();
        $backupSummary = [
            'backup_name' => $backupName,
            'backup_date' => now()->toISOString(),
            'total_tables' => count($tables),
            'total_records' => 0,
            'tables' => [],
            'backup_size' => 0,
            'status' => 'in_progress',
        ];
        
        try {
            foreach ($tables as $table) {
                $tableBackup = $this->backupTable($table, $backupPath);
                $backupSummary['tables'][$table] = $tableBackup;
                $backupSummary['total_records'] += $tableBackup['record_count'];
            }
            
            // Create backup metadata
            $this->createBackupMetadata($backupPath, $backupSummary);
            
            // Calculate backup size
            $backupSummary['backup_size'] = $this->calculateBackupSize($backupPath);
            $backupSummary['status'] = 'completed';
            
            // Update metadata with final information
            $this->createBackupMetadata($backupPath, $backupSummary);
            
        } catch (\Exception $e) {
            $backupSummary['status'] = 'failed';
            $backupSummary['error'] = $e->getMessage();
            
            // Clean up failed backup
            Storage::deleteDirectory($backupPath);
            
            throw $e;
        }
        
        return $backupSummary;
    }
    
    /**
     * Create an incremental backup (only changed data)
     */
    public function createIncrementalBackup(string $lastBackupDate, string $backupName = null): array
    {
        $backupName = $backupName ?: 'incremental_' . now()->format('Y_m_d_H_i_s');
        $backupPath = "backups/{$backupName}";
        
        Storage::makeDirectory($backupPath);
        
        $tables = $this->getAllTables();
        $backupSummary = [
            'backup_name' => $backupName,
            'backup_type' => 'incremental',
            'backup_date' => now()->toISOString(),
            'since_date' => $lastBackupDate,
            'total_tables' => count($tables),
            'total_records' => 0,
            'tables' => [],
            'backup_size' => 0,
            'status' => 'in_progress',
        ];
        
        try {
            foreach ($tables as $table) {
                $tableBackup = $this->backupTableIncremental($table, $backupPath, $lastBackupDate);
                $backupSummary['tables'][$table] = $tableBackup;
                $backupSummary['total_records'] += $tableBackup['record_count'];
            }
            
            $this->createBackupMetadata($backupPath, $backupSummary);
            $backupSummary['backup_size'] = $this->calculateBackupSize($backupPath);
            $backupSummary['status'] = 'completed';
            
            $this->createBackupMetadata($backupPath, $backupSummary);
            
        } catch (\Exception $e) {
            $backupSummary['status'] = 'failed';
            $backupSummary['error'] = $e->getMessage();
            Storage::deleteDirectory($backupPath);
            throw $e;
        }
        
        return $backupSummary;
    }
    
    /**
     * Restore from backup
     */
    public function restoreFromBackup(string $backupName, array $options = []): array
    {
        $backupPath = "backups/{$backupName}";
        
        if (!Storage::exists("{$backupPath}/backup_metadata.json")) {
            throw new \Exception("Backup metadata not found for: {$backupName}");
        }
        
        $metadata = json_decode(Storage::get("{$backupPath}/backup_metadata.json"), true);
        
        $restoreSummary = [
            'backup_name' => $backupName,
            'restore_date' => now()->toISOString(),
            'total_tables' => 0,
            'total_records' => 0,
            'tables' => [],
            'status' => 'in_progress',
        ];
        
        $dryRun = $options['dry_run'] ?? false;
        $skipExisting = $options['skip_existing'] ?? false;
        $tablesToRestore = $options['tables'] ?? array_keys($metadata['tables']);
        
        if ($dryRun) {
            $restoreSummary['dry_run'] = true;
        }
        
        try {
            // Disable foreign key checks during restore
            if (!$dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }
            
            foreach ($tablesToRestore as $table) {
                if (!isset($metadata['tables'][$table])) {
                    continue;
                }
                
                $tableRestore = $this->restoreTable($table, $backupPath, $dryRun, $skipExisting);
                $restoreSummary['tables'][$table] = $tableRestore;
                $restoreSummary['total_records'] += $tableRestore['restored_count'];
                $restoreSummary['total_tables']++;
            }
            
            $restoreSummary['status'] = 'completed';
            
        } catch (\Exception $e) {
            $restoreSummary['status'] = 'failed';
            $restoreSummary['error'] = $e->getMessage();
            throw $e;
        } finally {
            // Re-enable foreign key checks
            if (!$dryRun) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
        
        // Create restore log
        $this->createRestoreLog($backupPath, $restoreSummary);
        
        return $restoreSummary;
    }
    
    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [];
        $backupDirectories = Storage::directories('backups');
        
        foreach ($backupDirectories as $backupDir) {
            $backupName = basename($backupDir);
            $metadataPath = "{$backupDir}/backup_metadata.json";
            
            if (Storage::exists($metadataPath)) {
                $metadata = json_decode(Storage::get($metadataPath), true);
                $backups[] = [
                    'name' => $backupName,
                    'date' => $metadata['backup_date'],
                    'type' => $metadata['backup_type'] ?? 'full',
                    'size' => $metadata['backup_size'],
                    'tables' => $metadata['total_tables'],
                    'records' => $metadata['total_records'],
                    'status' => $metadata['status'],
                ];
            }
        }
        
        // Sort by date (newest first)
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Delete old backups
     */
    public function cleanupOldBackups(int $keepDays = 30): array
    {
        $cutoffDate = now()->subDays($keepDays);
        $backups = $this->listBackups();
        $deletedBackups = [];
        
        foreach ($backups as $backup) {
            $backupDate = Carbon::parse($backup['date']);
            
            if ($backupDate->lt($cutoffDate)) {
                Storage::deleteDirectory("backups/{$backup['name']}");
                $deletedBackups[] = $backup;
            }
        }
        
        return [
            'deleted_count' => count($deletedBackups),
            'deleted_backups' => $deletedBackups,
            'cutoff_date' => $cutoffDate->toISOString(),
        ];
    }
    
    /**
     * Get all tables in the database
     */
    protected function getAllTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";
        
        return array_map(function ($table) use ($tableKey) {
            return $table->$tableKey;
        }, $tables);
    }
    
    /**
     * Backup a single table
     */
    protected function backupTable(string $table, string $backupPath): array
    {
        $recordCount = DB::table($table)->count();
        $chunkSize = 1000;
        $fileIndex = 1;
        
        $tableBackup = [
            'record_count' => $recordCount,
            'files' => [],
            'backup_date' => now()->toISOString(),
        ];
        
        if ($recordCount === 0) {
            return $tableBackup;
        }
        
        // Export table structure
        $this->exportTableStructure($table, $backupPath);
        
        // Export data in chunks
        DB::table($table)
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($table, $backupPath, &$fileIndex, &$tableBackup) {
                $fileName = "{$table}_{$fileIndex}.json";
                $filePath = "{$backupPath}/{$fileName}";
                
                $data = $records->toArray();
                Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
                
                $tableBackup['files'][] = $fileName;
                $fileIndex++;
            });
        
        return $tableBackup;
    }
    
    /**
     * Backup table incrementally (only changed records)
     */
    protected function backupTableIncremental(string $table, string $backupPath, string $sinceDate): array
    {
        $query = DB::table($table);
        
        // Only backup records modified since the last backup
        if (Schema::hasColumn($table, 'updated_at')) {
            $query->where('updated_at', '>=', $sinceDate);
        } elseif (Schema::hasColumn($table, 'created_at')) {
            $query->where('created_at', '>=', $sinceDate);
        }
        
        $records = $query->get();
        $recordCount = $records->count();
        
        $tableBackup = [
            'record_count' => $recordCount,
            'files' => [],
            'backup_date' => now()->toISOString(),
            'since_date' => $sinceDate,
        ];
        
        if ($recordCount > 0) {
            $fileName = "{$table}_incremental.json";
            $filePath = "{$backupPath}/{$fileName}";
            
            Storage::put($filePath, json_encode($records->toArray(), JSON_PRETTY_PRINT));
            $tableBackup['files'][] = $fileName;
        }
        
        return $tableBackup;
    }
    
    /**
     * Export table structure
     */
    protected function exportTableStructure(string $table, string $backupPath): void
    {
        $createTable = DB::select("SHOW CREATE TABLE {$table}")[0];
        $structure = [
            'table' => $table,
            'create_statement' => $createTable->{'Create Table'},
            'columns' => DB::select("DESCRIBE {$table}"),
            'indexes' => DB::select("SHOW INDEX FROM {$table}"),
        ];
        
        Storage::put("{$backupPath}/{$table}_structure.json", json_encode($structure, JSON_PRETTY_PRINT));
    }
    
    /**
     * Restore a single table
     */
    protected function restoreTable(string $table, string $backupPath, bool $dryRun, bool $skipExisting): array
    {
        $metadataPath = "{$backupPath}/backup_metadata.json";
        $metadata = json_decode(Storage::get($metadataPath), true);
        $tableMetadata = $metadata['tables'][$table];
        
        $restoreResult = [
            'restored_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];
        
        if (empty($tableMetadata['files'])) {
            return $restoreResult;
        }
        
        foreach ($tableMetadata['files'] as $fileName) {
            $filePath = "{$backupPath}/{$fileName}";
            
            if (!Storage::exists($filePath)) {
                $restoreResult['errors'][] = "File not found: {$fileName}";
                $restoreResult['error_count']++;
                continue;
            }
            
            $data = json_decode(Storage::get($filePath), true);
            
            foreach ($data as $record) {
                try {
                    if ($skipExisting && isset($record['id'])) {
                        $exists = DB::table($table)->where('id', $record['id'])->exists();
                        if ($exists) {
                            $restoreResult['skipped_count']++;
                            continue;
                        }
                    }
                    
                    if (!$dryRun) {
                        DB::table($table)->insert($record);
                    }
                    
                    $restoreResult['restored_count']++;
                    
                } catch (\Exception $e) {
                    $restoreResult['errors'][] = "Error restoring record: " . $e->getMessage();
                    $restoreResult['error_count']++;
                }
            }
        }
        
        return $restoreResult;
    }
    
    /**
     * Create backup metadata
     */
    protected function createBackupMetadata(string $backupPath, array $metadata): void
    {
        Storage::put("{$backupPath}/backup_metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
        
        // Create readable summary
        $summary = $this->createReadableBackupSummary($metadata);
        Storage::put("{$backupPath}/backup_summary.txt", $summary);
    }
    
    /**
     * Create readable backup summary
     */
    protected function createReadableBackupSummary(array $metadata): string
    {
        $summary = "Database Backup Summary\n";
        $summary .= str_repeat('=', 30) . "\n\n";
        $summary .= "Backup Name: {$metadata['backup_name']}\n";
        $summary .= "Backup Date: {$metadata['backup_date']}\n";
        $summary .= "Backup Type: " . ($metadata['backup_type'] ?? 'full') . "\n";
        $summary .= "Status: {$metadata['status']}\n";
        $summary .= "Total Tables: {$metadata['total_tables']}\n";
        $summary .= "Total Records: {$metadata['total_records']}\n";
        
        if (isset($metadata['backup_size'])) {
            $summary .= "Backup Size: " . $this->formatBytes($metadata['backup_size']) . "\n";
        }
        
        $summary .= "\nTable Details:\n";
        $summary .= str_repeat('-', 20) . "\n";
        
        foreach ($metadata['tables'] as $table => $tableData) {
            $summary .= sprintf("%-25s: %d records\n", $table, $tableData['record_count']);
        }
        
        return $summary;
    }
    
    /**
     * Create restore log
     */
    protected function createRestoreLog(string $backupPath, array $restoreSummary): void
    {
        $logPath = "{$backupPath}/restore_log_" . now()->format('Y_m_d_H_i_s') . ".json";
        Storage::put($logPath, json_encode($restoreSummary, JSON_PRETTY_PRINT));
    }
    
    /**
     * Calculate backup size
     */
    protected function calculateBackupSize(string $backupPath): int
    {
        $files = Storage::allFiles($backupPath);
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }
        
        return $totalSize;
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}