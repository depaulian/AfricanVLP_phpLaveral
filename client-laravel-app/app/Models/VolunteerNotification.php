<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class VolunteerNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'is_read',
        'read_at',
        'sent_at',
        'status',
        'failure_reason',
        'scheduled_for',
        'priority',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic relationship)
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific channel
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for scheduled notifications that are due
     */
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('scheduled_for')
                          ->orWhere('scheduled_for', '<=', now());
                    });
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 1);
    }

    /**
     * Scope for medium priority notifications
     */
    public function scopeMediumPriority($query)
    {
        return $query->where('priority', 2);
    }

    /**
     * Scope for low priority notifications
     */
    public function scopeLowPriority($query)
    {
        return $query->where('priority', 3);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Cancel notification
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Get notification type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'opportunity_match' => 'Opportunity Match',
            'application_status' => 'Application Status',
            'hour_approval' => 'Hour Approval',
            'deadline_reminder' => 'Deadline Reminder',
            'supervisor_notification' => 'Supervisor Notification',
            'digest' => 'Activity Digest',
            'assignment_created' => 'Assignment Created',
            'assignment_completed' => 'Assignment Completed',
            'certificate_issued' => 'Certificate Issued',
            'achievement_earned' => 'Achievement Earned',
            'feedback_request' => 'Feedback Request',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get priority display name
     */
    public function getPriorityDisplayAttribute(): string
    {
        return match ($this->priority) {
            1 => 'High',
            2 => 'Medium',
            3 => 'Low',
            default => 'Unknown',
        };
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            1 => 'red',
            2 => 'yellow',
            3 => 'green',
            default => 'gray',
        };
    }

    /**
     * Get channel display name
     */
    public function getChannelDisplayAttribute(): string
    {
        return match ($this->channel) {
            'database' => 'In-App',
            'email' => 'Email',
            'sms' => 'SMS',
            'push' => 'Push Notification',
            default => ucfirst($this->channel),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if notification is overdue
     */
    public function isOverdue(): bool
    {
        return $this->scheduled_for && 
               $this->scheduled_for->isPast() && 
               $this->status === 'pending';
    }

    /**
     * Get time until scheduled
     */
    public function getTimeUntilScheduledAttribute(): ?string
    {
        if (!$this->scheduled_for) {
            return null;
        }

        if ($this->scheduled_for->isPast()) {
            return 'Overdue by ' . $this->scheduled_for->diffForHumans(null, true);
        }

        return 'Scheduled for ' . $this->scheduled_for->diffForHumans();
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'opportunity_match' => 'fas fa-handshake',
            'application_status' => 'fas fa-file-alt',
            'hour_approval' => 'fas fa-clock',
            'deadline_reminder' => 'fas fa-exclamation-triangle',
            'supervisor_notification' => 'fas fa-user-tie',
            'digest' => 'fas fa-envelope',
            'assignment_created' => 'fas fa-plus-circle',
            'assignment_completed' => 'fas fa-check-circle',
            'certificate_issued' => 'fas fa-certificate',
            'achievement_earned' => 'fas fa-trophy',
            'feedback_request' => 'fas fa-comment-dots',
            default => 'fas fa-bell',
        };
    }

    /**
     * Get notification URL for navigation
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->related) {
            return null;
        }

        return match ($this->related_type) {
            'App\\Models\\VolunteeringOpportunity' => route('client.volunteering.show', $this->related_id),
            'App\\Models\\VolunteerApplication' => route('client.volunteering.applications.show', $this->related_id),
            'App\\Models\\VolunteerAssignment' => route('client.volunteering.assignment', $this->related_id),
            'App\\Models\\VolunteerTimeLog' => route('client.volunteering.time-logs.show', $this->related_id),
            default => null,
        };
    }

    /**
     * Create a notification for opportunity match
     */
    public static function createOpportunityMatch(
        User $user, 
        VolunteeringOpportunity $opportunity, 
        array $matchData = [],
        string $channel = 'database'
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => 'opportunity_match',
            'title' => 'New Opportunity Match',
            'message' => "We found a volunteering opportunity that matches your interests: {$opportunity->title}",
            'data' => array_merge($matchData, [
                'opportunity_id' => $opportunity->id,
                'match_score' => $matchData['match_score'] ?? null,
            ]),
            'channel' => $channel,
            'priority' => 2,
            'related_type' => VolunteeringOpportunity::class,
            'related_id' => $opportunity->id,
        ]);
    }

    /**
     * Create a notification for application status change
     */
    public static function createApplicationStatus(
        User $user, 
        VolunteerApplication $application, 
        string $status,
        string $channel = 'database'
    ): self {
        $statusMessages = [
            'approved' => 'Your volunteer application has been approved!',
            'rejected' => 'Your volunteer application has been reviewed.',
            'pending' => 'Your volunteer application is under review.',
            'withdrawn' => 'Your volunteer application has been withdrawn.',
        ];

        return self::create([
            'user_id' => $user->id,
            'type' => 'application_status',
            'title' => 'Application Status Update',
            'message' => $statusMessages[$status] ?? 'Your application status has been updated.',
            'data' => [
                'application_id' => $application->id,
                'status' => $status,
                'opportunity_title' => $application->opportunity->title,
            ],
            'channel' => $channel,
            'priority' => $status === 'approved' ? 1 : 2,
            'related_type' => VolunteerApplication::class,
            'related_id' => $application->id,
        ]);
    }

    /**
     * Create a notification for hour approval
     */
    public static function createHourApproval(
        User $user, 
        VolunteerTimeLog $timeLog, 
        bool $approved,
        string $channel = 'database'
    ): self {
        $message = $approved 
            ? "Your logged hours ({$timeLog->hours} hours) have been approved."
            : "Your logged hours ({$timeLog->hours} hours) need revision.";

        return self::create([
            'user_id' => $user->id,
            'type' => 'hour_approval',
            'title' => 'Hour Log ' . ($approved ? 'Approved' : 'Needs Revision'),
            'message' => $message,
            'data' => [
                'time_log_id' => $timeLog->id,
                'hours' => $timeLog->hours,
                'approved' => $approved,
                'assignment_id' => $timeLog->assignment_id,
            ],
            'channel' => $channel,
            'priority' => $approved ? 3 : 2,
            'related_type' => VolunteerTimeLog::class,
            'related_id' => $timeLog->id,
        ]);
    }

    /**
     * Create a deadline reminder notification
     */
    public static function createDeadlineReminder(
        User $user, 
        Model $relatedModel, 
        string $deadlineType,
        Carbon $deadline,
        string $channel = 'database'
    ): self {
        $daysUntil = now()->diffInDays($deadline, false);
        $timeText = $daysUntil > 0 ? "in {$daysUntil} days" : ($daysUntil === 0 ? 'today' : 'overdue');

        return self::create([
            'user_id' => $user->id,
            'type' => 'deadline_reminder',
            'title' => ucfirst($deadlineType) . ' Deadline Reminder',
            'message' => "Your {$deadlineType} deadline is {$timeText}.",
            'data' => [
                'deadline_type' => $deadlineType,
                'deadline' => $deadline->toISOString(),
                'days_until' => $daysUntil,
            ],
            'channel' => $channel,
            'priority' => $daysUntil <= 1 ? 1 : 2,
            'related_type' => get_class($relatedModel),
            'related_id' => $relatedModel->id,
        ]);
    }

    /**
     * Create a supervisor notification
     */
    public static function createSupervisorNotification(
        User $supervisor, 
        string $notificationType,
        Model $relatedModel,
        array $data = [],
        string $channel = 'database'
    ): self {
        $messages = [
            'pending_approval' => 'You have pending time logs to approve.',
            'new_application' => 'A new volunteer application requires your review.',
            'volunteer_assigned' => 'A new volunteer has been assigned to your supervision.',
        ];

        return self::create([
            'user_id' => $supervisor->id,
            'type' => 'supervisor_notification',
            'title' => 'Supervisor Action Required',
            'message' => $messages[$notificationType] ?? 'You have a pending supervisor action.',
            'data' => array_merge($data, [
                'notification_type' => $notificationType,
            ]),
            'channel' => $channel,
            'priority' => 2,
            'related_type' => get_class($relatedModel),
            'related_id' => $relatedModel->id,
        ]);
    }
}