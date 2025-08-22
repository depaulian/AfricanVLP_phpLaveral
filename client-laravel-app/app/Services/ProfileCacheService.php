<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\UserVolunteeringInterest;
use App\Models\UserVolunteeringHistory;
use App\Models\UserDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfileCacheService
{
    // Primary keys required by tests
    public const PROFILE_KEY = 'profile:';
    public const PROFILE_COMPLETE_KEY = 'profile_complete:';
    public const PROFILE_STATS_KEY = 'profile_stats:';

    // Additional keys used by middleware/invalidation and extra features
    public const PROFILE_SKILLS_KEY = 'profile_skills:';
    public const PROFILE_INTERESTS_KEY = 'profile_interests:';
    public const PROFILE_HISTORY_KEY = 'profile_history:';
    public const PROFILE_DOCUMENTS_KEY = 'profile_documents:';

    // Auxiliary keys
    public const PROFILE_SEARCH_KEY = 'profile_search:';        // + md5(query|filters)
    public const PROFILE_ANALYTICS_KEY = 'profile_analytics:';  // + userId

    // TTLs (minutes)
    protected int $ttlProfile = 60;
    protected int $ttlComplete = 30;
    protected int $ttlStats = 20;
    protected int $ttlItems = 60;
    protected int $ttlSearch = 10;
    protected int $ttlAnalytics = 60;

    // Store expiration timestamps alongside cache values (under a parallel :exp key)
    protected function expKey(string $key): string
    {
        return $key . ':exp';
    }

    // Key builder exposed for tests/middleware
    public function getCacheKey(int $userId, string $type): string
    {
        return match ($type) {
            'profile' => self::PROFILE_KEY . $userId,
            'complete' => self::PROFILE_COMPLETE_KEY . $userId,
            'stats' => self::PROFILE_STATS_KEY . $userId,
            'skills' => self::PROFILE_SKILLS_KEY . $userId,
            'interests' => self::PROFILE_INTERESTS_KEY . $userId,
            'history' => self::PROFILE_HISTORY_KEY . $userId,
            'documents' => self::PROFILE_DOCUMENTS_KEY . $userId,
            default => self::PROFILE_COMPLETE_KEY . $userId,
        };
    }

    public function hasCachedData(int $userId, string $type): bool
    {
        return Cache::has($this->getCacheKey($userId, $type));
    }

    public function extendCacheTTL(int $userId, string $type, int $minutes): bool
    {
        $key = $this->getCacheKey($userId, $type);
        if (!Cache::has($key)) {
            return false;
        }

        $value = Cache::get($key);
        Cache::put($key, $value, now()->addMinutes($minutes));

        // Update parallel exp key if present
        $expKey = $this->expKey($key);
        Cache::put($expKey, now()->addMinutes($minutes), now()->addMinutes($minutes));

        return true;
    }

    public function getCacheExpiration(int $userId, string $type): ?Carbon
    {
        $key = $this->getCacheKey($userId, $type);
        $exp = Cache::get($this->expKey($key));
        return $exp instanceof Carbon ? $exp : (is_string($exp) ? Carbon::parse($exp) : null);
    }

    // ---------- Core getters ----------

    public function getProfile(int $userId, bool $force = false): ?UserProfile
    {
        // If user doesn't exist
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $key = self::PROFILE_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlProfile);

        $result = Cache::remember($key, $ttl, function () use ($userId) {
            return UserProfile::where('user_id', $userId)->first();
        });

        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    public function getCompleteProfile(int $userId, bool $force = false): ?array
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $key = self::PROFILE_COMPLETE_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlComplete);

        $result = Cache::remember($key, $ttl, function () use ($userId, $user) {
            $profile = UserProfile::where('user_id', $userId)->first();

            return [
                'user' => $user->only(['id', 'name', 'email']),
                'profile' => $profile,
                'skills' => UserSkill::where('user_id', $userId)->get(),
                'interests' => UserVolunteeringInterest::where('user_id', $userId)->get(),
                'history' => UserVolunteeringHistory::where('user_id', $userId)->get(),
                'documents' => UserDocument::where('user_id', $userId)->get(),
                'cached_at' => now(),
            ];
        });

        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    public function getProfileStats(int $userId, bool $force = false): array
    {
        $key = self::PROFILE_STATS_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlStats);

        $result = Cache::remember($key, $ttl, function () use ($userId) {
            $profile = UserProfile::where('user_id', $userId)->first();
            $skillsCount = UserSkill::where('user_id', $userId)->count();

            $fields = [
                'first_name', 'last_name', 'bio', 'phone_number', 'address',
                'city_id', 'country_id', 'date_of_birth', 'gender', 'education_level',
            ];

            $present = 0;
            $total = count($fields);

            if ($profile) {
                foreach ($fields as $field) {
                    $val = $profile->{$field} ?? null;
                    if (!is_null($val) && $val !== '') {
                        $present++;
                    }
                }
            }

            $completion = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

            return [
                'profile_completion' => $completion,
                'skills_count' => $skillsCount,
                'last_updated' => $profile?->updated_at,
                'cached_at' => now(),
            ];
        });

        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    // ---------- Additional getters for specific sections ----------

    public function getUserSkills(int $userId, bool $force = false)
    {
        $key = self::PROFILE_SKILLS_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlItems);

        $result = Cache::remember($key, $ttl, fn () => UserSkill::where('user_id', $userId)->get());
        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    public function getUserInterests(int $userId, bool $force = false)
    {
        $key = self::PROFILE_INTERESTS_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlItems);

        $result = Cache::remember($key, $ttl, fn () => UserVolunteeringInterest::where('user_id', $userId)->get());
        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    public function getVolunteeringHistory(int $userId, bool $force = false)
    {
        $key = self::PROFILE_HISTORY_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlItems);

        $result = Cache::remember($key, $ttl, fn () => UserVolunteeringHistory::where('user_id', $userId)->get());
        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    public function getUserDocuments(int $userId, bool $force = false)
    {
        $key = self::PROFILE_DOCUMENTS_KEY . $userId;

        if ($force) {
            Cache::forget($key);
            Cache::forget($this->expKey($key));
        }

        $ttl = now()->addMinutes($this->ttlItems);

        $result = Cache::remember($key, $ttl, fn () => UserDocument::where('user_id', $userId)->get());
        Cache::put($this->expKey($key), $ttl, $ttl);

        return $result;
    }

    // ---------- Invalidation and warming ----------

    public function invalidateUserCache(int $userId): void
    {
        $types = ['profile', 'complete', 'stats', 'skills', 'interests', 'history', 'documents'];
        foreach ($types as $type) {
            $this->invalidateSpecificCache($userId, $type);
        }
    }

    public function invalidateSpecificCache(int $userId, string $type): void
    {
        $key = $this->getCacheKey($userId, $type);
        Cache::forget($key);
        Cache::forget($this->expKey($key));
    }

    public function warmUpUserCache(int $userId): void
    {
        $this->getProfile($userId, true);
        $this->getCompleteProfile($userId, true);
        $this->getProfileStats($userId, true);
    }

    // ---------- Search + Analytics cache ----------

    protected function searchKey(string $query, array $filters): string
    {
        $normalized = mb_strtolower(trim($query));
        $hash = md5($normalized . '|' . json_encode($filters));
        return self::PROFILE_SEARCH_KEY . $hash;
    }

    public function cacheSearchResults(string $query, array $filters, array $results): void
    {
        $key = $this->searchKey($query, $filters);
        $ttl = now()->addMinutes($this->ttlSearch);

        Cache::put($key, [
            'query' => $query,
            'filters' => $filters,
            'results' => $results,
            'cached_at' => now(),
        ], $ttl);

        Cache::put($this->expKey($key), $ttl, $ttl);
    }

    public function getCachedSearchResults(string $query, array $filters): ?array
    {
        $key = $this->searchKey($query, $filters);
        return Cache::get($key);
    }

    public function cacheAnalytics(int $userId, array $analytics): void
    {
        $key = self::PROFILE_ANALYTICS_KEY . $userId;
        $ttl = now()->addMinutes($this->ttlAnalytics);

        Cache::put($key, $analytics, $ttl);
        Cache::put($this->expKey($key), $ttl, $ttl);
    }

    public function getCachedAnalytics(int $userId): ?array
    {
        $key = self::PROFILE_ANALYTICS_KEY . $userId;
        $data = Cache::get($key);
        return is_array($data) ? $data : null;
    }

    // ---------- Diagnostics ----------

    public function getCacheStats(): array
    {
        
        return [
            'total_cached_profiles' => null, 
            'cache_hit_rate' => null,
            'memory_usage' => null,
            'last_updated' => now(),
        ];
    }
}