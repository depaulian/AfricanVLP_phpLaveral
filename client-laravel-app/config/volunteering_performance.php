<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Volunteering Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for optimizing volunteering system performance
    |
    */

    'cache' => [
        'enabled' => env('VOLUNTEERING_CACHE_ENABLED', true),
        'default_ttl' => env('VOLUNTEERING_CACHE_TTL', 3600), // 1 hour
        'long_ttl' => env('VOLUNTEERING_CACHE_LONG_TTL', 86400), // 24 hours
        'short_ttl' => env('VOLUNTEERING_CACHE_SHORT_TTL', 300), // 5 minutes
        
        'keys' => [
            'opportunities' => 'volunteering:opportunities',
            'categories' => 'volunteering:categories',
            'popular' => 'volunteering:popular',
            'featured' => 'volunteering:featured',
            'user_applications' => 'volunteering:user_applications',
            'stats' => 'volunteering:stats',
        ],
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'cursor_threshold' => 100, // Use cursor pagination after this page
    ],

    'search' => [
        'use_fulltext' => env('VOLUNTEERING_USE_FULLTEXT_SEARCH', true),
        'min_search_length' => 3,
        'max_results' => 1000,
    ],

    'performance_monitoring' => [
        'enabled' => env('VOLUNTEERING_PERFORMANCE_MONITORING', true),
        'log_channel' => 'performance',
        
        'thresholds' => [
            'execution_time' => 2000, // milliseconds
            'memory_usage' => 50 * 1024 * 1024, // 50MB
            'query_count' => 50,
            'query_time' => 1000, // milliseconds
        ],
        
        'alerts' => [
            'enabled' => env('PERFORMANCE_ALERTS_ENABLED', false),
            'channels' => ['slack', 'email'],
            'threshold_multiplier' => 2, // Alert when threshold is exceeded by this factor
        ],
    ],

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        
        'optimization' => [
            'use_indexes' => true,
            'analyze_tables' => true,
            'optimize_queries' => true,
        ],
        
        'indexes' => [
            'volunteering_opportunities' => [
                'status_deadline' => ['status', 'application_deadline'],
                'category_status' => ['category_id', 'status'],
                'city_status' => ['city_id', 'status'],
                'featured_status' => ['featured', 'status'],
                'fulltext_search' => ['title', 'description'],
            ],
            
            'volunteer_applications' => [
                'user_status' => ['user_id', 'status'],
                'opportunity_status' => ['opportunity_id', 'status'],
                'status_created' => ['status', 'created_at'],
            ],
            
            'volunteer_assignments' => [
                'user_status' => ['user_id', 'status'],
                'opportunity_status' => ['opportunity_id', 'status'],
                'assignment_dates' => ['start_date', 'end_date'],
            ],
        ],
    ],

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'provider' => env('CDN_PROVIDER', 'cloudinary'),
        
        'assets' => [
            'opportunity_images' => [
                'sizes' => ['thumbnail', 'card', 'hero'],
                'formats' => ['webp', 'jpg'],
                'quality' => 'auto',
            ],
            
            'organization_logos' => [
                'sizes' => ['small', 'medium', 'large'],
                'formats' => ['webp', 'png'],
                'quality' => 'auto',
            ],
            
            'certificates' => [
                'format' => 'pdf',
                'compression' => true,
            ],
        ],
    ],

    'lazy_loading' => [
        'enabled' => true,
        
        'relationships' => [
            'opportunity' => [
                'basic' => ['organization:id,name,slug', 'category:id,name,slug', 'city:id,name'],
                'detailed' => ['organization', 'category', 'city', 'role', 'creator'],
                'with_stats' => ['applications', 'assignments'],
            ],
            
            'application' => [
                'basic' => ['user:id,name,email', 'opportunity:id,title,slug'],
                'detailed' => ['user', 'opportunity.organization', 'assignment'],
            ],
            
            'assignment' => [
                'basic' => ['user:id,name,email', 'opportunity:id,title'],
                'detailed' => ['user', 'opportunity', 'supervisor', 'timeLogs'],
            ],
        ],
    ],

    'preloading' => [
        'enabled' => true,
        
        'data' => [
            'popular_opportunities' => 10,
            'featured_opportunities' => 6,
            'recent_opportunities' => 50,
            'categories_with_counts' => true,
            'cities_list' => true,
            'countries_list' => true,
        ],
        
        'schedule' => [
            'popular_opportunities' => 'hourly',
            'featured_opportunities' => 'daily',
            'categories_with_counts' => 'daily',
        ],
    ],

    'optimization' => [
        'query_optimization' => true,
        'eager_loading' => true,
        'select_optimization' => true,
        'chunk_processing' => true,
        'batch_operations' => true,
        
        'chunk_size' => 1000,
        'batch_size' => 100,
    ],

    'metrics' => [
        'enabled' => env('VOLUNTEERING_METRICS_ENABLED', true),
        'retention_days' => 30,
        
        'collect' => [
            'response_times' => true,
            'memory_usage' => true,
            'query_counts' => true,
            'cache_hit_rates' => true,
            'error_rates' => true,
        ],
        
        'aggregation' => [
            'intervals' => ['1m', '5m', '1h', '1d'],
            'metrics' => ['avg', 'max', 'min', 'count'],
        ],
    ],
];