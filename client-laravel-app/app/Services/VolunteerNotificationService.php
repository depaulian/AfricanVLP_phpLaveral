<?php

namespace App\Services;

use App\Models\User;
use App\Models\VolunteerNotification;
use App\Models\VolunteerNotificationPreference;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Notifications\VolunteerEmailNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class VolunteerNotificationService
{
    /**
     * Send a notification to a user
     */
    public function sendNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        Model $relatedModel = null,
        int $priority = 3,
        Carbon $scheduledFor = null
    ): Collection {
        $enabledChannels = VolunteerNotificationPreference::getEnabledChannelsForUser($user, $type);
        $notifications = collect();

        foreach ($enabledChannels as $channel) {
            $notification = VolunteerNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'channel' => $channel,
                'priority' => $priority,
                'related_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_id' => $relatedModel?->id,
                'scheduled_for' => $scheduledFor,
            ]);

            $notifications->push($notification);

            // Send immediately if not scheduled
            if (!$scheduledFor) {
                $this->deliverNotification($notification);
            }
        }

        return $notifications;
    }

    /**
     * Send opportunity match notification
     */
    public function sendOpportunityMatchNotification(
        User $user,
        VolunteeringOpportunity $opportunity,
        array $matchData = []
    ): Collection {
        $matchScore = $matchData['match_score'] ?? 0;
        $message = "We found a volunteering opportunity that matches your interests with a {$matchScore}% match: {$opportunity->title}";

        return $this->sendNotification(
            $user,
            'opportunity_match',
            'New Opportunity Match',
            $message,
            array_merge($matchData, [
                'opportunity_id' => $opportunity->id,
                'opportunity_title' => $opportunity->title,
                'organization_name' => $opportunity->organization->name,
            ]),
            $opportunity,
            2
        );
    }

    /**
     * Send application status notification
     */
    public function sendApplicationStatusNotification(
        VolunteerApplication $application,
        string $oldStatus,
        string $newStatus
    ): Collection {
        $statusMessages = [
            'approved' => 'Congratulations! Your volunteer application has been approved.',
            'rejected' => 'Your volunteer application has been reviewed. Please check your application for feedback.',
            'pending' => 'Your volunteer application is under review.',
            'withdrawn' => 'Your volunteer application has been withdrawn.',
        ];

        $priority = match ($newStatus) {
            'approved' => 1,
            'rejected' => 2,
            default => 3,
        };

        return $this->sendNotification(
            $application->user,
            'application_status',
            'Application Status Update',
            $statusMessages[$newStatus] ?? 'Your application status has been updated.',
            [
                'application_id' => $application->id,
                'opportunity_title' => $application->opportunity->title,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'organization_name' => $application->opportunity->organization->name,
            ],
            $application,
            $priority
        );
    }

    /**
     * Send hour approval notification
     */
    public function sendHourApprovalNotification(
        VolunteerTimeLog $timeLog,
        bool $approved,
        string $feedback = null
    ): Collection {
        $message = $approved 
            ? "Your logged hours ({$timeLog->hours} hours on {$timeLog->date->format('M j, Y')}) have been approved."
            : "Your logged hours ({$timeLog->hours} hours on {$timeLog->date->format('M j, Y')}) need revision.";

        if ($feedback) {
            $message .= " Feedback: {$feedback}";
        }

        return $this->sendNotification(
            $timeLog->user,
            'hour_approval',
            'Hour Log ' . ($approved ? 'Approved' : 'Needs Revision'),
            $message,
            [
                'time_log_id' => $timeLog->id,
                'hours' => $timeLog->hours,
                'date' => $timeLog->date->toDateString(),
                'approved' => $approved,
                'feedback' => $feedback,
                'assignment_id' => $timeLog->assignment_id,
            ],
            $timeLog,
            $approved ? 3 : 2
        );
    }

    /**
     * Send deadline reminder notification
     */
    public function sendDeadlineReminder(
        User $user,
        Model $relatedModel,
        string $deadlineType,
        Carbon $deadline,
        array $additionalData = []
    ): Collection {
        $daysUntil = now()->diffInDays($deadline, false);
        $timeText = match (true) {
            $daysUntil > 1 => "in {$daysUntil} days",
            $daysUntil === 1 => 'tomorrow',
            $daysUntil === 0 => 'today',
            default => 'overdue',
        };

        $priority = match (true) {
            $daysUntil <= 0 => 1, // Overdue or due today
            $daysUntil <= 1 => 1, // Due tomorrow
            $daysUntil <= 3 => 2, // Due in 2-3 days
            default => 3,
        };

        return $this->sendNotification(
            $user,
            'deadline_reminder',
            ucfirst($deadlineType) . ' Deadline Reminder',
            "Your {$deadlineType} deadline is {$timeText} ({$deadline->format('M j, Y')}).",
            array_merge($additionalData, [
                'deadline_type' => $deadlineType,
                'deadline' => $deadline->toISOString(),
                'days_until' => $daysUntil,
                'is_overdue' => $daysUntil < 0,
            ]),
            $relatedModel,
            $priority
        );
    }

    /**
     * Send supervisor notification
     */
    public function sendSupervisorNotification(
        User $supervisor,
        string $notificationType,
        Model $relatedModel,
        array $data = []
    ): Collection {
        $messages = [
            'pending_approval' => 'You have pending time logs that require your approval.',
            'new_application' => 'A new volunteer application requires your review.',
            'volunteer_assigned' => 'A new volunteer has been assigned to your supervision.',
            'assignment_completed' => 'A volunteer assignment under your supervision has been completed.',
            'feedback_requested' => 'Feedback has been requested for a volunteer under your supervision.',
        ];

        $titles = [
            'pending_approval' => 'Time Logs Pending Approval',
            'new_application' => 'New Application to Review',
            'volunteer_assigned' => 'New Volunteer Assigned',
            'assignment_completed' => 'Assignment Completed',
            'feedback_requested' => 'Feedback Requested',
        ];

        return $this->sendNotification(
            $supervisor,
            'supervisor_notification',
            $titles[$notificationType] ?? 'Supervisor Action Required',
            $messages[$notificationType] ?? 'You have a pending supervisor action.',
            array_merge($data, [
                'notification_type' => $notificationType,
            ]),
            $relatedModel,
            2
        );
    }

    /**
     * Send assignment created notification
     */
    public function sendAssignmentCreatedNotification(VolunteerAssignment $assignment): Collection
    {
        return $this->sendNotification(
            $assignment->user,
            'assignment_created',
            'New Volunteer Assignment',
            "You have been assigned to: {$assignment->opportunity->title}. Your assignment starts on {$assignment->start_date->format('M j, Y')}.",
            [
                'assignment_id' => $assignment->id,
                'opportunity_title' => $assignment->opportunity->title,
                'organization_name' => $assignment->opportunity->organization->name,
                'start_date' => $assignment->start_date->toDateString(),
                'end_date' => $assignment->end_date?->toDateString(),
            ],
            $assignment,
            2
        );
    }

    /**
     * Send assignment completed notification
     */
    public function sendAssignmentCompletedNotification(VolunteerAssignment $assignment): Collection
    {
        $notifications = collect();

        // Notify the volunteer
        $volunteerNotifications = $this->sendNotification(
            $assignment->user,
            'assignment_completed',
            'Assignment Completed',
            "Congratulations! You have completed your volunteer assignment: {$assignment->opportunity->title}.",
            [
                'assignment_id' => $assignment->id,
                'opportunity_title' => $assignment->opportunity->title,
                'organization_name' => $assignment->opportunity->organization->name,
                'total_hours' => $assignment->total_hours,
                'completion_date' => $assignment->completed_at?->toDateString(),
            ],
            $assignment,
            3
        );

        $notifications = $notifications->merge($volunteerNotifications);

        // Notify the supervisor if exists
        if ($assignment->supervisor) {
            $supervisorNotifications = $this->sendSupervisorNotification(
                $assignment->supervisor,
                'assignment_completed',
                $assignment,
                [
                    'volunteer_name' => $assignment->user->name,
                    'opportunity_title' => $assignment->opportunity->title,
                    'total_hours' => $assignment->total_hours,
                ]
            );

            $notifications = $notifications->merge($supervisorNotifications);
        }

        return $notifications;
    }

    /**
     * Send certificate issued notification
     */
    public function sendCertificateIssuedNotification(
        User $user,
        VolunteerAssignment $assignment,
        string $certificateUrl = null
    ): Collection {
        return $this->sendNotification(
            $user,
            'certificate_issued',
            'Certificate Issued',
            "Your volunteer certificate for '{$assignment->opportunity->title}' is now available for download.",
            [
                'assignment_id' => $assignment->id,
                'opportunity_title' => $assignment->opportunity->title,
                'organization_name' => $assignment->opportunity->organization->name,
                'certificate_url' => $certificateUrl,
                'total_hours' => $assignment->total_hours,
            ],
            $assignment,
            2
        );
    }

    /**
     * Send achievement earned notification
     */
    public function sendAchievementEarnedNotification(
        User $user,
        string $achievementName,
        string $achievementDescription,
        Model $relatedModel = null
    ): Collection {
        return $this->sendNotification(
            $user,
            'achievement_earned',
            'Achievement Unlocked!',
            "Congratulations! You've earned the '{$achievementName}' achievement. {$achievementDescription}",
            [
                'achievement_name' => $achievementName,
                'achievement_description' => $achievementDescription,
            ],
            $relatedModel,
            2
        );
    }

    /**
     * Send feedback request notification
     */
    public function sendFeedbackRequestNotification(
        User $user,
        Model $relatedModel,
        string $feedbackType,
        array $additionalData = []
    ): Collection {
        $messages = [
            'assignment_feedback' => 'Please provide feedback on your recent volunteer assignment.',
            'organization_feedback' => 'Please share your experience with the organization.',
            'supervisor_feedback' => 'Please provide feedback on your supervisor.',
        ];

        return $this->sendNotification(
            $user,
            'feedback_request',
            'Feedback Requested',
            $messages[$feedbackType] ?? 'Your feedback is requested.',
            array_merge($additionalData, [
                'feedback_type' => $feedbackType,
            ]),
            $relatedModel,
            3
        );
    }

    /**
     * Process and send scheduled notifications
     */
    public function processScheduledNotifications(): int
    {
        $dueNotifications = VolunteerNotification::due()
            ->orderBy('priority')
            ->orderBy('scheduled_for')
            ->limit(100) // Process in batches
            ->get();

        $processed = 0;

        foreach ($dueNotifications as $notification) {
            try {
                $this->deliverNotification($notification);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to deliver scheduled notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);

                $notification->markAsFailed($e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Deliver a notification through its specified channel
     */
    protected function deliverNotification(VolunteerNotification $notification): void
    {
        try {
            switch ($notification->channel) {
                case 'database':
                    // Already stored in database, just mark as sent
                    $notification->markAsSent();
                    break;

                case 'email':
                    $this->sendEmailNotification($notification);
                    break;

                case 'sms':
                    $this->sendSmsNotification($notification);
                    break;

                case 'push':
                    $this->sendPushNotification($notification);
                    break;

                default:
                    throw new \InvalidArgumentException("Unsupported notification channel: {$notification->channel}");
            }
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(VolunteerNotification $notification): void
    {
        if (!$notification->user->email) {
            throw new \Exception('User has no email address');
        }

        Mail::to($notification->user->email)->send(
            new VolunteerEmailNotification($notification)
        );

        $notification->markAsSent();
    }

    /**
     * Send SMS notification
     */
    protected function sendSmsNotification(VolunteerNotification $notification): void
    {
        // Implementation would depend on SMS service (Twilio, etc.)
        // For now, just mark as sent
        Log::info('SMS notification would be sent', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'message' => $notification->message,
        ]);

        $notification->markAsSent();
    }

    /**
     * Send push notification
     */
    protected function sendPushNotification(VolunteerNotification $notification): void
    {
        // Implementation would depend on push service (Firebase, etc.)
        // For now, just mark as sent
        Log::info('Push notification would be sent', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'title' => $notification->title,
            'message' => $notification->message,
        ]);

        $notification->markAsSent();
    }

    /**
     * Generate and send digest notifications
     */
    public function generateDigestNotifications(): int
    {
        $users = User::whereHas('volunteerNotificationPreferences', function ($query) {
            $query->where('notification_type', 'digest')
                  ->where('is_enabled', true);
        })->get();

        $sent = 0;

        foreach ($users as $user) {
            $preference = VolunteerNotificationPreference::getUserPreference($user, 'digest');
            
            if (!$preference || !$preference->is_enabled) {
                continue;
            }

            $settings = $preference->settings ?? [];
            $frequency = $settings['frequency'] ?? 'weekly';

            if ($this->shouldSendDigest($user, $frequency, $settings)) {
                $this->sendDigestNotification($user);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Check if digest should be sent based on frequency and settings
     */
    protected function shouldSendDigest(User $user, string $frequency, array $settings): bool
    {
        $lastDigest = VolunteerNotification::where('user_id', $user->id)
            ->where('type', 'digest')
            ->where('status', 'sent')
            ->latest()
            ->first();

        $now = now();

        return match ($frequency) {
            'daily' => !$lastDigest || $lastDigest->sent_at->diffInDays($now) >= 1,
            'weekly' => !$lastDigest || $lastDigest->sent_at->diffInWeeks($now) >= 1,
            'monthly' => !$lastDigest || $lastDigest->sent_at->diffInMonths($now) >= 1,
            default => false,
        };
    }

    /**
     * Send digest notification
     */
    protected function sendDigestNotification(User $user): void
    {
        $period = now()->subWeek(); // Last week's activity
        
        // Gather digest data
        $digestData = [
            'new_opportunities' => $this->getNewOpportunitiesForUser($user, $period),
            'application_updates' => $this->getApplicationUpdatesForUser($user, $period),
            'completed_hours' => $this->getCompletedHoursForUser($user, $period),
            'upcoming_deadlines' => $this->getUpcomingDeadlinesForUser($user),
            'achievements' => $this->getRecentAchievementsForUser($user, $period),
        ];

        $this->sendNotification(
            $user,
            'digest',
            'Your Weekly Volunteer Activity Digest',
            'Here\'s a summary of your volunteer activity from the past week.',
            $digestData,
            null,
            3
        );
    }

    /**
     * Get new opportunities for user digest
     */
    protected function getNewOpportunitiesForUser(User $user, Carbon $since): array
    {
        // This would integrate with the matching service
        return [];
    }

    /**
     * Get application updates for user digest
     */
    protected function getApplicationUpdatesForUser(User $user, Carbon $since): array
    {
        return VolunteerApplication::where('user_id', $user->id)
            ->where('updated_at', '>=', $since)
            ->with('opportunity')
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'opportunity_title' => $application->opportunity->title,
                    'status' => $application->status,
                    'updated_at' => $application->updated_at->toDateString(),
                ];
            })
            ->toArray();
    }

    /**
     * Get completed hours for user digest
     */
    protected function getCompletedHoursForUser(User $user, Carbon $since): array
    {
        $timeLogs = VolunteerTimeLog::where('user_id', $user->id)
            ->where('supervisor_approved', true)
            ->where('updated_at', '>=', $since)
            ->with('assignment.opportunity')
            ->get();

        return [
            'total_hours' => $timeLogs->sum('hours'),
            'sessions' => $timeLogs->count(),
            'opportunities' => $timeLogs->pluck('assignment.opportunity.title')->unique()->values()->toArray(),
        ];
    }

    /**
     * Get upcoming deadlines for user digest
     */
    protected function getUpcomingDeadlinesForUser(User $user): array
    {
        $upcomingDeadlines = [];

        // Get assignment deadlines
        $assignments = VolunteerAssignment::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->where('end_date', '<=', now()->addWeeks(2))
            ->with('opportunity')
            ->get();

        foreach ($assignments as $assignment) {
            $upcomingDeadlines[] = [
                'type' => 'assignment_end',
                'title' => $assignment->opportunity->title,
                'deadline' => $assignment->end_date->toDateString(),
                'days_until' => now()->diffInDays($assignment->end_date, false),
            ];
        }

        return $upcomingDeadlines;
    }

    /**
     * Get recent achievements for user digest
     */
    protected function getRecentAchievementsForUser(User $user, Carbon $since): array
    {
        // This would integrate with the achievement system
        return [];
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return VolunteerNotification::where('created_at', '<', $cutoffDate)
            ->where('is_read', true)
            ->delete();
    }

    /**
     * Get notification statistics for a user
     */
    public function getNotificationStats(User $user): array
    {
        $notifications = VolunteerNotification::where('user_id', $user->id);

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->clone()->unread()->count(),
            'by_type' => $notifications->clone()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_channel' => $notifications->clone()
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'recent_activity' => $notifications->clone()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }
}