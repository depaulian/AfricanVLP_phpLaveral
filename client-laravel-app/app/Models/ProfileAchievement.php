<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_type',
        'achievement_name',
        'achievement_description',
        'badge_icon',
        'badge_color',
        'points_awarded',
        'earned_at',
        'is_featured',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'is_featured' => 'boolean',
        'points_awarded' => 'integer',
    ];

    /**
     * Achievement types
     */
    const TYPES = [
        'profile_completion' => 'Profile Completion',
        'skill_verification' => 'Skill Verification',
        'document_upload' => 'Document Upload',
        'volunteering_history' => 'Volunteering History',
        'social_connection' => 'Social Connection',
        'platform_engagement' => 'Platform Engagement',
    ];

    /**
     * Get the user that owns the achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPES[$this->achievement_type] ?? 'Unknown';
    }

    /**
     * Check if achievement was earned recently (within 7 days).
     */
    public function isRecent(): bool
    {
        return $this->earned_at && $this->earned_at->isAfter(now()->subDays(7));
    }

    /**
     * Scope for featured achievements.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for recent achievements.
     */
    public function scopeRecent($query)
    {
        return $query->where('earned_at', '>=', now()->subDays(7));
    }

    /**
     * Scope by achievement type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('achievement_type', $type);
    }
}