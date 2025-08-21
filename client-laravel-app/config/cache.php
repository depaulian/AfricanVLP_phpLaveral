<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |            "memcached", "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        // Custom cache stores for different data types
        'users' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'users',
        ],

        'organizations' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'organizations',
        ],

        'events' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'events',
        ],

        'resources' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'resources',
        ],

        'search' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'search',
        ],

        'queries' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'prefix' => 'queries',
        ],

        'sessions' => [
            'driver' => 'redis',
            'connection' => env('REDIS_SESSION_CONNECTION', 'default'),
            'prefix' => 'sessions',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, or DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_client_cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Cache tags allow you to tag related cache items and then flush them
    | all at once. This is useful for cache invalidation strategies.
    |
    */

    'tags' => [
        'users' => ['users', 'auth'],
        'organizations' => ['organizations', 'entities'],
        'events' => ['events', 'activities'],
        'resources' => ['resources', 'content'],
        'search' => ['search', 'discovery'],
        'statistics' => ['stats', 'analytics'],
        'configuration' => ['config', 'settings'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Defaults
    |--------------------------------------------------------------------------
    |
    | Default TTL values for different types of cached data.
    |
    */

    'ttl' => [
        'short' => 300,      // 5 minutes
        'medium' => 1800,    // 30 minutes
        'long' => 3600,      // 1 hour
        'very_long' => 86400, // 24 hours
        'permanent' => 604800, // 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related cache settings.
    |
    */

    'performance' => [
        'enable_compression' => env('CACHE_COMPRESSION', true),
        'compression_threshold' => env('CACHE_COMPRESSION_THRESHOLD', 1024), // bytes
        'serialization' => env('CACHE_SERIALIZATION', 'php'), // php, json, igbinary
        'max_key_length' => 250,
        'max_value_size' => 1048576, // 1MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for cache monitoring and statistics.
    |
    */

    'monitoring' => [
        'enabled' => env('CACHE_MONITORING', true),
        'log_hits' => env('CACHE_LOG_HITS', false),
        'log_misses' => env('CACHE_LOG_MISSES', true),
        'log_writes' => env('CACHE_LOG_WRITES', false),
        'alert_on_high_miss_rate' => env('CACHE_ALERT_HIGH_MISS_RATE', true),
        'high_miss_rate_threshold' => 0.8, // 80%
    ],

];