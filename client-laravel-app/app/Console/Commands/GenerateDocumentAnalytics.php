<?php

namespace App\Console\Commands;

use App\Models\UserDocument;
use App\Services\DocumentManagementService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateDocumentAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:generate-analytics 
                            {--period=month : Analytics period (day, week, month, year)}
                            {--format=table : Output format (table, json, csv)}
                            {--export= : Export to file path}';

    /**
     * The console command description.
     */
    protected $description = 'Generate comprehensive document management analytics and reports';

    protected DocumentManagementService $documentService;

    public function __construct(DocumentManagementService $documentService)
    {
        parent::__construct();
        $this->documentService = $documentService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating document analytics...');

        $period = $this->option('period');
        $format = $this->option('format');
        $exportPath = $this->option('export');

        // Generate analytics data
        $analytics = $this->generateAnalytics($period);

        // Display analytics
        $this->displayAnalytics($analytics, $format);

        // Export if requested
        if ($exportPath) {
            $this->exportAnalytics($analytics, $exportPath, $format);
        }

        $this->info('Document analytics generation completed.');
        return 0;
    }

    /**
     * Generate comprehensive analytics data.
     */
    protected function generateAnalytics(string $period): array
    {
        $dateRange = $this->getDateRange($period);
        $platformStats = $this->documentService->getPlatformDocumentStatistics();

        return [
            'period' => $period,
            'date_range' => $dateRange,
            'platform_statistics' => $platformStats,
            'upload_trends' => $this->getUploadTrends($dateRange),
            'verification_metrics' => $this->getVerificationMetrics($dateRange),
            'category_distribution' => $this->getCategoryDistribution($dateRange),
            'user_engagement' => $this->getUserEngagementMetrics($dateRange),
            'security_metrics' => $this->getSecurityMetrics($dateRange),
            'storage_analytics' => $this->getStorageAnalytics($dateRange),
            'expiration_analytics' => $this->getExpirationAnalytics(),
            'performance_metrics' => $this->getPerformanceMetrics($dateRange)
        ];
    }

    /**
     * Get date range for the specified period.
     */
    protected function getDateRange(string $period): array
    {
        $now = now();
        
        switch ($period) {
            case 'day':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
        }
    }

    /**
     * Get upload trends data.
     */
    protected function getUploadTrends(array $dateRange): array
    {
        $uploads = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalUploads = $uploads->sum('count');
        $avgUploadsPerDay = $uploads->count() > 0 ? $totalUploads / $uploads->count() : 0;

        return [
            'total_uploads' => $totalUploads,
            'average_per_day' => round($avgUploadsPerDay, 2),
            'daily_breakdown' => $uploads->toArray(),
            'peak_day' => $uploads->sortByDesc('count')->first(),
            'growth_rate' => $this->calculateGrowthRate($dateRange)
        ];
    }

    /**
     * Get verification metrics.
     */
    protected function getVerificationMetrics(array $dateRange): array
    {
        $verifications = UserDocument::whereBetween('verified_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('verified_at')
            ->get();

        $avgVerificationTime = UserDocument::whereBetween('verified_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('verified_at')
            ->whereNotNull('verification_requested_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, verification_requested_at, verified_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total_verifications' => $verifications->count(),
            'approved' => $verifications->where('verification_status', 'verified')->count(),
            'rejected' => $verifications->where('verification_status', 'rejected')->count(),
            'approval_rate' => $verifications->count() > 0 
                ? round(($verifications->where('verification_status', 'verified')->count() / $verifications->count()) * 100, 2) 
                : 0,
            'average_verification_time_hours' => round($avgVerificationTime ?? 0, 2),
            'pending_queue_size' => UserDocument::where('verification_status', 'pending')->count()
        ];
    }

    /**
     * Get category distribution data.
     */
    protected function getCategoryDistribution(array $dateRange): array
    {
        $distribution = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('category, COUNT(*) as count, AVG(file_size) as avg_size')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        return [
            'categories' => $distribution->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => $item->count,
                    'percentage' => 0, // Will be calculated below
                    'average_file_size' => round($item->avg_size ?? 0, 2)
                ];
            })->toArray(),
            'total_documents' => $distribution->sum('count')
        ];
    }

    /**
     * Get user engagement metrics.
     */
    protected function getUserEngagementMetrics(array $dateRange): array
    {
        $activeUsers = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->distinct('user_id')
            ->count('user_id');

        $topUploaders = UserDocument::with('user')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('user_id, COUNT(*) as upload_count')
            ->groupBy('user_id')
            ->orderByDesc('upload_count')
            ->limit(10)
            ->get();

        return [
            'active_users' => $activeUsers,
            'average_uploads_per_user' => $activeUsers > 0 
                ? round(UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count() / $activeUsers, 2)
                : 0,
            'top_uploaders' => $topUploaders->map(function ($item) {
                return [
                    'user_name' => $item->user->name,
                    'user_email' => $item->user->email,
                    'upload_count' => $item->upload_count
                ];
            })->toArray()
        ];
    }

    /**
     * Get security metrics.
     */
    protected function getSecurityMetrics(array $dateRange): array
    {
        $documentsWithScans = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('metadata->scan_result')
            ->get();

        $threatsDetected = $documentsWithScans->filter(function ($document) {
            $scanResult = $document->metadata['scan_result'] ?? [];
            return isset($scanResult['safe']) && !$scanResult['safe'];
        });

        return [
            'total_scanned' => $documentsWithScans->count(),
            'threats_detected' => $threatsDetected->count(),
            'threat_detection_rate' => $documentsWithScans->count() > 0 
                ? round(($threatsDetected->count() / $documentsWithScans->count()) * 100, 2)
                : 0,
            'sensitive_documents' => UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->where('is_sensitive', true)
                ->count()
        ];
    }

    /**
     * Get storage analytics.
     */
    protected function getStorageAnalytics(array $dateRange): array
    {
        $storageStats = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('
                SUM(file_size) as total_storage,
                AVG(file_size) as average_file_size,
                MAX(file_size) as largest_file,
                MIN(file_size) as smallest_file,
                COUNT(*) as file_count
            ')
            ->first();

        return [
            'total_storage_bytes' => $storageStats->total_storage ?? 0,
            'total_storage_mb' => round(($storageStats->total_storage ?? 0) / 1024 / 1024, 2),
            'average_file_size_mb' => round(($storageStats->average_file_size ?? 0) / 1024 / 1024, 2),
            'largest_file_mb' => round(($storageStats->largest_file ?? 0) / 1024 / 1024, 2),
            'smallest_file_mb' => round(($storageStats->smallest_file ?? 0) / 1024 / 1024, 2),
            'total_files' => $storageStats->file_count ?? 0
        ];
    }

    /**
     * Get expiration analytics.
     */
    protected function getExpirationAnalytics(): array
    {
        $expiringDocuments = $this->documentService->getExpiringDocuments(30);
        $expiredDocuments = $this->documentService->getExpiredDocuments();

        return [
            'expiring_within_30_days' => $expiringDocuments->count(),
            'expired_documents' => $expiredDocuments->count(),
            'documents_with_expiry' => UserDocument::whereNotNull('expiry_date')->count(),
            'expiring_by_category' => $expiringDocuments->groupBy('category')
                ->map(function ($docs) {
                    return $docs->count();
                })->toArray()
        ];
    }

    /**
     * Get performance metrics.
     */
    protected function getPerformanceMetrics(array $dateRange): array
    {
        // This would typically come from application performance monitoring
        return [
            'average_upload_time_seconds' => 2.5, // Placeholder
            'average_verification_processing_time_hours' => 24, // Placeholder
            'system_uptime_percentage' => 99.9, // Placeholder
            'api_response_time_ms' => 150 // Placeholder
        ];
    }

    /**
     * Calculate growth rate compared to previous period.
     */
    protected function calculateGrowthRate(array $dateRange): float
    {
        $currentPeriodCount = UserDocument::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        
        $periodLength = $dateRange['end']->diffInDays($dateRange['start']);
        $previousStart = $dateRange['start']->copy()->subDays($periodLength);
        $previousEnd = $dateRange['start']->copy()->subDay();
        
        $previousPeriodCount = UserDocument::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        
        if ($previousPeriodCount == 0) {
            return $currentPeriodCount > 0 ? 100.0 : 0.0;
        }
        
        return round((($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100, 2);
    }

    /**
     * Display analytics in the specified format.
     */
    protected function displayAnalytics(array $analytics, string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($analytics, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->displayCsvFormat($analytics);
                break;
            default:
                $this->displayTableFormat($analytics);
                break;
        }
    }

    /**
     * Display analytics in table format.
     */
    protected function displayTableFormat(array $analytics): void
    {
        $this->newLine();
        $this->info("=== Document Analytics Report ({$analytics['period']}) ===");
        
        // Platform Statistics
        $this->info('Platform Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Documents', number_format($analytics['platform_statistics']['total_documents'])],
                ['Pending Verification', number_format($analytics['platform_statistics']['pending_verification'])],
                ['Verified Documents', number_format($analytics['platform_statistics']['verified_documents'])],
                ['Rejected Documents', number_format($analytics['platform_statistics']['rejected_documents'])],
                ['Total Storage Used', $analytics['storage_analytics']['total_storage_mb'] . ' MB'],
            ]
        );

        // Upload Trends
        $this->info('Upload Trends:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Uploads', number_format($analytics['upload_trends']['total_uploads'])],
                ['Average Per Day', $analytics['upload_trends']['average_per_day']],
                ['Growth Rate', $analytics['upload_trends']['growth_rate'] . '%'],
            ]
        );

        // Verification Metrics
        $this->info('Verification Metrics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Verifications', number_format($analytics['verification_metrics']['total_verifications'])],
                ['Approval Rate', $analytics['verification_metrics']['approval_rate'] . '%'],
                ['Avg Verification Time', $analytics['verification_metrics']['average_verification_time_hours'] . ' hours'],
                ['Pending Queue Size', number_format($analytics['verification_metrics']['pending_queue_size'])],
            ]
        );

        // Category Distribution
        if (!empty($analytics['category_distribution']['categories'])) {
            $this->info('Category Distribution:');
            $this->table(
                ['Category', 'Count', 'Avg File Size (MB)'],
                array_map(function ($cat) {
                    return [
                        ucfirst($cat['category']),
                        number_format($cat['count']),
                        round($cat['average_file_size'] / 1024 / 1024, 2)
                    ];
                }, $analytics['category_distribution']['categories'])
            );
        }

        // Security Metrics
        $this->info('Security Metrics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Documents Scanned', number_format($analytics['security_metrics']['total_scanned'])],
                ['Threats Detected', number_format($analytics['security_metrics']['threats_detected'])],
                ['Threat Detection Rate', $analytics['security_metrics']['threat_detection_rate'] . '%'],
                ['Sensitive Documents', number_format($analytics['security_metrics']['sensitive_documents'])],
            ]
        );
    }

    /**
     * Display analytics in CSV format.
     */
    protected function displayCsvFormat(array $analytics): void
    {
        // This would output CSV formatted data
        $this->line('CSV format output would be implemented here');
    }

    /**
     * Export analytics to file.
     */
    protected function exportAnalytics(array $analytics, string $path, string $format): void
    {
        try {
            $content = '';
            
            switch ($format) {
                case 'json':
                    $content = json_encode($analytics, JSON_PRETTY_PRINT);
                    break;
                case 'csv':
                    $content = $this->convertToCsv($analytics);
                    break;
                default:
                    $content = $this->convertToText($analytics);
                    break;
            }
            
            file_put_contents($path, $content);
            $this->info("Analytics exported to: {$path}");
            
        } catch (\Exception $e) {
            $this->error("Failed to export analytics: {$e->getMessage()}");
        }
    }

    /**
     * Convert analytics to CSV format.
     */
    protected function convertToCsv(array $analytics): string
    {
        // Implementation for CSV conversion
        return "CSV conversion would be implemented here\n";
    }

    /**
     * Convert analytics to text format.
     */
    protected function convertToText(array $analytics): string
    {
        // Implementation for text conversion
        return "Text conversion would be implemented here\n";
    }
}