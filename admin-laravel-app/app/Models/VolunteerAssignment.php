<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class VolunteerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'start_date',
        'end_date',
        'hours_committed',
        'hours_completed',
        'supervisor_id',
        'status',
        'completion_notes',
        'rating',
        'feedback',
        'certificate_issued'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'certificate_issued' => 'boolean'
    ];

    /**
     * Get the application this assignment belongs to
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(VolunteerApplication::class, 'application_id');
    }

    /**
     * Get the volunteer through the application
     */
    public function volunteer(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            VolunteerApplication::class,
            'id',
            'id',
            'application_id',
            'user_id'
        );
    }

    /**
     * Get the opportunity through the application
     */
    public function opportunity(): HasOneThrough
    {
        return $this->hasOneThrough(
            VolunteeringOpportunity::class,
            VolunteerApplication::class,
            'id',
            'id',
            'application_id',
            'opportunity_id'
        );
    }

    /**
     * Get the supervisor for this assignment
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get all time logs for this assignment
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(VolunteerTimeLog::class, 'assignment_id');
    }

    /**
     * Get approved time logs for this assignment
     */
    public function approvedTimeLogs(): HasMany
    {
        return $this->timeLogs()->where('supervisor_approved', true);
    }

    /**
     * Get pending time logs for this assignment
     */
    public function pendingTimeLogs(): HasMany
    {
        return $this->timeLogs()->where('supervisor_approved', false);
    }

    /**
     * Scope to get active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter by supervisor
     */
    public function scopeBySupervisor($query, $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }

    /**
     * Scope to filter by volunteer
     */
    public function scopeByVolunteer($query, $userId)
    {
        return $query->whereHas('application', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Check if assignment is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if assignment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get total hours logged
     */
    public function getTotalHoursLoggedAttribute(): float
    {
        return $this->timeLogs()->sum('hours');
    }

    /**
     * Get total approved hours
     */
    public function getTotalApprovedHoursAttribute(): float
    {
        return $this->approvedTimeLogs()->sum('hours');
    }

    /**
     * Get pending hours count
     */
    public function getPendingHoursAttribute(): float
    {
        return $this->pendingTimeLogs()->sum('hours');
    }

    /**
     * Get completion percentage based on committed hours
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (!$this->hours_committed || $this->hours_committed <= 0) {
            return 0;
        }

        return min(100, ($this->total_approved_hours / $this->hours_committed) * 100);
    }

    /**
     * Get remaining hours to complete
     */
    public function getRemainingHoursAttribute(): float
    {
        if (!$this->hours_committed) {
            return 0;
        }

        return max(0, $this->hours_committed - $this->total_approved_hours);
    }

    /**
     * Get assignment duration in days
     */
    public function getDurationInDaysAttribute(): ?int
    {
        if (!$this->start_date) {
            return null;
        }

        $endDate = $this->end_date ?? now();
        return $this->start_date->diffInDays($endDate);
    }

    /**
     * Check if assignment has pending time logs
     */
    public function hasPendingTimeLogs(): bool
    {
        return $this->pendingTimeLogs()->exists();
    }

    /**
     * Complete the assignment
     */
    public function complete(array $data = []): void
    {
        $this->update(array_merge([
            'status' => 'completed',
            'end_date' => $data['end_date'] ?? now()->toDateString(),
            'hours_completed' => $this->total_approved_hours
        ], $data));
    }

    /**
     * Terminate the assignment
     */
    public function terminate(string $reason = null): void
    {
        $this->update([
            'status' => 'terminated',
            'end_date' => now()->toDateString(),
            'completion_notes' => $reason
        ]);
    }

    /**
     * Put assignment on hold
     */
    public function putOnHold(string $reason = null): void
    {
        $this->update([
            'status' => 'on_hold',
            'completion_notes' => $reason
        ]);
    }

    /**
     * Resume assignment from hold
     */
    public function resume(): void
    {
        $this->update([
            'status' => 'active'
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'completed' => 'primary',
            'terminated' => 'danger',
            'on_hold' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return match ($this->status) {
            'on_hold' => 'On Hold',
            default => ucfirst($this->status)
        };
    }
}