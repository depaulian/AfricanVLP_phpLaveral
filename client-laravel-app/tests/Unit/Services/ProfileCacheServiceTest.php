<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProfileCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileCacheService $cacheService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(ProfileCacheService::class);
        
        // Create test user with profile
        $this->user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_cache_and_retrieve_profile()
    {
        // Clear any existing cache
        Cache::flush();
        
        // First call should hit database and cache result
        $profile1 = $this->cacheService->getProfile($this->user->id);
        $this->assertNotNull($profile1);
        
        // Second call should hit cache
        $profile2 = $this->cacheService->getProfile($this->user->id);
        $this->assertNotNull($profile2);
        
        // Verify cache key exists
        $cacheKey = ProfileCacheService::PROFILE_KEY . $this->user->id;
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_can_force_refresh_cache()
    {
        // Cache initial data
        $profile1 = $this->cacheService->getProfile($this->user->id);
        
        // Force refresh
        $profile2 = $this->cacheService->getProfile($this->user->id, true);
        
        $this->assertNotNull($profile1);
        $this->assertNotNull($profile2);
    }

    public function test_can_get_complete_profile()
    {
        $completeProfile = $this->cacheService->getCompleteProfile($this->user->id);
        
        $this->assertNotNull($completeProfile);
        $this->assertIsArray($completeProfile);
        $this->assertArrayHasKey('user', $completeProfile);
        $this->assertArrayHasKey('profile', $completeProfile);
        $this->assertArrayHasKey('cached_at', $completeProfile);
    }

    public function test_can_get_profile_stats()
    {
        $stats = $this->cacheService->getProfileStats($this->user->id);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('profile_completion', $stats);
        $this->assertArrayHasKey('skills_count', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
    }

    public function test_can_invalidate_user_cache()
    {
        // Cache some data
        $this->cacheService->getProfile($this->user->id);
        $this->cacheService->getCompleteProfile($this->user->id);
        
        // Verify cache exists
        $profileKey = ProfileCacheService::PROFILE_KEY . $this->user->id;
        $completeKey = ProfileCacheService::PROFILE_COMPLETE_KEY . $this->user->id;
        
        $this->assertTrue(Cache::has($profileKey));
        $this->assertTrue(Cache::has($completeKey));
        
        // Invalidate cache
        $this->cacheService->invalidateUserCache($this->user->id);
        
        // Verify cache is cleared
        $this->assertFalse(Cache::has($profileKey));
        $this->assertFalse(Cache::has($completeKey));
    }

    public function test_can_invalidate_specific_cache_type()
    {
        // Cache different types
        $this->cacheService->getProfile($this->user->id);
        $this->cacheService->getProfileStats($this->user->id);
        
        $profileKey = ProfileCacheService::PROFILE_KEY . $this->user->id;
        $statsKey = ProfileCacheService::PROFILE_STATS_KEY . $this->user->id;
        
        $this->assertTrue(Cache::has($profileKey));
        $this->assertTrue(Cache::has($statsKey));
        
        // Invalidate only profile cache
        $this->cacheService->invalidateSpecificCache($this->user->id, 'profile');
        
        // Profile cache should be cleared, stats should remain
        $this->assertFalse(Cache::has($profileKey));
        $this->assertTrue(Cache::has($statsKey));
    }

    public function test_can_warm_up_user_cache()
    {
        // Clear cache
        Cache::flush();
        
        // Warm up cache
        $this->cacheService->warmUpUserCache($this->user->id);
        
        // Verify different cache types exist
        $profileKey = ProfileCacheService::PROFILE_KEY . $this->user->id;
        $completeKey = ProfileCacheService::PROFILE_COMPLETE_KEY . $this->user->id;
        $statsKey = ProfileCacheService::PROFILE_STATS_KEY . $this->user->id;
        
        $this->assertTrue(Cache::has($profileKey));
        $this->assertTrue(Cache::has($completeKey));
        $this->assertTrue(Cache::has($statsKey));
    }

    public function test_can_check_cached_data_existence()
    {
        // Initially no cache
        $this->assertFalse($this->cacheService->hasCachedData($this->user->id, 'profile'));
        
        // Cache some data
        $this->cacheService->getProfile($this->user->id);
        
        // Now cache should exist
        $this->assertTrue($this->cacheService->hasCachedData($this->user->id, 'profile'));
    }

    public function test_can_get_cache_key()
    {
        $profileKey = $this->cacheService->getCacheKey($this->user->id, 'profile');
        $expectedKey = ProfileCacheService::PROFILE_KEY . $this->user->id;
        
        $this->assertEquals($expectedKey, $profileKey);
    }

    public function test_can_extend_cache_ttl()
    {
        // Cache some data
        $this->cacheService->getProfile($this->user->id);
        
        // Extend TTL
        $result = $this->cacheService->extendCacheTTL($this->user->id, 'profile', 120);
        
        $this->assertTrue($result);
    }

    public function test_returns_null_for_non_existent_user()
    {
        $profile = $this->cacheService->getProfile(99999);
        $this->assertNull($profile);
        
        $completeProfile = $this->cacheService->getCompleteProfile(99999);
        $this->assertNull($completeProfile);
    }

    public function test_can_cache_search_results()
    {
        $query = 'test search';
        $filters = ['city_id' => 1];
        $results = ['user1', 'user2'];
        
        // Cache search results
        $this->cacheService->cacheSearchResults($query, $filters, $results);
        
        // Retrieve cached results
        $cachedResults = $this->cacheService->getCachedSearchResults($query, $filters);
        
        $this->assertNotNull($cachedResults);
        $this->assertEquals($results, $cachedResults['results']);
        $this->assertEquals($query, $cachedResults['query']);
        $this->assertEquals($filters, $cachedResults['filters']);
    }

    public function test_can_cache_analytics_data()
    {
        $analytics = [
            'views' => 100,
            'interactions' => 50,
            'completion_rate' => 85.5
        ];
        
        // Cache analytics
        $this->cacheService->cacheAnalytics($this->user->id, $analytics);
        
        // Retrieve cached analytics
        $cachedAnalytics = $this->cacheService->getCachedAnalytics($this->user->id);
        
        $this->assertEquals($analytics, $cachedAnalytics);
    }

    public function test_can_get_cache_stats()
    {
        $stats = $this->cacheService->getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_cached_profiles', $stats);
        $this->assertArrayHasKey('cache_hit_rate', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}