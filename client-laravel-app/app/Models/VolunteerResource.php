<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VolunteerResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'contributor_id',
        'organization_id',
        'categories',
        'tags',
        'difficulty_level',
        'target_roles',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'external_url',
        'content',
        'metadata',
        'visibility',
        'requires_approval',
        'is_featured',
        'is_verified',
        'verified_by',
        'verified_at',
        'download_count',
        'view_count',
        'like_count',
        'bookmark_count',
        'average_rating',
        'rating_count',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'version',
        'parent_resource_id',
        'version_notes',
    ];

    protected $casts = [
        'categories' => 'array',
        'tags' => 'array',
        'target_roles' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'bookmark_count' => 'integer',
        'rating_count' => 'integer',
        'average_rating' => 'decimal:1',
        'requires_approval' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the contributor user
     */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the verifier user
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the reviewer user
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the parent resource (for versions)
     */
    public function parentResource(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_resource_id');
    }

    /**
     * Get child versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(static::class, 'parent_resource_id');
    }

    /**
     * Get resource interactions
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(ResourceInteraction::class, 'resource_id');
    }

    /**
     * Scope for approved resources
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending resources
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending_review');
    }

    /**
     * Scope for public resources
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for featured resources
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for verified resources
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for resources by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for resources by difficulty level
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
        
        // Record interaction
        $this->recordInteraction('view');
    }

    /**
     * Increment download count
     */
    public function incrementDownloads(): void
    {
        $this->increment('download_count');
        
        // Record interaction
        $this->recordInteraction('download');
    }

    /**
     * Toggle like
     */
    public function toggleLike(User $user): bool
    {
        $interaction = $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'like')
            ->first();
        
        if ($interaction) {
            $interaction->delete();
            $this->decrement('like_count');
            return false;
        } else {
            $this->recordInteraction('like', $user);
            $this->increment('like_count');
            return true;
        }
    }

    /**
     * Toggle bookmark
     */
    public function toggleBookmark(User $user): bool
    {
        $interaction = $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'bookmark')
            ->first();
        
        if ($interaction) {
            $interaction->delete();
            $this->decrement('bookmark_count');
            return false;
        } else {
            $this->recordInteraction('bookmark', $user);
            $this->increment('bookmark_count');
            return true;
        }
    }

    /**
     * Add rating
     */
    public function addRating(User $user, float $rating, string $comment = null): void
    {
        // Remove existing rating
        $existingInteraction = $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'rating')
            ->first();
        
        if ($existingInteraction) {
            $existingInteraction->update([
                'rating' => $rating,
                'comment' => $comment,
            ]);
        } else {
            $this->recordInteraction('rating', $user, [
                'rating' => $rating,
                'comment' => $comment,
            ]);
            $this->increment('rating_count');
        }
        
        // Recalculate average rating
        $this->updateAverageRating();
    }

    /**
     * Update average rating
     */
    public function updateAverageRating(): void
    {
        $averageRating = $this->interactions()
            ->where('interaction_type', 'rating')
            ->whereNotNull('rating')
            ->avg('rating');
        
        $this->update(['average_rating' => $averageRating]);
    }

    /**
     * Record interaction
     */
    private function recordInteraction(string $type, User $user = null, array $data = []): void
    {
        $interactionData = [
            'resource_id' => $this->id,
            'interaction_type' => $type,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        if ($user) {
            $interactionData['user_id'] = $user->id;
        }
        
        if (isset($data['rating'])) {
            $interactionData['rating'] = $data['rating'];
        }
        
        if (isset($data['comment'])) {
            $interactionData['comment'] = $data['comment'];
        }
        
        if (!empty($data)) {
            $interactionData['metadata'] = $data;
        }
        
        ResourceInteraction::create($interactionData);
    }

    /**
     * Approve the resource
     */
    public function approve(User $reviewer): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the resource
     */
    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Verify the resource
     */
    public function verify(User $verifier): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    /**
     * Feature the resource
     */
    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    /**
     * Unfeature the resource
     */
    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    /**
     * Archive the resource
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Check if user can access resource
     */
    public function canUserAccess(User $user = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }
        
        if ($this->visibility === 'public') {
            return true;
        }
        
        if (!$user) {
            return false;
        }
        
        if ($this->visibility === 'members_only' && $user->hasRole('volunteer')) {
            return true;
        }
        
        if ($this->visibility === 'organization_only' && 
            $this->organization_id && 
            $user->organizations()->where('id', $this->organization_id)->exists()) {
            return true;
        }
        
        if ($this->visibility === 'private' && $user->id === $this->contributor_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user has liked resource
     */
    public function isLikedBy(User $user): bool
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'like')
            ->exists();
    }

    /**
     * Check if user has bookmarked resource
     */
    public function isBookmarkedBy(User $user): bool
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'bookmark')
            ->exists();
    }

    /**
     * Get user's rating for resource
     */
    public function getUserRating(User $user): ?float
    {
        $interaction = $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'rating')
            ->first();
        
        return $interaction?->rating;
    }

    /**
     * Get file URL
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        
        return Storage::url($this->file_path);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'document' => 'Document',
            'template' => 'Template',
            'guide' => 'Guide',
            'checklist' => 'Checklist',
            'video' => 'Video',
            'audio' => 'Audio',
            'image' => 'Image',
            'link' => 'External Link',
            'tool' => 'Tool',
            'course' => 'Course',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get difficulty level display
     */
    public function getDifficultyDisplayAttribute(): string
    {
        return match ($this->difficulty_level) {
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'all_levels' => 'All Levels',
            default => ucfirst($this->difficulty_level),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'archived' => 'Archived',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_review' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'archived' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get popularity score
     */
    public function getPopularityScoreAttribute(): int
    {
        return ($this->view_count * 1) + 
               ($this->download_count * 3) + 
               ($this->like_count * 2) + 
               ($this->bookmark_count * 4) + 
               ($this->rating_count * 5);
    }

    /**
     * Create new version
     */
    public function createVersion(array $data, User $contributor): static
    {
        $versionNumber = $this->getNextVersionNumber();
        
        return static::create(array_merge($data, [
            'parent_resource_id' => $this->id,
            'contributor_id' => $contributor->id,
            'version' => $versionNumber,
            'status' => 'pending_review',
        ]));
    }

    /**
     * Get next version number
     */
    private function getNextVersionNumber(): string
    {
        $latestVersion = $this->versions()
            ->orderByDesc('version')
            ->first();
        
        if (!$latestVersion) {
            return '2.0';
        }
        
        $parts = explode('.', $latestVersion->version);
        $major = (int) $parts[0];
        $minor = isset($parts[1]) ? (int) $parts[1] : 0;
        
        return ($major) . '.' . ($minor + 1);
    }

    /**
     * Get similar resources
     */
    public function getSimilarResources(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::approved()
            ->public()
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('type', $this->type)
                      ->orWhere('difficulty_level', $this->difficulty_level);
                
                if ($this->categories) {
                    foreach ($this->categories as $category) {
                        $query->orWhereJsonContains('categories', $category);
                    }
                }
                
                if ($this->tags) {
                    foreach ($this->tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                }
            })
            ->orderByDesc('popularity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Search resources
     */
    public static function search(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = static::approved()->public();
        
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%")
                  ->orWhereJsonContains('tags', $keyword);
            });
        }
        
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }
        
        if (!empty($filters['categories'])) {
            foreach ((array) $filters['categories'] as $category) {
                $query->whereJsonContains('categories', $category);
            }
        }
        
        if (!empty($filters['tags'])) {
            foreach ((array) $filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }
        
        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }
        
        if (!empty($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }
        
        if (!empty($filters['contributor_id'])) {
            $query->where('contributor_id', $filters['contributor_id']);
        }
        
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        // Sorting
        $sortBy = $filters['sort_by'] ?? 'popularity';
        switch ($sortBy) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            case 'downloads':
                $query->orderByDesc('download_count');
                break;
            case 'popularity':
            default:
                $query->orderByDesc('popularity_score');
                break;
        }
        
        return $query;
    }

    /**
     * Get resource analytics
     */
    public static function getAnalytics(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        $totalResources = $query->count();
        $approvedResources = $query->where('status', 'approved')->count();
        $pendingResources = $query->where('status', 'pending_review')->count();
        $verifiedResources = $query->where('is_verified', true)->count();
        
        $totalDownloads = static::sum('download_count');
        $totalViews = static::sum('view_count');
        $averageRating = static::whereNotNull('average_rating')->avg('average_rating');
        
        return [
            'total_resources' => $totalResources,
            'approved_resources' => $approvedResources,
            'pending_resources' => $pendingResources,
            'verified_resources' => $verifiedResources,
            'total_downloads' => $totalDownloads,
            'total_views' => $totalViews,
            'average_rating' => round($averageRating, 2),
            'resources_by_type' => static::getResourcesByType($filters),
            'resources_by_difficulty' => static::getResourcesByDifficulty($filters),
            'top_contributors' => static::getTopContributors($filters),
            'most_popular' => static::getMostPopular($filters),
            'monthly_trends' => static::getMonthlyTrends($filters),
        ];
    }

    /**
     * Get resources by type
     */
    private static function getResourcesByType(array $filters = []): array
    {
        $query = static::approved();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get resources by difficulty
     */
    private static function getResourcesByDifficulty(array $filters = []): array
    {
        $query = static::approved();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('difficulty_level, COUNT(*) as count')
            ->groupBy('difficulty_level')
            ->orderByDesc('count')
            ->pluck('count', 'difficulty_level')
            ->toArray();
    }

    /**
     * Get top contributors
     */
    private static function getTopContributors(array $filters = [], int $limit = 10): array
    {
        $query = static::approved();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->join('users', 'volunteer_resources.contributor_id', '=', 'users.id')
            ->selectRaw('users.name, users.id, COUNT(*) as resource_count, SUM(download_count) as total_downloads')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resource_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get most popular resources
     */
    private static function getMostPopular(array $filters = [], int $limit = 10): array
    {
        $query = static::approved()->public();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->orderByDesc('popularity_score')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get monthly trends
     */
    private static function getMonthlyTrends(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        } else {
            $query->where('created_at', '>=', now()->subYear());
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as total_resources,
                SUM(download_count) as total_downloads,
                SUM(view_count) as total_views,
                AVG(average_rating) as avg_rating
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}