<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Volunteering Integration Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for volunteering system
    | integrations including API settings, social media, and exports.
    |
    */

    'api' => [
        'rate_limit' => env('VOLUNTEERING_API_RATE_LIMIT', 100), // requests per hour
        'cache_ttl' => env('VOLUNTEERING_API_CACHE_TTL', 3600), // seconds
        'pagination_limit' => env('VOLUNTEERING_API_PAGINATION_LIMIT', 50),
    ],

    'social_media' => [
        'enabled_platforms' => [
            'twitter' => env('SOCIAL_TWITTER_ENABLED', true),
            'facebook' => env('SOCIAL_FACEBOOK_ENABLED', true),
            'linkedin' => env('SOCIAL_LINKEDIN_ENABLED', true),
            'instagram' => env('SOCIAL_INSTAGRAM_ENABLED', true),
        ],
        
        'auto_share' => env('SOCIAL_AUTO_SHARE', false),
        
        'hashtags' => [
            'default' => ['Volunteer', 'Community', 'MakeADifference'],
            'categories' => [
                'education' => ['Education', 'Learning', 'Teaching'],
                'environment' => ['Environment', 'Green', 'Sustainability'],
                'health' => ['Health', 'Wellness', 'Healthcare'],
                'community' => ['Community', 'Local', 'Neighborhood'],
            ]
        ],
    ],

    'widgets' => [
        'cache_ttl' => env('WIDGET_CACHE_TTL', 1800), // 30 minutes
        'refresh_interval' => env('WIDGET_REFRESH_INTERVAL', 300000), // 5 minutes in milliseconds
        
        'available_widgets' => [
            'overview' => 'Volunteering Overview Statistics',
            'recent_applications' => 'Recent Volunteer Applications',
            'upcoming_opportunities' => 'Upcoming Opportunities',
            'volunteer_hours' => 'Volunteer Hours Chart',
            'top_volunteers' => 'Top Volunteers Leaderboard',
            'impact_metrics' => 'Impact Metrics Dashboard',
        ],
    ],

    'exports' => [
        'formats' => ['csv', 'json', 'xlsx'],
        'max_records' => env('EXPORT_MAX_RECORDS', 10000),
        'chunk_size' => env('EXPORT_CHUNK_SIZE', 1000),
        
        'available_types' => [
            'opportunities' => 'Volunteering Opportunities',
            'applications' => 'Volunteer Applications',
            'time_logs' => 'Volunteer Time Logs',
            'volunteers' => 'Volunteer Profiles',
            'organizations' => 'Organization Data',
        ],
    ],

    'event_integration' => [
        'enabled' => env('EVENT_INTEGRATION_ENABLED', false),
        'sync_interval' => env('EVENT_SYNC_INTERVAL', 3600), // 1 hour
        'auto_create_events' => env('AUTO_CREATE_EVENTS', false),
        
        'supported_platforms' => [
            'eventbrite' => [
                'api_key' => env('EVENTBRITE_API_KEY'),
                'webhook_url' => env('EVENTBRITE_WEBHOOK_URL'),
            ],
            'meetup' => [
                'api_key' => env('MEETUP_API_KEY'),
                'group_id' => env('MEETUP_GROUP_ID'),
            ],
        ],
    ],

    'organization_dashboard' => [
        'default_widgets' => [
            'overview',
            'recent_applications',
            'upcoming_opportunities',
            'volunteer_hours'
        ],
        
        'widget_permissions' => [
            'overview' => 'view_volunteering_stats',
            'recent_applications' => 'view_applications',
            'upcoming_opportunities' => 'view_opportunities',
            'volunteer_hours' => 'view_time_logs',
        ],
    ],

    'external_apis' => [
        'webhook_timeout' => env('WEBHOOK_TIMEOUT', 30), // seconds
        'webhook_retries' => env('WEBHOOK_RETRIES', 3),
        
        'allowed_origins' => [
            'localhost',
            '*.yourdomain.com',
        ],
        
        'cors_headers' => [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ],
    ],

    'notifications' => [
        'integration_events' => [
            'opportunity_shared' => true,
            'data_exported' => true,
            'widget_error' => true,
            'api_limit_reached' => true,
        ],
        
        'email_templates' => [
            'data_export_ready' => 'emails.volunteering.export-ready',
            'integration_error' => 'emails.volunteering.integration-error',
        ],
    ],

    'security' => [
        'api_key_length' => 64,
        'api_key_prefix' => 'vlp_',
        'encrypt_exports' => env('ENCRYPT_EXPORTS', false),
        'require_https' => env('REQUIRE_HTTPS', true),
        
        'rate_limiting' => [
            'api_calls' => '100:60', // 100 calls per minute
            'exports' => '10:60', // 10 exports per minute
            'social_shares' => '50:60', // 50 shares per minute
        ],
    ],
];