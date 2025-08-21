<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'icon',
        'color',
        'criteria',
        'is_automatic',
        'is_active',
        'points_value',
        'rarity',
        'max_recipients',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_automatic' => 'boolean',
        'is_active' => 'boolean',
        'points_value' => 'integer',
        'max_recipients' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get user awards
     */
    public function userAwards(): HasMany
    {
        return $this->hasMany(UserAward::class, 'award_id');
    }

    /**
     * Scope for active awards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for automatic awards
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope for manual awards
     */
    public function scopeManual($query)
    {
        return $query->where('is_automatic', false);
    }

    /**
     * Scope for awards by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for awards by category
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for valid awards (within date range)
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', now());
        });
    }

    /**
     * Check if award is currently valid
     */
    public function isValid(): bool
    {
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }
        
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }
        
        return $this->is_active;
    }

    /**
     * Check if award has reached max recipients
     */
    public function hasReachedMaxRecipients(): bool
    {
        if (!$this->max_recipients) {
            return false;
        }
        
        return $this->userAwards()->where('status', 'active')->count() >= $this->max_recipients;
    }

    /**
     * Check if user can receive this award
     */
    public function canUserReceive(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        
        if ($this->hasReachedMaxRecipients()) {
            return false;
        }
        
        // Check if user already has this award
        if ($this->userAwards()->where('user_id', $user->id)->where('status', 'active')->exists()) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if user meets criteria for automatic award
     */
    public function checkUserCriteria(User $user): bool
    {
        if (!$this->is_automatic || !$this->criteria) {
            return false;
        }
        
        foreach ($this->criteria as $criterion) {
            if (!$this->evaluateCriterion($user, $criterion)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single criterion
     */
    private function evaluateCriterion(User $user, array $criterion): bool
    {
        $type = $criterion['type'] ?? '';
        $operator = $criterion['operator'] ?? '>=';
        $value = $criterion['value'] ?? 0;
        
        $userValue = $this->getUserValue($user, $type);
        
        return match ($operator) {
            '>=' => $userValue >= $value,
            '>' => $userValue > $value,
            '<=' => $userValue <= $value,
            '<' => $userValue < $value,
            '==' => $userValue == $value,
            '!=' => $userValue != $value,
            default => false,
        };
    }

    /**
     * Get user value for criterion type
     */
    private function getUserValue(User $user, string $type): mixed
    {
        return match ($type) {
            'volunteer_hours' => $user->volunteerTimeLogs()->sum('hours_logged'),
            'volunteer_assignments' => $user->volunteerAssignments()->count(),
            'completed_assignments' => $user->volunteerAssignments()->where('status', 'completed')->count(),
            'organizations_helped' => $user->volunteerAssignments()->distinct('opportunity.organization_id')->count(),
            'categories_volunteered' => $user->volunteerAssignments()->distinct('opportunity.category_id')->count(),
            'feedback_rating' => $user->receivedFeedback()->avg('overall_rating'),
            'mentorships_completed' => VolunteerMentorship::forMentor($user->id)->completed()->count(),
            'mentees_helped' => VolunteerMentorship::forMentor($user->id)->completed()->count(),
            'events_attended' => EventRegistration::where('user_id', $user->id)->attended()->count(),
            'resources_contributed' => VolunteerResource::where('contributor_id', $user->id)->approved()->count(),
            'community_connections' => VolunteerConnection::forUser($user->id)->accepted()->count(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            'profile_completeness' => $this->calculateProfileCompleteness($user),
            default => 0,
        };
    }

    /**
     * Calculate profile completeness percentage
     */
    private function calculateProfileCompleteness(User $user): int
    {
        $fields = [
            'name' => !empty($user->name),
            'email' => !empty($user->email),
            'phone' => !empty($user->phone),
            'bio' => !empty($user->bio),
            'avatar' => !empty($user->avatar),
            'skills' => $user->skills()->exists(),
            'interests' => $user->volunteeringInterests()->exists(),
            'location' => !empty($user->city) && !empty($user->country),
        ];
        
        $completed = array_sum($fields);
        $total = count($fields);
        
        return round(($completed / $total) * 100);
    }

    /**
     * Award to user
     */
    public function awardToUser(User $user, User $awardedBy = null, string $reason = null, array $achievementData = []): UserAward
    {
        if (!$this->canUserReceive($user)) {
            throw new \InvalidArgumentException('User cannot receive this award.');
        }
        
        return UserAward::create([
            'user_id' => $user->id,
            'award_id' => $this->id,
            'awarded_by' => $awardedBy?->id,
            'reason' => $reason,
            'achievement_data' => $achievementData,
            'earned_date' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'achievement' => 'Achievement',
            'milestone' => 'Milestone',
            'recognition' => 'Recognition',
            'badge' => 'Badge',
            'certificate' => 'Certificate',
            'nomination' => 'Nomination',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get category display name
     */
    public function getCategoryDisplayAttribute(): string
    {
        return match ($this->category) {
            'hours' => 'Volunteer Hours',
            'impact' => 'Impact',
            'leadership' => 'Leadership',
            'innovation' => 'Innovation',
            'collaboration' => 'Collaboration',
            'dedication' => 'Dedication',
            'skill' => 'Skill Development',
            'community' => 'Community Building',
            'special' => 'Special Recognition',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get rarity display name
     */
    public function getRarityDisplayAttribute(): string
    {
        return match ($this->rarity) {
            'common' => 'Common',
            'uncommon' => 'Uncommon',
            'rare' => 'Rare',
            'epic' => 'Epic',
            'legendary' => 'Legendary',
            default => ucfirst($this->rarity),
        };
    }

    /**
     * Get rarity color
     */
    public function getRarityColorAttribute(): string
    {
        return match ($this->rarity) {
            'common' => '#6B7280',
            'uncommon' => '#10B981',
            'rare' => '#3B82F6',
            'epic' => '#8B5CF6',
            'legendary' => '#F59E0B',
            default => '#6B7280',
        };
    }

    /**
     * Get recipients count
     */
    public function getRecipientsCountAttribute(): int
    {
        return $this->userAwards()->where('status', 'active')->count();
    }

    /**
     * Get availability status
     */
    public function getAvailabilityStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return 'Not Yet Available';
        }
        
        if ($this->valid_until && $this->valid_until->isPast()) {
            return 'Expired';
        }
        
        if ($this->hasReachedMaxRecipients()) {
            return 'Max Recipients Reached';
        }
        
        return 'Available';
    }

    /**
     * Process automatic awards for all eligible users
     */
    public static function processAutomaticAwards(): array
    {
        $results = [];
        $automaticAwards = static::automatic()->active()->valid()->get();
        
        foreach ($automaticAwards as $award) {
            $results[$award->id] = $award->processForAllUsers();
        }
        
        return $results;
    }

    /**
     * Process this award for all users
     */
    public function processForAllUsers(): array
    {
        $awarded = [];
        $users = User::all();
        
        foreach ($users as $user) {
            if ($this->canUserReceive($user) && $this->checkUserCriteria($user)) {
                try {
                    $userAward = $this->awardToUser($user, null, 'Automatically awarded based on criteria');
                    $awarded[] = $userAward;
                    
                    // TODO: Send notification to user
                } catch (\Exception $e) {
                    // Log error but continue processing other users
                    \Log::error("Failed to award {$this->name} to user {$user->id}: " . $e->getMessage());
                }
            }
        }
        
        return $awarded;
    }

    /**
     * Get award statistics
     */
    public static function getStatistics(): array
    {
        $totalAwards = static::count();
        $activeAwards = static::active()->count();
        $automaticAwards = static::automatic()->count();
        $totalRecipients = UserAward::where('status', 'active')->count();
        
        return [
            'total_awards' => $totalAwards,
            'active_awards' => $activeAwards,
            'automatic_awards' => $automaticAwards,
            'manual_awards' => $totalAwards - $automaticAwards,
            'total_recipients' => $totalRecipients,
            'awards_by_type' => static::getAwardsByType(),
            'awards_by_category' => static::getAwardsByCategory(),
            'awards_by_rarity' => static::getAwardsByRarity(),
            'most_awarded' => static::getMostAwarded(),
            'recent_awards' => static::getRecentAwards(),
        ];
    }

    /**
     * Get awards by type
     */
    private static function getAwardsByType(): array
    {
        return static::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get awards by category
     */
    private static function getAwardsByCategory(): array
    {
        return static::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Get awards by rarity
     */
    private static function getAwardsByRarity(): array
    {
        return static::selectRaw('rarity, COUNT(*) as count')
            ->groupBy('rarity')
            ->orderByDesc('count')
            ->pluck('count', 'rarity')
            ->toArray();
    }

    /**
     * Get most awarded
     */
    private static function getMostAwarded(int $limit = 10): array
    {
        return static::withCount(['userAwards' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderByDesc('user_awards_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent awards
     */
    private static function getRecentAwards(int $limit = 10): array
    {
        return UserAward::with(['award', 'user'])
            ->where('status', 'active')
            ->orderByDesc('earned_date')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Create default awards
     */
    public static function createDefaults(User $creator): array
    {
        $defaultAwards = [
            [
                'name' => 'First Steps',
                'description' => 'Complete your first volunteer assignment',
                'type' => 'milestone',
                'category' => 'dedication',
                'icon' => 'star',
                'color' => '#10B981',
                'criteria' => [
                    ['type' => 'completed_assignments', 'operator' => '>=', 'value' => 1]
                ],
                'is_automatic' => true,
                'points_value' => 10,
                'rarity' => 'common',
            ],
            [
                'name' => 'Dedicated Volunteer',
                'description' => 'Complete 10 volunteer assignments',
                'type' => 'achievement',
                'category' => 'dedication',
                'icon' => 'trophy',
                'color' => '#3B82F6',
                'criteria' => [
                    ['type' => 'completed_assignments', 'operator' => '>=', 'value' => 10]
                ],
                'is_automatic' => true,
                'points_value' => 50,
                'rarity' => 'uncommon',
            ],
            [
                'name' => '100 Hours Hero',
                'description' => 'Log 100 hours of volunteer service',
                'type' => 'milestone',
                'category' => 'hours',
                'icon' => 'clock',
                'color' => '#8B5CF6',
                'criteria' => [
                    ['type' => 'volunteer_hours', 'operator' => '>=', 'value' => 100]
                ],
                'is_automatic' => true,
                'points_value' => 100,
                'rarity' => 'rare',
            ],
            [
                'name' => 'Community Builder',
                'description' => 'Connect with 5 other volunteers',
                'type' => 'achievement',
                'category' => 'community',
                'icon' => 'users',
                'color' => '#F59E0B',
                'criteria' => [
                    ['type' => 'community_connections', 'operator' => '>=', 'value' => 5]
                ],
                'is_automatic' => true,
                'points_value' => 30,
                'rarity' => 'uncommon',
            ],
            [
                'name' => 'Mentor',
                'description' => 'Complete a mentorship as a mentor',
                'type' => 'recognition',
                'category' => 'leadership',
                'icon' => 'academic-cap',
                'color' => '#EF4444',
                'criteria' => [
                    ['type' => 'mentorships_completed', 'operator' => '>=', 'value' => 1]
                ],
                'is_automatic' => true,
                'points_value' => 75,
                'rarity' => 'rare',
            ],
        ];
        
        $created = [];
        foreach ($defaultAwards as $awardData) {
            $awardData['created_by'] = $creator->id;
            $created[] = static::create($awardData);
        }
        
        return $created;
    }
}