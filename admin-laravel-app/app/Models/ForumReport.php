<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ForumReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'reporter_id',
        'moderator_id',
        'reason',
        'description',
        'severity',
        'status',
        'moderator_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the reportable model (thread or post)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the report
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the moderator who handled the report
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for resolved reports
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for high severity reports
     */
    public function scopeHighSeverity($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Check if report is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if report is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Mark report as resolved
     */
    public function markAsResolved(User $moderator, ?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'moderator_id' => $moderator->id,
            'moderator_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark report as dismissed
     */
    public function markAsDismissed(User $moderator, ?string $notes = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'moderator_id' => $moderator->id,
            'moderator_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'resolved' => 'green',
            'dismissed' => 'gray',
            'escalated' => 'red',
            default => 'gray'
        };
    }
}