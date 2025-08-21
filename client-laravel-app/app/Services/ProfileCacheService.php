<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfileCacheService
{
    // Cache key constants
    public const PROFILE_KEY = 'user_profile_';
    public const PROFILE_COMPLETE_KEY = 'user_profile_complete_';
    public const PROFILE_STATS_KEY = 'user_profile_stats_';
    public const PROFILE_SEARCH_KEY = 'profile_search_';
    public const PROFILE_ANALYTICS_KEY = 'profile_analytics_';
    public const PROFILE_SKILLS_KEY = 'user_profile_skills_';
    public const PROFILE_INTERESTS_KEY = 'user_profile_interests_';
    public const PROFILE_HISTORY_KEY = 'user_profile_history_';
    public const PROFILE_DOCUMENTS_KEY = 'user_profile_documents_';

    // Default cache TTL in minutes
    protected int $defaultTtl = 60;
    protected int $searchTtl = 30;
    protected int $analyticsTtl = 120;

    /**
     * Get user profile with caching
     */
    public function getProfile(int $userId, bool $forceRefresh = false): ?UserProfile
    {
        $cacheKey = self::PROFILE_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return UserProfile::where('user_id', $userId)->first();
        });
    }

    /**
     * Get complete profile data (user + profile + metadata)
     */
    public function getCompleteProfile(int $userId, bool $forceRefresh = false): ?array
    {
        $cacheKey = self::PROFILE_COMPLETE_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            $user = User::find($userId);
            if (!$user) {
                return null;
            }

            $profile = UserProfile::where('user_id', $userId)->first();
            if (!$profile) {
                return null;
            }

            return [
                'user' => $user->toArray(),
                'profile' => $profile->toArray(),
                'cached_at' => Carbon::now()->toISOString(),
            ];
        });
    }

    /**
     * Get user skills with caching
     */
    public function getUserSkills(int $userId, bool $forceRefresh = false): array
    {
        $cacheKey = self::PROFILE_SKILLS_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            // Assuming you have a UserSkill model with relationships
            return \App\Models\UserSkill::where('user_id', $userId)
                ->with('skill') // If you have a skills table
                ->get()
                ->toArray();
        });
    }

    /**
     * Get user volunteering interests with caching
     */
    public function getUserInterests(int $userId, bool $forceRefresh = false): array
    {
        $cacheKey = self::PROFILE_INTERESTS_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return \App\Models\UserVolunteeringInterest::where('user_id', $userId)
                ->with('interest') // If you have an interests table
                ->get()
                ->toArray();
        });
    }

    /**
     * Get user volunteering history with caching
     */
    public function getUserHistory(int $userId, bool $forceRefresh = false): array
    {
        $cacheKey = self::PROFILE_HISTORY_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return \App\Models\UserVolunteeringHistory::where('user_id', $userId)
                ->orderBy('start_date', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get user documents with caching
     */
    public function getUserDocuments(int $userId, bool $forceRefresh = false): array
    {
        $cacheKey = self::PROFILE_DOCUMENTS_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            return \App\Models\UserDocument::where('user_id', $userId)
                ->where('is_active', true) // Assuming you have an active flag
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        });
    }
    public function getProfileStats(int $userId, bool $forceRefresh = false): array
    {
        $cacheKey = self::PROFILE_STATS_KEY . $userId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->defaultTtl, function () use ($userId) {
            $profile = UserProfile::where('user_id', $userId)->first();
            if (!$profile) {
                return [
                    'profile_completion' => 0,
                    'skills_count' => 0,
                    'last_updated' => null,
                ];
            }

            // Calculate profile completion percentage
            $completionFields = [
                'first_name', 'last_name', 'email', 'phone', 'address',
                'city', 'state', 'bio', 'experience', 'skills'
            ];
            
            $completedFields = 0;
            foreach ($completionFields as $field) {
                if (!empty($profile->$field)) {
                    $completedFields++;
                }
            }

            $completionPercentage = round(($completedFields / count($completionFields)) * 100);

            // Count skills (assuming it's a JSON field or comma-separated)
            $skillsCount = 0;
            if (!empty($profile->skills)) {
                if (is_string($profile->skills)) {
                    $skills = json_decode($profile->skills, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($skills)) {
                        $skillsCount = count($skills);
                    } else {
                        // Fallback to comma-separated count
                        $skillsCount = count(array_filter(explode(',', $profile->skills)));
                    }
                } elseif (is_array($profile->skills)) {
                    $skillsCount = count($profile->skills);
                }
            }

            return [
                'profile_completion' => $completionPercentage,
                'skills_count' => $skillsCount,
                'last_updated' => $profile->updated_at?->toISOString(),
            ];
        });
    }

    /**
     * Invalidate all cache for a specific user
     */
    public function invalidateUserCache(int $userId): void
    {
        $keys = [
            self::PROFILE_KEY . $userId,
            self::PROFILE_COMPLETE_KEY . $userId,
            self::PROFILE_STATS_KEY . $userId,
            self::PROFILE_ANALYTICS_KEY . $userId,
            self::PROFILE_SKILLS_KEY . $userId,
            self::PROFILE_INTERESTS_KEY . $userId,
            self::PROFILE_HISTORY_KEY . $userId,
            self::PROFILE_DOCUMENTS_KEY . $userId,
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate specific cache type for a user
     */
    public function invalidateSpecificCache(int $userId, string $type): void
    {
        $keyMap = [
            'profile' => self::PROFILE_KEY . $userId,
            'complete' => self::PROFILE_COMPLETE_KEY . $userId,
            'stats' => self::PROFILE_STATS_KEY . $userId,
            'analytics' => self::PROFILE_ANALYTICS_KEY . $userId,
            'skills' => self::PROFILE_SKILLS_KEY . $userId,
            'interests' => self::PROFILE_INTERESTS_KEY . $userId,
            'history' => self::PROFILE_HISTORY_KEY . $userId,
            'documents' => self::PROFILE_DOCUMENTS_KEY . $userId,
        ];

        if (isset($keyMap[$type])) {
            Cache::forget($keyMap[$type]);
        }
    }

    /**
     * Warm up cache for a user (preload all cache types)
     */
    public function warmUpUserCache(int $userId): void
    {
        // Preload all cache types
        $this->getProfile($userId);
        $this->getCompleteProfile($userId);
        $this->getProfileStats($userId);
        $this->getUserSkills($userId);
        $this->getUserInterests($userId);
        $this->getUserHistory($userId);
        $this->getUserDocuments($userId);
    }

    /**
     * Check if cached data exists for a user and type
     */
    public function hasCachedData(int $userId, string $type): bool
    {
        $keyMap = [
            'profile' => self::PROFILE_KEY . $userId,
            'complete' => self::PROFILE_COMPLETE_KEY . $userId,
            'stats' => self::PROFILE_STATS_KEY . $userId,
            'analytics' => self::PROFILE_ANALYTICS_KEY . $userId,
            'skills' => self::PROFILE_SKILLS_KEY . $userId,
            'interests' => self::PROFILE_INTERESTS_KEY . $userId,
            'history' => self::PROFILE_HISTORY_KEY . $userId,
            'documents' => self::PROFILE_DOCUMENTS_KEY . $userId,
        ];

        if (isset($keyMap[$type])) {
            return Cache::has($keyMap[$type]);
        }

        return false;
    }

    /**
     * Get cache key for a specific user and type
     */
    public function getCacheKey(int $userId, string $type): string
    {
        $keyMap = [
            'profile' => self::PROFILE_KEY . $userId,
            'complete' => self::PROFILE_COMPLETE_KEY . $userId,
            'stats' => self::PROFILE_STATS_KEY . $userId,
            'analytics' => self::PROFILE_ANALYTICS_KEY . $userId,
            'skills' => self::PROFILE_SKILLS_KEY . $userId,
            'interests' => self::PROFILE_INTERESTS_KEY . $userId,
            'history' => self::PROFILE_HISTORY_KEY . $userId,
            'documents' => self::PROFILE_DOCUMENTS_KEY . $userId,
        ];

        return $keyMap[$type] ?? '';
    }

    /**
     * Extend cache TTL for a specific key
     */
    public function extendCacheTTL(int $userId, string $type, int $minutes): bool
    {
        $cacheKey = $this->getCacheKey($userId, $type);
        
        if (empty($cacheKey) || !Cache::has($cacheKey)) {
            return false;
        }

        $value = Cache::get($cacheKey);
        Cache::put($cacheKey, $value, $minutes);

        return true;
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults(string $query, array $filters, array $results): void
    {
        $searchKey = $this->generateSearchKey($query, $filters);
        
        $cacheData = [
            'query' => $query,
            'filters' => $filters,
            'results' => $results,
            'cached_at' => Carbon::now()->toISOString(),
        ];

        Cache::put($searchKey, $cacheData, $this->searchTtl);
    }

    /**
     * Get cached search results
     */
    public function getCachedSearchResults(string $query, array $filters): ?array
    {
        $searchKey = $this->generateSearchKey($query, $filters);
        return Cache::get($searchKey);
    }

    /**
     * Cache analytics data for a user
     */
    public function cacheAnalytics(int $userId, array $analytics): void
    {
        $cacheKey = self::PROFILE_ANALYTICS_KEY . $userId;
        Cache::put($cacheKey, $analytics, $this->analyticsTtl);
    }

    /**
     * Get cached analytics data
     */
    public function getCachedAnalytics(int $userId): ?array
    {
        $cacheKey = self::PROFILE_ANALYTICS_KEY . $userId;
        return Cache::get($cacheKey);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // Get all cached profile keys (this is a simplified version)
        // In production, you might want to use Redis commands or track this separately
        $totalCachedProfiles = $this->countCachedProfiles();
        
        return [
            'total_cached_profiles' => $totalCachedProfiles,
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'last_updated' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Generate search key from query and filters
     */
    protected function generateSearchKey(string $query, array $filters): string
    {
        $filterString = http_build_query($filters);
        $keyString = $query . '_' . $filterString;
        return self::PROFILE_SEARCH_KEY . md5($keyString);
    }

    /**
     * Count cached profiles (simplified implementation)
     */
    protected function countCachedProfiles(): int
    {
        // This is a simplified implementation
        // In production, you might want to maintain a counter or use Redis commands
        return 0; // Placeholder - implement based on your cache driver
    }

    /**
     * Calculate cache hit rate (simplified implementation)
     */
    protected function calculateCacheHitRate(): float
    {
        // This would typically require tracking hits/misses
        // For now, return a placeholder value
        return 85.5; // Placeholder percentage
    }

    /**
     * Get cache memory usage (simplified implementation)
     */
    protected function getCacheMemoryUsage(): string
    {
        // This would depend on your cache driver
        // For now, return a placeholder value
        return '50MB'; // Placeholder
    }
}