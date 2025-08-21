<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'user_id',
        'cover_letter',
        'availability',
        'experience',
        'skills',
        'motivation',
        'status',
        'reviewer_notes',
        'withdrawal_reason',
        'withdrawn_at'
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'skills' => 'array'
    ];

    /**
     * Get the opportunity this application is for
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteeringOpportunity::class, 'opportunity_id');
    }

    /**
     * Get the user who submitted this application
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who reviewed this application
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the assignment for this application (if accepted)
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(VolunteerAssignment::class, 'application_id');
    }

    /**
     * Get the status history for this application
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(VolunteerApplicationStatusHistory::class, 'application_id');
    }

    /**
     * Get the messages for this application
     */
    public function messages(): HasMany
    {
        return $this->hasMany(VolunteerApplicationMessage::class, 'application_id');
    }

    /**
     * Scope to get pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted applications
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get rejected applications
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get withdrawn applications
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    /**
     * Scope to filter by opportunity
     */
    public function scopeForOpportunity($query, $opportunityId)
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if application is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if application is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if application is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if application is withdrawn
     */
    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    /**
     * Accept the application and create assignment
     */
    public function accept(User $reviewer, array $assignmentData = []): VolunteerAssignment
    {
        $this->update([
            'status' => 'accepted',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id
        ]);

        // Update opportunity volunteer count
        $this->opportunity->incrementVolunteers();

        // Create assignment
        return $this->assignment()->create(array_merge([
            'start_date' => $assignmentData['start_date'] ?? now()->toDateString(),
            'hours_committed' => $assignmentData['hours_committed'] ?? null,
            'supervisor_id' => $assignmentData['supervisor_id'] ?? null,
            'status' => 'active'
        ], $assignmentData));
    }

    /**
     * Reject the application
     */
    public function reject(User $reviewer, string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
            'reviewer_notes' => $reason
        ]);
    }

    /**
     * Withdraw the application
     */
    public function withdraw(): void
    {
        $this->update([
            'status' => 'withdrawn'
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'accepted' => 'success',
            'rejected' => 'danger',
            'withdrawn' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get days since application
     */
    public function getDaysSinceApplicationAttribute(): int
    {
        return $this->applied_at->diffInDays(now());
    }

    /**
     * Check if application has been reviewed
     */
    public function getIsReviewedAttribute(): bool
    {
        return !is_null($this->reviewed_at);
    }
}