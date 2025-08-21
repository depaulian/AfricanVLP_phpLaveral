<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'award_id',
        'awarded_by',
        'organization_id',
        'reason',
        'achievement_data',
        'is_public',
        'is_featured',
        'earned_date',
        'expires_at',
        'status',
        'revocation_reason',
        'revoked_by',
        'revoked_at',
    ];

    protected $casts = [
        'achievement_data' => 'array',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'earned_date' => 'date',
        'expires_at' => 'date',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user who received the award
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the award
     */
    public function award(): BelongsTo
    {
        return $this->belongsTo(VolunteerAward::class, 'award_id');
    }

    /**
     * Get the user who awarded this
     */
    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    /**
     * Get the organization (if awarded by organization)
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who revoked this award
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Scope for active awards
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for revoked awards
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    /**
     * Scope for expired awards
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for public awards
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for featured awards
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for awards by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for awards by organization
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Check if award is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        
        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->markAsExpired();
            return false;
        }
        
        return true;
    }

    /**
     * Check if award has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark award as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Revoke the award
     */
    public function revoke(User $revokedBy, string $reason): void
    {
        $this->update([
            'status' => 'revoked',
            'revocation_reason' => $reason,
            'revoked_by' => $revokedBy->id,
            'revoked_at' => now(),
        ]);
    }

    /**
     * Feature the award
     */
    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    /**
     * Unfeature the award
     */
    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    /**
     * Make award public
     */
    public function makePublic(): void
    {
        $this->update(['is_public' => true]);
    }

    /**
     * Make award private
     */
    public function makePrivate(): void
    {
        $this->update(['is_public' => false]);
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'revoked' => 'Revoked',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'revoked' => 'red',
            'expired' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get time since earned
     */
    public function getTimeSinceEarnedAttribute(): string
    {
        return $this->earned_date->diffForHumans();
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at || $this->status !== 'active') {
            return null;
        }
        
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if award is expiring soon (within 30 days)
     */
    public function isExpiringSoon(): bool
    {
        $daysUntilExpiration = $this->days_until_expiration;
        return $daysUntilExpiration !== null && $daysUntilExpiration <= 30;
    }

    /**
     * Get achievement data value
     */
    public function getAchievementValue(string $key, $default = null)
    {
        return $this->achievement_data[$key] ?? $default;
    }

    /**
     * Set achievement data value
     */
    public function setAchievementValue(string $key, $value): void
    {
        $data = $this->achievement_data ?? [];
        $data[$key] = $value;
        $this->update(['achievement_data' => $data]);
    }

    /**
     * Get award display information
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'award_name' => $this->award->name,
            'award_description' => $this->award->description,
            'award_icon' => $this->award->icon,
            'award_color' => $this->award->color,
            'award_type' => $this->award->type_display,
            'award_category' => $this->award->category_display,
            'award_rarity' => $this->award->rarity_display,
            'earned_date' => $this->earned_date->format('M d, Y'),
            'time_since_earned' => $this->time_since_earned,
            'status' => $this->status_display,
            'is_featured' => $this->is_featured,
            'points_value' => $this->award->points_value,
        ];
    }

    /**
     * Get user's awards summary
     */
    public static function getUserAwardsSummary(User $user): array
    {
        $awards = static::forUser($user->id)->with('award');
        
        $totalAwards = $awards->count();
        $activeAwards = $awards->active()->count();
        $featuredAwards = $awards->featured()->count();
        $totalPoints = $awards->active()->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->sum('volunteer_awards.points_value');
        
        $awardsByType = $awards->active()
            ->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->selectRaw('volunteer_awards.type, COUNT(*) as count')
            ->groupBy('volunteer_awards.type')
            ->pluck('count', 'type')
            ->toArray();
        
        $awardsByCategory = $awards->active()
            ->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->selectRaw('volunteer_awards.category, COUNT(*) as count')
            ->groupBy('volunteer_awards.category')
            ->pluck('count', 'category')
            ->toArray();
        
        $awardsByRarity = $awards->active()
            ->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->selectRaw('volunteer_awards.rarity, COUNT(*) as count')
            ->groupBy('volunteer_awards.rarity')
            ->pluck('count', 'rarity')
            ->toArray();
        
        $recentAwards = $awards->active()
            ->with('award')
            ->orderByDesc('earned_date')
            ->limit(5)
            ->get();
        
        $expiringSoon = $awards->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->with('award')
            ->get();
        
        return [
            'total_awards' => $totalAwards,
            'active_awards' => $activeAwards,
            'featured_awards' => $featuredAwards,
            'total_points' => $totalPoints,
            'awards_by_type' => $awardsByType,
            'awards_by_category' => $awardsByCategory,
            'awards_by_rarity' => $awardsByRarity,
            'recent_awards' => $recentAwards,
            'expiring_soon' => $expiringSoon,
            'achievement_level' => static::calculateAchievementLevel($totalPoints),
        ];
    }

    /**
     * Calculate achievement level based on points
     */
    private static function calculateAchievementLevel(int $points): array
    {
        $levels = [
            ['name' => 'Newcomer', 'min_points' => 0, 'color' => '#6B7280'],
            ['name' => 'Helper', 'min_points' => 50, 'color' => '#10B981'],
            ['name' => 'Contributor', 'min_points' => 150, 'color' => '#3B82F6'],
            ['name' => 'Dedicated', 'min_points' => 300, 'color' => '#8B5CF6'],
            ['name' => 'Champion', 'min_points' => 500, 'color' => '#F59E0B'],
            ['name' => 'Hero', 'min_points' => 750, 'color' => '#EF4444'],
            ['name' => 'Legend', 'min_points' => 1000, 'color' => '#EC4899'],
        ];
        
        $currentLevel = $levels[0];
        $nextLevel = null;
        
        foreach ($levels as $index => $level) {
            if ($points >= $level['min_points']) {
                $currentLevel = $level;
                $nextLevel = $levels[$index + 1] ?? null;
            } else {
                break;
            }
        }
        
        $progressToNext = 0;
        if ($nextLevel) {
            $pointsNeeded = $nextLevel['min_points'] - $currentLevel['min_points'];
            $pointsEarned = $points - $currentLevel['min_points'];
            $progressToNext = ($pointsEarned / $pointsNeeded) * 100;
        }
        
        return [
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'current_points' => $points,
            'points_to_next' => $nextLevel ? $nextLevel['min_points'] - $points : 0,
            'progress_percentage' => round($progressToNext, 1),
        ];
    }

    /**
     * Get leaderboard
     */
    public static function getLeaderboard(int $limit = 10, array $filters = []): array
    {
        $query = static::active()
            ->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->join('users', 'user_awards.user_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                users.avatar,
                COUNT(*) as total_awards,
                SUM(volunteer_awards.points_value) as total_points
            ')
            ->groupBy('users.id', 'users.name', 'users.avatar');
        
        if (isset($filters['organization_id'])) {
            $query->where('user_awards.organization_id', $filters['organization_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('user_awards.earned_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('user_awards.earned_date', '<=', $filters['date_to']);
        }
        
        $leaderboard = $query->orderByDesc('total_points')
            ->orderByDesc('total_awards')
            ->limit($limit)
            ->get();
        
        return $leaderboard->map(function ($entry, $index) {
            return [
                'rank' => $index + 1,
                'user_id' => $entry->id,
                'user_name' => $entry->name,
                'user_avatar' => $entry->avatar,
                'total_awards' => $entry->total_awards,
                'total_points' => $entry->total_points,
                'achievement_level' => static::calculateAchievementLevel($entry->total_points),
            ];
        })->toArray();
    }

    /**
     * Get award statistics
     */
    public static function getStatistics(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('earned_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('earned_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        $totalAwarded = $query->count();
        $activeAwarded = $query->where('status', 'active')->count();
        $revokedAwarded = $query->where('status', 'revoked')->count();
        $expiredAwarded = $query->where('status', 'expired')->count();
        
        $uniqueRecipients = $query->distinct('user_id')->count();
        $totalPoints = $query->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id')
            ->where('user_awards.status', 'active')
            ->sum('volunteer_awards.points_value');
        
        return [
            'total_awarded' => $totalAwarded,
            'active_awarded' => $activeAwarded,
            'revoked_awarded' => $revokedAwarded,
            'expired_awarded' => $expiredAwarded,
            'unique_recipients' => $uniqueRecipients,
            'total_points_awarded' => $totalPoints,
            'average_awards_per_user' => $uniqueRecipients > 0 ? round($activeAwarded / $uniqueRecipients, 2) : 0,
            'monthly_trends' => static::getMonthlyTrends($filters),
            'top_awards' => static::getTopAwards($filters),
            'recent_awards' => static::getRecentAwards($filters),
        ];
    }

    /**
     * Get monthly trends
     */
    private static function getMonthlyTrends(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('earned_date', '>=', $filters['date_from']);
        } else {
            $query->where('earned_date', '>=', now()->subYear());
        }
        
        if (isset($filters['date_to'])) {
            $query->where('earned_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        return $query->selectRaw('
                DATE_FORMAT(earned_date, "%Y-%m") as month,
                COUNT(*) as total_awarded,
                COUNT(DISTINCT user_id) as unique_recipients
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get top awards
     */
    private static function getTopAwards(array $filters = [], int $limit = 10): array
    {
        $query = static::active()
            ->join('volunteer_awards', 'user_awards.award_id', '=', 'volunteer_awards.id');
        
        if (isset($filters['date_from'])) {
            $query->where('user_awards.earned_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('user_awards.earned_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['organization_id'])) {
            $query->where('user_awards.organization_id', $filters['organization_id']);
        }
        
        return $query->selectRaw('
                volunteer_awards.id,
                volunteer_awards.name,
                volunteer_awards.type,
                volunteer_awards.category,
                volunteer_awards.rarity,
                COUNT(*) as recipients_count
            ')
            ->groupBy('volunteer_awards.id', 'volunteer_awards.name', 'volunteer_awards.type', 'volunteer_awards.category', 'volunteer_awards.rarity')
            ->orderByDesc('recipients_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent awards
     */
    private static function getRecentAwards(array $filters = [], int $limit = 10): array
    {
        $query = static::with(['user', 'award']);
        
        if (isset($filters['date_from'])) {
            $query->where('earned_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('earned_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        return $query->orderByDesc('earned_date')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Process expired awards
     */
    public static function processExpiredAwards(): int
    {
        $expiredCount = static::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
        
        return $expiredCount;
    }
}