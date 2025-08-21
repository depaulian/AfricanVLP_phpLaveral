<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ProfileAnalyticsReportGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $reportData;
    protected ?string $filePath;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $reportData, ?string $filePath = null)
    {
        $this->reportData = $reportData;
        $this->filePath = $filePath;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        if (config('profile_analytics.email.enabled', true)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $metadata = $this->reportData['metadata'] ?? [];
        $summary = $this->reportData['summary'] ?? [];
        
        $reportType = ucwords(str_replace('_', ' ', $metadata['report_type'] ?? 'Unknown'));
        $period = ucwords($metadata['period'] ?? 'Unknown');
        
        $message = (new MailMessage)
            ->subject(config('profile_analytics.email.subject_prefix', '[Analytics Report]') . " {$reportType} Report - {$period}")
            ->greeting('Profile Analytics Report Generated')
            ->line("A new {$reportType} profile analytics report has been generated for the {$period} period.")
            ->line('Report Details:')
            ->line("• Report Type: {$reportType}")
            ->line("• Period: {$period}")
            ->line("• Generated: " . ($metadata['generated_at'] ?? 'Unknown'))
            ->line("• Total Users Analyzed: " . ($metadata['total_users'] ?? 'Unknown'));

        // Add summary statistics if available
        if (!empty($summary)) {
            $message->line('Summary Statistics:');
            
            if (isset($summary['completion_statistics'])) {
                $stats = $summary['completion_statistics'];
                $message->line("• Average Profile Completion: " . ($stats['average_completion'] ?? 'N/A') . "%");
                $message->line("• Users Above 80%: " . ($stats['users_above_80_percent'] ?? 'N/A'));
            }
            
            if (isset($summary['engagement_statistics'])) {
                $stats = $summary['engagement_statistics'];
                $message->line("• Average Engagement: " . ($stats['average_engagement'] ?? 'N/A'));
                $message->line("• Highly Engaged Users: " . ($stats['highly_engaged_users'] ?? 'N/A'));
                $message->line("• Inactive Users: " . ($stats['inactive_users'] ?? 'N/A'));
            }
        }

        // Attach file if available and within size limits
        if ($this->filePath && file_exists($this->filePath)) {
            $fileSize = filesize($this->filePath);
            $maxSize = config('profile_analytics.email.max_attachment_size', 25 * 1024 * 1024);
            
            if ($fileSize <= $maxSize) {
                $message->attach($this->filePath, [
                    'as' => basename($this->filePath),
                    'mime' => $this->getMimeType($this->filePath),
                ]);
            } else {
                $message->line('Note: The report file is too large to attach via email. Please access it through the admin dashboard or contact your administrator.');
            }
        }

        $message->line('You can also access this report through the admin dashboard.')
                ->action('View Dashboard', url('/admin/profile-analytics'))
                ->line('Thank you for using our profile analytics system!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $metadata = $this->reportData['metadata'] ?? [];
        $summary = $this->reportData['summary'] ?? [];
        
        return [
            'type' => 'profile_analytics_report_generated',
            'report_type' => $metadata['report_type'] ?? 'unknown',
            'period' => $metadata['period'] ?? 'unknown',
            'generated_at' => $metadata['generated_at'] ?? now()->toISOString(),
            'total_users' => $metadata['total_users'] ?? 0,
            'file_path' => $this->filePath,
            'summary' => $summary,
            'title' => 'Profile Analytics Report Generated',
            'message' => "A new " . ucwords(str_replace('_', ' ', $metadata['report_type'] ?? 'unknown')) . " report has been generated.",
        ];
    }

    /**
     * Get MIME type for file.
     */
    protected function getMimeType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        $mimeTypes = [
            'json' => 'application/json',
            'csv' => 'text/csv',
            'html' => 'text/html',
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}