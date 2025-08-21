<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderator_id',
        'target_type',
        'target_id',
        'action_type',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function scopeByModerator($query, User $moderator)
    {
        return $query->where('moderator_id', $moderator->id);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action_type', $action);
    }

    public function scopeByTargetType($query, string $type)
    {
        return $query->where('target_type', $type);
    }

    public function getActionDescriptionAttribute(): string
    {
        return match ($this->action_type) {
            'pin' => 'Pinned thread',
            'unpin' => 'Unpinned thread',
            'lock' => 'Locked thread',
            'unlock' => 'Unlocked thread',
            'delete' => 'Deleted ' . $this->target_type,
            'edit' => 'Edited ' . $this->target_type,
            'warn' => 'Warned user',
            'suspend' => 'Suspended user',
            'ban' => 'Banned user',
            'report' => 'Handled report',
            default => ucfirst($this->action_type) . ' ' . $this->target_type
        };
    }
}