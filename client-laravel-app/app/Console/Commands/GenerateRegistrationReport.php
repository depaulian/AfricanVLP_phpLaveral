<?php

namespace App\Console\Commands;

use App\Services\RegistrationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateRegistrationReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'registration:generate-report
                            {--start-date= : Start date (YYYY-MM-DD)}
                            {--end-date= : End date (YYYY-MM-DD)}
                            {--format=json : Output format (json, csv)}
                            {--output= : Output file path}';

    /**
     * The console command description.
     */
    protected $description = 'Generate registration analytics report';

    /**
     * Execute the console command.
     */
    public function handle(RegistrationService $registrationService): int
    {
        try {
            $startDate = $this->option('start-date') ? 
                Carbon::parse($this->option('start-date')) : 
                Carbon::now()->subDays(30);
            
            $endDate = $this->option('end-date') ? 
                Carbon::parse($this->option('end-date')) : 
                Carbon::now();

            $this->info("Generating registration report from {$startDate->toDateString()} to {$endDate->toDateString()}");

            $analytics = $registrationService->getRegistrationAnalytics($startDate, $endDate);
            $funnel = $registrationService->getRegistrationFunnel($startDate, $endDate);

            $report = [
                'generated_at' => now()->toISOString(),
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString()
                ],
                'analytics' => $analytics,
                'funnel' => $funnel
            ];

            $format = $this->option('format');
            $outputPath = $this->option('output') ?? 
                "reports/registration_report_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.{$format}";

            switch ($format) {
                case 'csv':
                    $this->generateCSVReport($report, $outputPath);
                    break;
                case 'json':
                default:
                    $this->generateJSONReport($report, $outputPath);
                    break;
            }

            $this->info("Report generated successfully: {$outputPath}");

            // Display summary in console
            $this->displaySummary($analytics, $funnel);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate report: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Generate JSON report
     */
    private function generateJSONReport(array $report, string $outputPath): void
    {
        Storage::put($outputPath, json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Generate CSV report
     */
    private function generateCSVReport(array $report, string $outputPath): void
    {
        $csv = [];
        
        // Analytics section
        $csv[] = ['Registration Analytics'];
        $csv[] = ['Metric', 'Value'];
        $csv[] = ['Period', $report['period']['start_date'] . ' to ' . $report['period']['end_date']];
        $csv[] = ['Total Registrations', $report['analytics']['total_registrations']];
        $csv[] = ['Completed Registrations', $report['analytics']['completed_registrations']];
        $csv[] = ['Conversion Rate (%)', $report['analytics']['conversion_rate']];
        $csv[] = ['Abandonment Rate (%)', $report['analytics']['abandonment_rate']];
        $csv[] = ['Avg Completion Time (min)', $report['analytics']['average_completion_time_minutes']];
        $csv[] = [];

        // Step completion rates
        $csv[] = ['Step Completion Rates'];
        $csv[] = ['Step', 'Completion Rate (%)'];
        foreach ($report['analytics']['step_completion_rates'] as $step => $rate) {
            $csv[] = [ucfirst(str_replace('_', ' ', $step)), $rate];
        }
        $csv[] = [];

        // Funnel data
        $csv[] = ['Registration Funnel'];
        $csv[] = ['Stage', 'Count', 'Percentage'];
        foreach ($report['funnel'] as $stage => $data) {
            $stageName = $data['title'] ?? ucfirst(str_replace('_', ' ', $stage));
            $csv[] = [$stageName, $data['count'], $data['percentage']];
        }

        // Convert to CSV string
        $csvContent = '';
        foreach ($csv as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        Storage::put($outputPath, $csvContent);
    }

    /**
     * Display summary in console
     */
    private function displaySummary(array $analytics, array $funnel): void
    {
        $this->info("\n=== Registration Report Summary ===");
        
        $this->table([
            'Metric',
            'Value'
        ], [
            ['Total Registrations', $analytics['total_registrations']],
            ['Completed Registrations', $analytics['completed_registrations']],
            ['Conversion Rate', $analytics['conversion_rate'] . '%'],
            ['Abandonment Rate', $analytics['abandonment_rate'] . '%'],
            ['Avg Completion Time', $analytics['average_completion_time_minutes'] . ' minutes']
        ]);

        $this->info("\n=== Step Completion Rates ===");
        $stepData = [];
        foreach ($analytics['step_completion_rates'] as $step => $rate) {
            $stepData[] = [ucfirst(str_replace('_', ' ', $step)), $rate . '%'];
        }
        $this->table(['Step', 'Completion Rate'], $stepData);

        $this->info("\n=== Registration Funnel ===");
        $funnelData = [];
        foreach ($funnel as $stage => $data) {
            $stageName = $data['title'] ?? ucfirst(str_replace('_', ' ', $stage));
            $funnelData[] = [$stageName, $data['count'], $data['percentage'] . '%'];
        }
        $this->table(['Stage', 'Count', 'Percentage'], $funnelData);
    }
}