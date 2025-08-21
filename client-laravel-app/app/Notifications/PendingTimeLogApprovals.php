<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class PendingTimeLogApprovals extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $timeLogs;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $timeLogs)
    {
        $this->timeLogs = $timeLogs;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $totalHours = $this->timeLogs->sum('hours');
        $volunteerCount = $this->timeLogs->pluck('assignment.application.user_id')->unique()->count();
        $oldestLog = $this->timeLogs->sortBy('created_at')->first();
        
        $message = (new MailMessage)
            ->subject('Pending Volunteer Time Log Approvals')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have ' . $this->timeLogs->count() . ' volunteer time log entries waiting for your approval.')
            ->line('**Summary:**')
            ->line('• Total entries: ' . $this->timeLogs->count())
            ->line('• Total hours: ' . number_format($totalHours, 1))
            ->line('• Volunteers: ' . $volunteerCount)
            ->line('• Oldest entry: ' . $oldestLog->created_at->diffForHumans());

        // Add details for up to 5 entries
        $message->line('**Recent Entries:**');
        
        foreach ($this->timeLogs->take(5) as $log) {
            $volunteer = $log->assignment->application->user;
            $opportunity = $log->assignment->opportunity;
            
            $message->line('• **' . $volunteer->name . '** - ' . $opportunity->title)
                    ->line('  ' . $log->date->format('M d, Y') . ' - ' . number_format($log->hours, 1) . ' hours')
                    ->line('  ' . ($log->activity_description ? Str::limit($log->activity_description, 60) : 'No description'));
        }

        if ($this->timeLogs->count() > 5) {
            $remaining = $this->timeLogs->count() - 5;
            $message->line("... and {$remaining} more entries");
        }

        $message->action('Review Time Logs', route('admin.volunteering.time-logs.index'))
                ->line('Please review and approve these time logs to help volunteers track their contributions accurately.')
                ->line('Thank you for supervising our volunteers!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $totalHours = $this->timeLogs->sum('hours');
        $volunteerCount = $this->timeLogs->pluck('assignment.application.user_id')->unique()->count();
        
        return [
            'type' => 'pending_time_log_approvals',
            'message' => 'You have ' . $this->timeLogs->count() . ' time log entries pending approval',
            'data' => [
                'total_entries' => $this->timeLogs->count(),
                'total_hours' => $totalHours,
                'volunteer_count' => $volunteerCount,
                'time_log_ids' => $this->timeLogs->pluck('id')->toArray(),
                'oldest_entry_date' => $this->timeLogs->min('created_at'),
            ],
            'action_url' => route('admin.volunteering.time-logs.index'),
            'action_text' => 'Review Time Logs'
        ];
    }
}