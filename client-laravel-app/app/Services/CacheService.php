<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public const SHORT_TTL = 300;      // 5 minutes
    public const MEDIUM_TTL = 3600;    // 1 hour
    public const LONG_TTL = 86400;     // 24 hours

    /**
     * Remember a value in cache, supporting optional cache tags when available.
     */
    public function remember(string $key, int $ttl, Closure $callback, array $tags = [])
    {
        if (!empty($tags) && method_exists(Cache::getStore(), 'tags')) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Get a cached value (with optional tags).
     */
    public function get(string $key, mixed $default = null, array $tags = []): mixed
    {
        if (!empty($tags) && method_exists(Cache::getStore(), 'tags')) {
            return Cache::tags($tags)->get($key, $default);
        }
        return Cache::get($key, $default);
    }

    /**
     * Put a value in cache (with optional tags).
     */
    public function put(string $key, mixed $value, int $ttl, array $tags = []): void
    {
        if (!empty($tags) && method_exists(Cache::getStore(), 'tags')) {
            Cache::tags($tags)->put($key, $value, $ttl);
            return;
        }
        Cache::put($key, $value, $ttl);
    }

    /**
     * Forget a cached value (with optional tags).
     */
    public function forget(string $key, array $tags = []): void
    {
        if (!empty($tags) && method_exists(Cache::getStore(), 'tags')) {
            Cache::tags($tags)->forget($key);
            return;
        }
        Cache::forget($key);
    }

    /**
     * Helpers tailored for query optimization service.
     */
    public function getCachedQuery(string $cacheKey, array $tags = []): mixed
    {
        return $this->get($cacheKey, null, $tags);
    }

    public function setCachedQuery(string $cacheKey, mixed $value, int $ttl = self::MEDIUM_TTL, array $tags = []): void
    {
        $this->put($cacheKey, $value, $ttl, $tags);
    }
}
