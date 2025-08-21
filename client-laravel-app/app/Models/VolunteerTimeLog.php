<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class VolunteerTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'activity_description',
        'supervisor_approved',
        'approved_by'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'hours' => 'decimal:2',
        'supervisor_approved' => 'boolean',
        'approved_at' => 'datetime'
    ];

    /**
     * Get the assignment this time log belongs to
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VolunteerAssignment::class, 'assignment_id');
    }

    /**
     * Get the user who approved this time log
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope to get approved time logs
     */
    public function scopeApproved($query)
    {
        return $query->where('supervisor_approved', true);
    }

    /**
     * Scope to get pending approval time logs
     */
    public function scopePendingApproval($query)
    {
        return $query->where('supervisor_approved', false);
    }

    /**
     * Scope to filter by assignment
     */
    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by month
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
    }

    /**
     * Check if time log is approved
     */
    public function isApproved(): bool
    {
        return $this->supervisor_approved;
    }

    /**
     * Check if time log is pending approval
     */
    public function isPending(): bool
    {
        return !$this->supervisor_approved;
    }

    /**
     * Approve the time log
     */
    public function approve(User $supervisor): void
    {
        $this->update([
            'supervisor_approved' => true,
            'approved_by' => $supervisor->id,
            'approved_at' => now()
        ]);

        // Update assignment hours
        $this->assignment->increment('hours_completed', $this->hours);
    }

    /**
     * Reject/unapprove the time log
     */
    public function unapprove(): void
    {
        if ($this->supervisor_approved) {
            // Decrease assignment hours
            $this->assignment->decrement('hours_completed', $this->hours);
        }

        $this->update([
            'supervisor_approved' => false,
            'approved_by' => null,
            'approved_at' => null
        ]);
    }

    /**
     * Calculate hours from start and end time
     */
    public static function calculateHours(string $startTime, string $endTime): float
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        return $end->diffInMinutes($start) / 60;
    }

    /**
     * Get formatted time range
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get approval status badge color
     */
    public function getApprovalStatusColorAttribute(): string
    {
        return $this->supervisor_approved ? 'success' : 'warning';
    }

    /**
     * Get formatted approval status
     */
    public function getFormattedApprovalStatusAttribute(): string
    {
        return $this->supervisor_approved ? 'Approved' : 'Pending Approval';
    }

    /**
     * Get days since logged
     */
    public function getDaysSinceLoggedAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Validate time log data before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($timeLog) {
            // Ensure end time is after start time
            if ($timeLog->start_time && $timeLog->end_time) {
                $start = Carbon::parse($timeLog->start_time);
                $end = Carbon::parse($timeLog->end_time);
                
                if ($end->lte($start)) {
                    throw new \InvalidArgumentException('End time must be after start time');
                }
                
                // Recalculate hours if not provided
                if (!$timeLog->hours) {
                    $timeLog->hours = $end->diffInMinutes($start) / 60;
                }
            }

            // Ensure date is not in the future
            if ($timeLog->date && $timeLog->date->isFuture()) {
                throw new \InvalidArgumentException('Date cannot be in the future');
            }
        });
    }
}