<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ForumNotification;
use App\Models\ForumNotificationPreference;
use App\Services\ForumNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendForumDigests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'forum:send-digests {frequency=daily : The digest frequency (daily, weekly, monthly)}';

    /**
     * The console command description.
     */
    protected $description = 'Send forum notification digests to users';

    protected ForumNotificationService $notificationService;

    public function __construct(ForumNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $frequency = $this->argument('frequency');
        
        if (!in_array($frequency, ['daily', 'weekly', 'monthly'])) {
            $this->error('Invalid frequency. Must be daily, weekly, or monthly.');
            return 1;
        }

        $this->info("Sending {$frequency} forum digests...");

        // Get users who have digest notifications enabled for this frequency
        $users = $this->getUsersForDigest($frequency);
        
        if ($users->isEmpty()) {
            $this->info('No users found for digest notifications.');
            return 0;
        }

        $this->info("Found {$users->count()} users for {$frequency} digest.");

        $sentCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                if ($this->sendDigestToUser($user, $frequency)) {
                    $sentCount++;
                    $this->line("✓ Sent digest to {$user->name} ({$user->email})");
                } else {
                    $this->line("- No notifications for {$user->name} ({$user->email})");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to send digest to {$user->name}: " . $e->getMessage());
                Log::error('Forum digest send failed', [
                    'user_id' => $user->id,
                    'frequency' => $frequency,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Digest sending completed:");
        $this->info("- Sent: {$sentCount}");
        $this->info("- Errors: {$errorCount}");

        return 0;
    }

    /**
     * Get users who should receive digest notifications
     */
    protected function getUsersForDigest(string $frequency): \Illuminate\Support\Collection
    {
        return User::whereHas('forumNotificationPreferences', function ($query) use ($frequency) {
            $query->where('digest_enabled', true)
                  ->where('digest_frequency', $frequency);
        })->get();
    }

    /**
     * Send digest to a specific user
     */
    protected function sendDigestToUser(User $user, string $frequency): bool
    {
        // Get the date range for the digest
        $dateRange = $this->getDateRange($frequency);
        
        // Get unread notifications for the user within the date range
        $notifications = ForumNotification::where('user_id', $user->id)
            ->whereBetween('created_at', $dateRange)
            ->with('notifiable')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($notifications->isEmpty()) {
            return false;
        }

        // Group notifications by type
        $groupedNotifications = $notifications->groupBy('type');

        // Prepare digest data
        $digestData = [
            'user' => $user,
            'frequency' => $frequency,
            'period' => $this->getPeriodDescription($frequency),
            'notifications' => $groupedNotifications,
            'total_count' => $notifications->count(),
            'unread_count' => $notifications->where('read_at', null)->count(),
        ];

        // Send the digest email
        // For now, we'll just log it - you would implement actual email sending here
        Log::info('Forum digest would be sent', [
            'user_id' => $user->id,
            'frequency' => $frequency,
            'notification_count' => $notifications->count(),
        ]);

        // In a real implementation, you would send an email like this:
        // Mail::to($user->email)->send(new ForumDigestMail($digestData));

        return true;
    }

    /**
     * Get date range for the frequency
     */
    protected function getDateRange(string $frequency): array
    {
        $now = now();
        
        switch ($frequency) {
            case 'daily':
                return [$now->copy()->subDay(), $now];
            case 'weekly':
                return [$now->copy()->subWeek(), $now];
            case 'monthly':
                return [$now->copy()->subMonth(), $now];
            default:
                return [$now->copy()->subDay(), $now];
        }
    }

    /**
     * Get human-readable period description
     */
    protected function getPeriodDescription(string $frequency): string
    {
        switch ($frequency) {
            case 'daily':
                return 'the past 24 hours';
            case 'weekly':
                return 'the past week';
            case 'monthly':
                return 'the past month';
            default:
                return 'recently';
        }
    }
}