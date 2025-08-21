<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledReport;
use App\Services\VolunteeringAnalyticsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteering:generate-reports 
                            {--report= : Specific report ID to generate}
                            {--force : Force generation even if not due}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and send scheduled volunteering reports';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private VolunteeringAnalyticsService $analyticsService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reportId = $this->option('report');
        $force = $this->option('force');

        $this->info('Starting scheduled report generation...');

        try {
            // Get reports to process
            $reports = $this->getReportsToProcess($reportId, $force);
            
            if ($reports->isEmpty()) {
                $this->info('No reports due for generation.');
                return Command::SUCCESS;
            }

            $this->info("Found {$reports->count()} report(s) to generate");

            $successCount = 0;
            $failureCount = 0;

            foreach ($reports as $report) {
                $this->line("Generating report: {$report->name} (ID: {$report->id})");
                
                try {
                    $this->generateAndSendReport($report);
                    $successCount++;
                    $this->line("  - Report generated and sent successfully");
                } catch (\Exception $e) {
                    $failureCount++;
                    $this->error("  - Failed to generate report: " . $e->getMessage());
                    
                    // Mark report as failed
                    $report->markAsFailed($e->getMessage());
                    
                    Log::error('Scheduled report generation failed', [
                        'report_id' => $report->id,
                        'report_name' => $report->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->info("Report generation completed. Success: {$successCount}, Failures: {$failureCount}");
            return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error in report generation process: " . $e->getMessage());
            Log::error('Scheduled report generation process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get reports that need to be processed
     */
    private function getReportsToProcess(?string $reportId, bool $force)
    {
        if ($reportId) {
            $report = ScheduledReport::find($reportId);
            if (!$report) {
                throw new \InvalidArgumentException("Report with ID {$reportId} not found");
            }
            return collect([$report]);
        }

        $query = ScheduledReport::with(['organization', 'creator'])->active();
        
        if (!$force) {
            $query->dueForGeneration();
        }

        return $query->get();
    }

    /**
     * Generate and send a specific report
     */
    private function generateAndSendReport(ScheduledReport $report): void
    {
        // Generate report data
        $reportData = $this->analyticsService->generateReportData(
            $report->report_type,
            $report->report_config,
            $report->organization_id
        );

        // Generate report file based on format
        $reportFile = $this->generateReportFile($reportData, $report);

        // Send report via email
        $this->sendReportEmail($report, $reportFile);

        // Mark report as generated
        $report->markAsGenerated([
            'file_size' => $reportFile['size'],
            'recipients_count' => count($report->recipients),
        ]);

        // Clean up temporary file
        if (isset($reportFile['temp_path'])) {
            Storage::delete($reportFile['temp_path']);
        }
    }

    /**
     * Generate report file in the specified format
     */
    private function generateReportFile(array $data, ScheduledReport $report): array
    {
        $fileName = $this->generateFileName($report);
        
        switch ($report->format) {
            case 'pdf':
                return $this->generatePdfFile($data, $report, $fileName);
            case 'excel':
                return $this->generateExcelFile($data, $report, $fileName);
            case 'csv':
                return $this->generateCsvFile($data, $report, $fileName);
            case 'html':
                return $this->generateHtmlFile($data, $report, $fileName);
            default:
                throw new \InvalidArgumentException("Unsupported format: {$report->format}");
        }
    }

    /**
     * Generate file name for the report
     */
    private function generateFileName(ScheduledReport $report): string
    {
        $organizationName = $report->organization ? 
            str_replace(' ', '_', $report->organization->name) : 
            'Global';
        
        $reportName = str_replace(' ', '_', $report->name);
        $date = now()->format('Y-m-d');
        
        return "{$organizationName}_{$reportName}_{$date}";
    }

    /**
     * Generate PDF report file
     */
    private function generatePdfFile(array $data, ScheduledReport $report, string $fileName): array
    {
        // This would use a PDF library like DomPDF or wkhtmltopdf
        // For now, create a placeholder
        $content = "PDF Report: {$report->name}\nGenerated: " . now()->toDateTimeString();
        $tempPath = "temp/reports/{$fileName}.pdf";
        
        Storage::put($tempPath, $content);
        
        return [
            'path' => $tempPath,
            'temp_path' => $tempPath,
            'name' => $fileName . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($content),
        ];
    }

    /**
     * Generate Excel report file
     */
    private function generateExcelFile(array $data, ScheduledReport $report, string $fileName): array
    {
        // This would use Laravel Excel package
        // For now, create a placeholder CSV
        $content = "Excel Report: {$report->name}\nGenerated: " . now()->toDateTimeString();
        $tempPath = "temp/reports/{$fileName}.xlsx";
        
        Storage::put($tempPath, $content);
        
        return [
            'path' => $tempPath,
            'temp_path' => $tempPath,
            'name' => $fileName . '.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => strlen($content),
        ];
    }

    /**
     * Generate CSV report file
     */
    private function generateCsvFile(array $data, ScheduledReport $report, string $fileName): array
    {
        $csvData = [];
        $csvData[] = ['Report', $report->name];
        $csvData[] = ['Generated', now()->toDateTimeString()];
        $csvData[] = ['Organization', $report->organization->name ?? 'Global'];
        $csvData[] = []; // Empty row
        
        // Add data rows based on report type
        if (isset($data['summary_metrics'])) {
            $csvData[] = ['Metric', 'Value', 'Trend'];
            foreach ($data['summary_metrics'] as $metric => $values) {
                $csvData[] = [
                    ucwords(str_replace('_', ' ', $metric)),
                    $values['formatted_value'] ?? $values['value'] ?? 'N/A',
                    isset($values['trend']) ? $values['trend']['direction'] . ' ' . ($values['trend']['percentage'] ?? 0) . '%' : 'N/A'
                ];
            }
        }
        
        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        $tempPath = "temp/reports/{$fileName}.csv";
        Storage::put($tempPath, $content);
        
        return [
            'path' => $tempPath,
            'temp_path' => $tempPath,
            'name' => $fileName . '.csv',
            'mime_type' => 'text/csv',
            'size' => strlen($content),
        ];
    }

    /**
     * Generate HTML report file
     */
    private function generateHtmlFile(array $data, ScheduledReport $report, string $fileName): array
    {
        $html = $this->generateHtmlContent($data, $report);
        $tempPath = "temp/reports/{$fileName}.html";
        
        Storage::put($tempPath, $html);
        
        return [
            'path' => $tempPath,
            'temp_path' => $tempPath,
            'name' => $fileName . '.html',
            'mime_type' => 'text/html',
            'size' => strlen($html),
        ];
    }

    /**
     * Generate HTML content for the report
     */
    private function generateHtmlContent(array $data, ScheduledReport $report): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>' . htmlspecialchars($report->name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .metric { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; }
        .metric-name { font-weight: bold; }
        .metric-value { color: #007cba; font-size: 1.2em; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($report->name) . '</h1>
        <p>Generated: ' . now()->toDateTimeString() . '</p>
        <p>Organization: ' . htmlspecialchars($report->organization->name ?? 'Global') . '</p>
    </div>';

        if (isset($data['summary_metrics'])) {
            $html .= '<h2>Summary Metrics</h2>';
            foreach ($data['summary_metrics'] as $metric => $values) {
                $html .= '<div class="metric">
                    <div class="metric-name">' . ucwords(str_replace('_', ' ', $metric)) . '</div>
                    <div class="metric-value">' . htmlspecialchars($values['formatted_value'] ?? $values['value'] ?? 'N/A') . '</div>
                </div>';
            }
        }

        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Send report via email
     */
    private function sendReportEmail(ScheduledReport $report, array $reportFile): void
    {
        // This would use Laravel's Mail system to send the report
        // For now, just log that we would send it
        Log::info('Scheduled report would be sent via email', [
            'report_id' => $report->id,
            'report_name' => $report->name,
            'recipients' => $report->recipients,
            'file_name' => $reportFile['name'],
            'file_size' => $reportFile['size'],
        ]);

        // In a real implementation, you would do something like:
        /*
        Mail::to($report->recipients)->send(new ScheduledReportMail($report, $reportFile));
        */
    }
}