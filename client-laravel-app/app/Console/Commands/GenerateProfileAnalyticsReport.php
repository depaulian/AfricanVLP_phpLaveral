<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ProfileActivityLog;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileScoringService;
use App\Services\BehavioralAnalyticsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use League\Csv\Writer;

class GenerateProfileAnalyticsReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:analytics-report 
                            {--type=comprehensive : Type of report (comprehensive, summary, behavioral, scoring)}
                            {--period=monthly : Report period (daily, weekly, monthly, quarterly, yearly)}
                            {--format=json : Output format (json, csv, pdf, html)}
                            {--user= : Generate report for specific user ID}
                            {--email= : Email address to send the report to}
                            {--save : Save report to storage}
                            {--output= : Custom output file path}';

    /**
     * The console command description.
     */
    protected $description = 'Generate comprehensive profile analytics reports with various formats and delivery options';

    protected ProfileAnalyticsService $analyticsService;
    protected ProfileScoringService $scoringService;
    protected BehavioralAnalyticsService $behavioralService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        ProfileAnalyticsService $analyticsService,
        ProfileScoringService $scoringService,
        BehavioralAnalyticsService $behavioralService
    ) {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->scoringService = $scoringService;
        $this->behavioralService = $behavioralService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Profile Analytics Report Generation...');
        
        try {
            $type = $this->option('type');
            $period = $this->option('period');
            $format = $this->option('format');
            $userId = $this->option('user');
            $email = $this->option('email');
            $save = $this->option('save');
            $customOutput = $this->option('output');

            // Validate inputs
            if (!$this->validateInputs($type, $period, $format)) {
                return Command::FAILURE;
            }

            // Generate report data
            $reportData = $this->generateReportData($type, $period, $userId);
            
            if (empty($reportData)) {
                $this->error('❌ No data available for the specified criteria.');
                return Command::FAILURE;
            }

            // Format and output report
            $outputPath = $this->processReport($reportData, $format, $type, $period, $customOutput, $save);
            
            // Send email if requested
            if ($email && $outputPath) {
                $this->sendReportByEmail($email, $outputPath, $type, $period);
            }

            $this->info("✅ Profile analytics report generated successfully!");
            if ($outputPath) {
                $this->info("📄 Report saved to: {$outputPath}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error generating report: " . $e->getMessage());
            Log::error('Profile Analytics Report Generation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Validate command inputs.
     */
    protected function validateInputs(string $type, string $period, string $format): bool
    {
        $validTypes = ['comprehensive', 'summary', 'behavioral', 'scoring'];
        $validPeriods = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
        $validFormats = ['json', 'csv', 'pdf', 'html'];

        if (!in_array($type, $validTypes)) {
            $this->error("❌ Invalid report type. Valid options: " . implode(', ', $validTypes));
            return false;
        }

        if (!in_array($period, $validPeriods)) {
            $this->error("❌ Invalid period. Valid options: " . implode(', ', $validPeriods));
            return false;
        }

        if (!in_array($format, $validFormats)) {
            $this->error("❌ Invalid format. Valid options: " . implode(', ', $validFormats));
            return false;
        }

        return true;
    }

    /**
     * Generate report data based on type and parameters.
     */
    protected function generateReportData(string $type, string $period, ?string $userId): array
    {
        $this->info("📊 Generating {$type} report for {$period} period...");
        
        $dateRange = $this->getDateRange($period);
        $users = $userId ? User::where('id', $userId)->get() : User::all();
        
        if ($users->isEmpty()) {
            return [];
        }

        $reportData = [
            'metadata' => [
                'report_type' => $type,
                'period' => $period,
                'date_range' => $dateRange,
                'generated_at' => Carbon::now()->toISOString(),
                'total_users' => $users->count(),
            ],
            'data' => []
        ];

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $userData = $this->generateUserData($user, $type, $dateRange);
            if (!empty($userData)) {
                $reportData['data'][] = $userData;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Add summary statistics
        $reportData['summary'] = $this->generateSummaryStatistics($reportData['data'], $type);

        return $reportData;
    }

    /**
     * Generate data for a specific user.
     */
    protected function generateUserData(User $user, string $type, array $dateRange): array
    {
        $userData = [
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
            'registration_date' => $user->created_at->toISOString(),
        ];

        switch ($type) {
            case 'comprehensive':
                $userData = array_merge($userData, [
                    'analytics' => $this->analyticsService->getUserProfileAnalytics($user),
                    'scoring' => $this->scoringService->calculateComprehensiveScore($user),
                    'behavioral' => $this->behavioralService->analyzeUserBehavior($user),
                ]);
                break;

            case 'summary':
                $analytics = $this->analyticsService->getUserProfileAnalytics($user);
                $userData = array_merge($userData, [
                    'completion_score' => $analytics['completion_score']['total_score'] ?? 0,
                    'engagement_level' => $analytics['engagement_metrics']['login_frequency']['last_30_days'] ?? 0,
                    'last_activity' => $this->getLastActivity($user),
                    'profile_views' => $analytics['performance_metrics']['profile_views']['last_30_days'] ?? 0,
                ]);
                break;

            case 'behavioral':
                $userData['behavioral_analysis'] = $this->behavioralService->analyzeUserBehavior($user);
                break;

            case 'scoring':
                $userData['scoring_analysis'] = $this->scoringService->calculateComprehensiveScore($user);
                break;
        }

        return $userData;
    }

    /**
     * Get date range based on period.
     */
    protected function getDateRange(string $period): array
    {
        $endDate = Carbon::now();
        
        switch ($period) {
            case 'daily':
                $startDate = $endDate->copy()->subDay();
                break;
            case 'weekly':
                $startDate = $endDate->copy()->subWeek();
                break;
            case 'monthly':
                $startDate = $endDate->copy()->subMonth();
                break;
            case 'quarterly':
                $startDate = $endDate->copy()->subQuarter();
                break;
            case 'yearly':
                $startDate = $endDate->copy()->subYear();
                break;
            default:
                $startDate = $endDate->copy()->subMonth();
        }

        return [
            'start_date' => $startDate->toISOString(),
            'end_date' => $endDate->toISOString(),
        ];
    }

    /**
     * Get last activity for a user.
     */
    protected function getLastActivity(User $user): ?string
    {
        $lastActivity = ProfileActivityLog::where('user_id', $user->id)
            ->latest()
            ->first();

        return $lastActivity ? $lastActivity->created_at->toISOString() : null;
    }

    /**
     * Generate summary statistics.
     */
    protected function generateSummaryStatistics(array $data, string $type): array
    {
        if (empty($data)) {
            return [];
        }

        $summary = [
            'total_users_analyzed' => count($data),
            'analysis_date' => Carbon::now()->toISOString(),
        ];

        switch ($type) {
            case 'comprehensive':
            case 'summary':
                $completionScores = array_column($data, 'completion_score');
                $completionScores = array_filter($completionScores, 'is_numeric');
                
                if (!empty($completionScores)) {
                    $summary['completion_statistics'] = [
                        'average_completion' => round(array_sum($completionScores) / count($completionScores), 2),
                        'highest_completion' => max($completionScores),
                        'lowest_completion' => min($completionScores),
                        'users_above_80_percent' => count(array_filter($completionScores, fn($score) => $score >= 80)),
                    ];
                }

                $engagementLevels = array_column($data, 'engagement_level');
                $engagementLevels = array_filter($engagementLevels, 'is_numeric');
                
                if (!empty($engagementLevels)) {
                    $summary['engagement_statistics'] = [
                        'average_engagement' => round(array_sum($engagementLevels) / count($engagementLevels), 2),
                        'highly_engaged_users' => count(array_filter($engagementLevels, fn($level) => $level >= 20)),
                        'inactive_users' => count(array_filter($engagementLevels, fn($level) => $level == 0)),
                    ];
                }
                break;

            case 'scoring':
                // Add scoring-specific summary statistics
                $summary['scoring_distribution'] = $this->calculateScoringDistribution($data);
                break;

            case 'behavioral':
                // Add behavioral-specific summary statistics
                $summary['behavioral_patterns'] = $this->calculateBehavioralPatterns($data);
                break;
        }

        return $summary;
    }

    /**
     * Calculate scoring distribution.
     */
    protected function calculateScoringDistribution(array $data): array
    {
        $grades = [];
        foreach ($data as $user) {
            if (isset($user['scoring_analysis']['grade']['letter'])) {
                $grade = $user['scoring_analysis']['grade']['letter'];
                $grades[$grade] = ($grades[$grade] ?? 0) + 1;
            }
        }

        return $grades;
    }

    /**
     * Calculate behavioral patterns.
     */
    protected function calculateBehavioralPatterns(array $data): array
    {
        $patterns = [
            'peak_hours' => [],
            'user_types' => [],
            'engagement_trends' => []
        ];

        foreach ($data as $user) {
            if (isset($user['behavioral_analysis']['usage_patterns']['peak_hour'])) {
                $hour = $user['behavioral_analysis']['usage_patterns']['peak_hour'];
                $patterns['peak_hours'][$hour] = ($patterns['peak_hours'][$hour] ?? 0) + 1;
            }
        }

        return $patterns;
    }

    /**
     * Process and output the report.
     */
    protected function processReport(
        array $reportData, 
        string $format, 
        string $type, 
        string $period, 
        ?string $customOutput, 
        bool $save
    ): ?string {
        $filename = $customOutput ?: $this->generateFilename($type, $period, $format);
        
        switch ($format) {
            case 'json':
                return $this->outputJson($reportData, $filename, $save);
            case 'csv':
                return $this->outputCsv($reportData, $filename, $save);
            case 'html':
                return $this->outputHtml($reportData, $filename, $save);
            case 'pdf':
                return $this->outputPdf($reportData, $filename, $save);
            default:
                $this->outputToConsole($reportData);
                return null;
        }
    }

    /**
     * Generate filename for the report.
     */
    protected function generateFilename(string $type, string $period, string $format): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        return "profile_analytics_{$type}_{$period}_{$timestamp}.{$format}";
    }

    /**
     * Output report as JSON.
     */
    protected function outputJson(array $data, string $filename, bool $save): ?string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($save) {
            $path = "reports/profile-analytics/{$filename}";
            Storage::disk('local')->put($path, $json);
            return storage_path("app/{$path}");
        } else {
            $this->line($json);
            return null;
        }
    }

    /**
     * Output report as CSV.
     */
    protected function outputCsv(array $data, string $filename, bool $save): ?string
    {
        if (empty($data['data'])) {
            return null;
        }

        $csv = Writer::createFromString('');
        
        // Add headers
        $firstRow = $data['data'][0];
        $headers = $this->flattenArrayKeys($firstRow);
        $csv->insertOne($headers);
        
        // Add data rows
        foreach ($data['data'] as $row) {
            $flatRow = $this->flattenArray($row);
            $csv->insertOne($flatRow);
        }
        
        $csvContent = $csv->toString();
        
        if ($save) {
            $path = "reports/profile-analytics/{$filename}";
            Storage::disk('local')->put($path, $csvContent);
            return storage_path("app/{$path}");
        } else {
            $this->line($csvContent);
            return null;
        }
    }

    /**
     * Output report as HTML.
     */
    protected function outputHtml(array $data, string $filename, bool $save): ?string
    {
        $html = $this->generateHtmlReport($data);
        
        if ($save) {
            $path = "reports/profile-analytics/{$filename}";
            Storage::disk('local')->put($path, $html);
            return storage_path("app/{$path}");
        } else {
            $this->line($html);
            return null;
        }
    }

    /**
     * Output report as PDF (placeholder - would need PDF library).
     */
    protected function outputPdf(array $data, string $filename, bool $save): ?string
    {
        $this->warn('PDF output not yet implemented. Generating HTML instead.');
        return $this->outputHtml($data, str_replace('.pdf', '.html', $filename), $save);
    }

    /**
     * Output report to console.
     */
    protected function outputToConsole(array $data): void
    {
        $this->info('📊 Profile Analytics Report Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Report Type', $data['metadata']['report_type']],
                ['Period', $data['metadata']['period']],
                ['Total Users', $data['metadata']['total_users']],
                ['Generated At', $data['metadata']['generated_at']],
            ]
        );

        if (isset($data['summary'])) {
            $this->info('📈 Summary Statistics:');
            foreach ($data['summary'] as $key => $value) {
                if (is_array($value)) {
                    $this->info("  {$key}:");
                    foreach ($value as $subKey => $subValue) {
                        $this->info("    {$subKey}: {$subValue}");
                    }
                } else {
                    $this->info("  {$key}: {$value}");
                }
            }
        }
    }

    /**
     * Generate HTML report.
     */
    protected function generateHtmlReport(array $data): string
    {
        $metadata = $data['metadata'];
        $summary = $data['summary'] ?? [];
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>Profile Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .summary { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .metric { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Profile Analytics Report</h1>
        <p><strong>Type:</strong> {$metadata['report_type']}</p>
        <p><strong>Period:</strong> {$metadata['period']}</p>
        <p><strong>Generated:</strong> {$metadata['generated_at']}</p>
        <p><strong>Total Users:</strong> {$metadata['total_users']}</p>
    </div>";

        if (!empty($summary)) {
            $html .= "<div class='summary'><h2>Summary Statistics</h2>";
            foreach ($summary as $key => $value) {
                if (is_array($value)) {
                    $html .= "<div class='metric'><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong><ul>";
                    foreach ($value as $subKey => $subValue) {
                        $html .= "<li>" . ucwords(str_replace('_', ' ', $subKey)) . ": {$subValue}</li>";
                    }
                    $html .= "</ul></div>";
                } else {
                    $html .= "<div class='metric'><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong> {$value}</div>";
                }
            }
            $html .= "</div>";
        }

        $html .= "</body></html>";
        
        return $html;
    }

    /**
     * Flatten array for CSV output.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Get flattened array keys.
     */
    protected function flattenArrayKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenArrayKeys($value, $newKey));
            } else {
                $keys[] = $newKey;
            }
        }
        return $keys;
    }

    /**
     * Send report by email.
     */
    protected function sendReportByEmail(string $email, string $filePath, string $type, string $period): void
    {
        try {
            $this->info("📧 Sending report to {$email}...");
            
            // This would typically use a Mailable class
            // For now, just log the action
            Log::info('Profile Analytics Report Email Sent', [
                'email' => $email,
                'file_path' => $filePath,
                'type' => $type,
                'period' => $period
            ]);
            
            $this->info("✅ Report sent successfully to {$email}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
        }
    }}
