<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'registration_notes',
        'custom_fields',
        'payment_required',
        'payment_status',
        'payment_reference',
        'amount_paid',
        'registered_at',
        'approved_at',
        'attended_at',
        'attendance_notes',
        'feedback_submitted',
        'event_rating',
        'event_feedback',
        'networking_connections',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'networking_connections' => 'array',
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'attended_at' => 'datetime',
        'amount_paid' => 'decimal:2',
        'event_rating' => 'decimal:1',
        'payment_required' => 'boolean',
        'feedback_submitted' => 'boolean',
    ];

    /**
     * Get the event
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(CommunityEvent::class, 'event_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for registered users
     */
    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    /**
     * Scope for waitlisted users
     */
    public function scopeWaitlisted($query)
    {
        return $query->where('status', 'waitlisted');
    }

    /**
     * Scope for approved registrations
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for attended registrations
     */
    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    /**
     * Scope for cancelled registrations
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Approve the registration
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Decline the registration
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Cancel the registration
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark as attended
     */
    public function markAttended(string $notes = null): void
    {
        $this->update([
            'status' => 'attended',
            'attended_at' => now(),
            'attendance_notes' => $notes,
        ]);
    }

    /**
     * Mark as no-show
     */
    public function markNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }

    /**
     * Submit feedback
     */
    public function submitFeedback(float $rating, string $feedback = null, array $connections = []): void
    {
        $this->update([
            'event_rating' => $rating,
            'event_feedback' => $feedback,
            'networking_connections' => $connections,
            'feedback_submitted' => true,
        ]);

        // Update event average rating
        $this->event->updateAverageRating();
    }

    /**
     * Check if registration is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['registered', 'approved']);
    }

    /**
     * Check if user attended
     */
    public function hasAttended(): bool
    {
        return $this->status === 'attended';
    }

    /**
     * Check if payment is required and pending
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_required && $this->payment_status === 'pending';
    }

    /**
     * Check if payment is completed
     */
    public function isPaymentCompleted(): bool
    {
        return !$this->payment_required || $this->payment_status === 'paid';
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'registered' => 'Registered',
            'waitlisted' => 'Waitlisted',
            'approved' => 'Approved',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
            'attended' => 'Attended',
            'no_show' => 'No Show',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'registered' => 'blue',
            'waitlisted' => 'yellow',
            'approved' => 'green',
            'declined' => 'red',
            'cancelled' => 'gray',
            'attended' => 'green',
            'no_show' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get payment status display
     */
    public function getPaymentStatusDisplayAttribute(): string
    {
        if (!$this->payment_required) {
            return 'Not Required';
        }

        return match ($this->payment_status) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            default => ucfirst($this->payment_status ?? 'pending'),
        };
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        if (!$this->payment_required) {
            return 'gray';
        }

        return match ($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'refunded' => 'blue',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get registration duration
     */
    public function getRegistrationDurationAttribute(): string
    {
        if (!$this->registered_at) {
            return 'Unknown';
        }

        return $this->registered_at->diffForHumans();
    }

    /**
     * Check if can cancel registration
     */
    public function canCancel(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Can't cancel if event has already started
        if ($this->event->start_datetime->isPast()) {
            return false;
        }

        // Can't cancel if within 24 hours of event (configurable)
        $cancelDeadline = $this->event->start_datetime->subHours(24);
        if (now()->isAfter($cancelDeadline)) {
            return false;
        }

        return true;
    }

    /**
     * Check if can submit feedback
     */
    public function canSubmitFeedback(): bool
    {
        return $this->hasAttended() && !$this->feedback_submitted && $this->event->isPast();
    }

    /**
     * Get networking connections count
     */
    public function getNetworkingConnectionsCountAttribute(): int
    {
        return count($this->networking_connections ?? []);
    }

    /**
     * Add networking connection
     */
    public function addNetworkingConnection(User $connectedUser, string $notes = null): void
    {
        $connections = $this->networking_connections ?? [];
        
        // Check if connection already exists
        $existingIndex = array_search($connectedUser->id, array_column($connections, 'user_id'));
        
        if ($existingIndex === false) {
            $connections[] = [
                'user_id' => $connectedUser->id,
                'user_name' => $connectedUser->name,
                'user_email' => $connectedUser->email,
                'notes' => $notes,
                'connected_at' => now()->toISOString(),
            ];
            
            $this->update(['networking_connections' => $connections]);
        }
    }

    /**
     * Remove networking connection
     */
    public function removeNetworkingConnection(int $userId): void
    {
        $connections = $this->networking_connections ?? [];
        
        $connections = array_filter($connections, function ($connection) use ($userId) {
            return $connection['user_id'] !== $userId;
        });
        
        $this->update(['networking_connections' => array_values($connections)]);
    }

    /**
     * Get custom field value
     */
    public function getCustomField(string $key, $default = null)
    {
        return $this->custom_fields[$key] ?? $default;
    }

    /**
     * Set custom field value
     */
    public function setCustomField(string $key, $value): void
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        $this->update(['custom_fields' => $fields]);
    }

    /**
     * Get registration statistics for an event
     */
    public static function getEventStatistics(CommunityEvent $event): array
    {
        $registrations = static::where('event_id', $event->id);
        
        return [
            'total_registrations' => $registrations->count(),
            'registered' => $registrations->where('status', 'registered')->count(),
            'waitlisted' => $registrations->where('status', 'waitlisted')->count(),
            'approved' => $registrations->where('status', 'approved')->count(),
            'attended' => $registrations->where('status', 'attended')->count(),
            'no_shows' => $registrations->where('status', 'no_show')->count(),
            'cancelled' => $registrations->where('status', 'cancelled')->count(),
            'attendance_rate' => $event->attendance_rate,
            'average_rating' => $registrations->whereNotNull('event_rating')->avg('event_rating'),
            'feedback_count' => $registrations->where('feedback_submitted', true)->count(),
            'payment_pending' => $registrations->where('payment_status', 'pending')->count(),
            'payment_completed' => $registrations->where('payment_status', 'paid')->count(),
        ];
    }

    /**
     * Get user's event history
     */
    public static function getUserEventHistory(User $user): array
    {
        $registrations = static::where('user_id', $user->id)->with('event');
        
        return [
            'total_events' => $registrations->count(),
            'attended_events' => $registrations->where('status', 'attended')->count(),
            'upcoming_events' => $registrations->whereHas('event', function ($query) {
                $query->where('start_datetime', '>', now());
            })->count(),
            'cancelled_registrations' => $registrations->where('status', 'cancelled')->count(),
            'average_rating_given' => $registrations->whereNotNull('event_rating')->avg('event_rating'),
            'total_networking_connections' => $registrations->get()->sum('networking_connections_count'),
            'favorite_event_types' => static::getUserFavoriteEventTypes($user),
            'recent_events' => $registrations->with('event')
                ->orderByDesc('registered_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get user's favorite event types
     */
    private static function getUserFavoriteEventTypes(User $user): array
    {
        return static::where('user_id', $user->id)
            ->join('community_events', 'event_registrations.event_id', '=', 'community_events.id')
            ->selectRaw('community_events.type, COUNT(*) as count')
            ->groupBy('community_events.type')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Send registration confirmation
     */
    public function sendConfirmation(): void
    {
        // TODO: Implement notification sending
        // This would typically send an email with event details, calendar invite, etc.
    }

    /**
     * Send reminder notification
     */
    public function sendReminder(): void
    {
        // TODO: Implement reminder notification
        // This would be called by a scheduled job before the event
    }

    /**
     * Process refund
     */
    public function processRefund(float $amount = null): void
    {
        if (!$this->payment_required || $this->payment_status !== 'paid') {
            throw new \InvalidArgumentException('No payment to refund.');
        }

        $refundAmount = $amount ?? $this->amount_paid;
        
        // TODO: Implement actual payment processing refund
        
        $this->update([
            'payment_status' => 'refunded',
            'amount_paid' => $this->amount_paid - $refundAmount,
        ]);
    }
}