<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderator_id',
        'forum_id',
        'reason',
        'description',
        'is_permanent',
        'expires_at',
        'is_active',
        'lifted_at',
        'lifted_by',
        'lift_reason',
        'ip_address',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'expires_at' => 'datetime',
        'lifted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function liftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->where('is_permanent', true)
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function isActive(): bool
    {
        return $this->is_active && ($this->is_permanent || $this->expires_at->isFuture());
    }

    public function lift(User $moderator, string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'lifted_at' => now(),
            'lifted_by' => $moderator->id,
            'lift_reason' => $reason,
        ]);
    }
}