<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profile Cache Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the profile caching system.
    | You can adjust cache TTL values, enable/disable features, and configure
    | performance optimization settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live) Settings
    |--------------------------------------------------------------------------
    |
    | Define how long different types of profile data should be cached.
    | Values are in minutes.
    |
    */
    'ttl' => [
        'profile' => env('PROFILE_CACHE_TTL', 60), // 1 hour
        'complete' => env('PROFILE_COMPLETE_CACHE_TTL', 60), // 1 hour
        'stats' => env('PROFILE_STATS_CACHE_TTL', 1440), // 24 hours
        'skills' => env('PROFILE_SKILLS_CACHE_TTL', 60), // 1 hour
        'interests' => env('PROFILE_INTERESTS_CACHE_TTL', 60), // 1 hour
        'history' => env('PROFILE_HISTORY_CACHE_TTL', 60), // 1 hour
        'documents' => env('PROFILE_DOCUMENTS_CACHE_TTL', 15), // 15 minutes
        'search' => env('PROFILE_SEARCH_CACHE_TTL', 15), // 15 minutes
        'analytics' => env('PROFILE_ANALYTICS_CACHE_TTL', 1440), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes used for different types of cached profile data.
    | These help organize and identify cached data.
    |
    */
    'prefixes' => [
        'profile' => 'profile:',
        'complete' => 'profile:complete:',
        'stats' => 'profile:stats:',
        'skills' => 'profile:skills:',
        'interests' => 'profile:interests:',
        'history' => 'profile:history:',
        'documents' => 'profile:documents:',
        'search' => 'profile:search:',
        'analytics' => 'profile:analytics:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific caching features.
    |
    */
    'features' => [
        'enabled' => env('PROFILE_CACHE_ENABLED', true),
        'auto_warm' => env('PROFILE_CACHE_AUTO_WARM', true),
        'smart_invalidation' => env('PROFILE_CACHE_SMART_INVALIDATION', true),
        'preload_active_users' => env('PROFILE_CACHE_PRELOAD_ACTIVE', true),
        'search_caching' => env('PROFILE_SEARCH_CACHE_ENABLED', true),
        'analytics_caching' => env('PROFILE_ANALYTICS_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization features.
    |
    */
    'performance' => [
        'batch_size' => env('PROFILE_CACHE_BATCH_SIZE', 10),
        'max_preload_users' => env('PROFILE_CACHE_MAX_PRELOAD', 100),
        'active_user_days' => env('PROFILE_CACHE_ACTIVE_DAYS', 7),
        'query_optimization' => env('PROFILE_QUERY_OPTIMIZATION', true),
        'eager_loading' => env('PROFILE_EAGER_LOADING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    |
    | Settings for cache monitoring and logging.
    |
    */
    'monitoring' => [
        'log_cache_hits' => env('PROFILE_CACHE_LOG_HITS', false),
        'log_cache_misses' => env('PROFILE_CACHE_LOG_MISSES', false),
        'log_invalidations' => env('PROFILE_CACHE_LOG_INVALIDATIONS', true),
        'performance_logging' => env('PROFILE_CACHE_PERFORMANCE_LOG', false),
        'metrics_enabled' => env('PROFILE_CACHE_METRICS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming Schedule
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic cache warming.
    |
    */
    'warming' => [
        'enabled' => env('PROFILE_CACHE_WARMING_ENABLED', true),
        'schedule' => env('PROFILE_CACHE_WARMING_SCHEDULE', '0 2 * * *'), // Daily at 2 AM
        'active_users_only' => env('PROFILE_CACHE_WARM_ACTIVE_ONLY', true),
        'batch_delay' => env('PROFILE_CACHE_WARM_DELAY', 500), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Cache Settings
    |--------------------------------------------------------------------------
    |
    | Specific settings for profile search result caching.
    |
    */
    'search' => [
        'max_results_to_cache' => env('PROFILE_SEARCH_MAX_CACHE_RESULTS', 100),
        'cache_empty_results' => env('PROFILE_SEARCH_CACHE_EMPTY', false),
        'filter_cache_enabled' => env('PROFILE_SEARCH_FILTER_CACHE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for database query optimization.
    |
    */
    'database' => [
        'use_raw_queries' => env('PROFILE_DB_RAW_QUERIES', true),
        'optimize_joins' => env('PROFILE_DB_OPTIMIZE_JOINS', true),
        'limit_relationships' => env('PROFILE_DB_LIMIT_RELATIONSHIPS', true),
        'select_specific_columns' => env('PROFILE_DB_SELECT_COLUMNS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Storage Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to cache storage and memory management.
    |
    */
    'storage' => [
        'compress_data' => env('PROFILE_CACHE_COMPRESS', false),
        'serialize_format' => env('PROFILE_CACHE_SERIALIZE', 'php'), // php, json, igbinary
        'memory_limit_mb' => env('PROFILE_CACHE_MEMORY_LIMIT', 256),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    |
    | Behavior when cache is unavailable or fails.
    |
    */
    'fallback' => [
        'use_database_on_cache_fail' => env('PROFILE_CACHE_FALLBACK_DB', true),
        'log_fallback_usage' => env('PROFILE_CACHE_LOG_FALLBACK', true),
        'retry_cache_operations' => env('PROFILE_CACHE_RETRY', true),
        'max_retries' => env('PROFILE_CACHE_MAX_RETRIES', 3),
    ],
];