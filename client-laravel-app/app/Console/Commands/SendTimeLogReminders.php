<?php

namespace App\Console\Commands;

use App\Models\VolunteerTimeLog;
use App\Models\User;
use App\Notifications\PendingTimeLogApprovals;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendTimeLogReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'volunteer:send-time-log-reminders 
                            {--days=7 : Number of days after which to send reminders}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder notifications to supervisors for pending time log approvals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Looking for time logs pending approval for more than {$days} days...");
        
        // Get time logs pending approval for more than specified days
        $pendingLogs = VolunteerTimeLog::where('supervisor_approved', false)
            ->where('created_at', '<=', now()->subDays($days))
            ->whereHas('assignment.supervisor')
            ->with([
                'assignment.supervisor',
                'assignment.application.user',
                'assignment.opportunity'
            ])
            ->get();

        if ($pendingLogs->isEmpty()) {
            $this->info('No pending time logs found that require reminders.');
            return 0;
        }

        $this->info("Found {$pendingLogs->count()} pending time log entries.");

        // Group by supervisor
        $logsBySupervisor = $pendingLogs->groupBy('assignment.supervisor_id');

        $remindersSent = 0;
        $errors = 0;

        foreach ($logsBySupervisor as $supervisorId => $logs) {
            $supervisor = $logs->first()->assignment->supervisor;
            
            if (!$supervisor) {
                $this->warn("Supervisor not found for time logs: " . $logs->pluck('id')->implode(', '));
                $errors++;
                continue;
            }

            $this->line("Processing {$logs->count()} pending logs for supervisor: {$supervisor->name}");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would send reminder to {$supervisor->email}");
                $this->table(
                    ['Date', 'Volunteer', 'Hours', 'Days Pending'],
                    $logs->map(function ($log) {
                        return [
                            $log->date->format('Y-m-d'),
                            $log->assignment->application->user->name,
                            number_format($log->hours, 1),
                            $log->created_at->diffInDays(now())
                        ];
                    })->toArray()
                );
            } else {
                try {
                    // Send notification
                    $supervisor->notify(new PendingTimeLogApprovals($logs));
                    
                    $this->info("  ✓ Reminder sent to {$supervisor->email}");
                    $remindersSent++;
                    
                    // Log the reminder
                    Log::info('Time log approval reminder sent', [
                        'supervisor_id' => $supervisor->id,
                        'supervisor_email' => $supervisor->email,
                        'pending_logs_count' => $logs->count(),
                        'time_log_ids' => $logs->pluck('id')->toArray()
                    ]);
                    
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send reminder to {$supervisor->email}: {$e->getMessage()}");
                    $errors++;
                    
                    Log::error('Failed to send time log approval reminder', [
                        'supervisor_id' => $supervisor->id,
                        'supervisor_email' => $supervisor->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if ($dryRun) {
            $this->info("\n[DRY RUN] Would have sent {$logsBySupervisor->count()} reminders for {$pendingLogs->count()} pending time logs.");
        } else {
            $this->info("\nReminder Summary:");
            $this->info("  Reminders sent: {$remindersSent}");
            if ($errors > 0) {
                $this->warn("  Errors: {$errors}");
            }
            $this->info("  Total pending logs: {$pendingLogs->count()}");
        }

        return 0;
    }
}