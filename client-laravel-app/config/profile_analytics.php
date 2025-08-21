<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profile Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the profile analytics
    | reporting system, including report types, formats, and delivery options.
    |
    */

    'reports' => [
        /*
        |--------------------------------------------------------------------------
        | Report Types
        |--------------------------------------------------------------------------
        |
        | Available report types and their configurations
        |
        */
        'types' => [
            'comprehensive' => [
                'name' => 'Comprehensive Analytics',
                'description' => 'Complete profile analytics including scoring, behavioral analysis, and engagement metrics',
                'includes' => ['analytics', 'scoring', 'behavioral'],
                'default_format' => 'html',
                'cache_duration' => 3600, // 1 hour
            ],
            'summary' => [
                'name' => 'Summary Report',
                'description' => 'High-level overview of key profile metrics',
                'includes' => ['completion_score', 'engagement_level', 'profile_views'],
                'default_format' => 'json',
                'cache_duration' => 1800, // 30 minutes
            ],
            'behavioral' => [
                'name' => 'Behavioral Analysis',
                'description' => 'Detailed analysis of user behavior patterns and usage statistics',
                'includes' => ['usage_patterns', 'engagement_patterns', 'behavioral_insights'],
                'default_format' => 'csv',
                'cache_duration' => 7200, // 2 hours
            ],
            'scoring' => [
                'name' => 'Profile Scoring',
                'description' => 'Comprehensive profile scoring with detailed breakdowns',
                'includes' => ['scoring_analysis', 'improvement_areas', 'strengths'],
                'default_format' => 'json',
                'cache_duration' => 3600, // 1 hour
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Report Periods
        |--------------------------------------------------------------------------
        |
        | Available time periods for report generation
        |
        */
        'periods' => [
            'daily' => [
                'name' => 'Daily',
                'description' => 'Last 24 hours',
                'days' => 1,
            ],
            'weekly' => [
                'name' => 'Weekly',
                'description' => 'Last 7 days',
                'days' => 7,
            ],
            'monthly' => [
                'name' => 'Monthly',
                'description' => 'Last 30 days',
                'days' => 30,
            ],
            'quarterly' => [
                'name' => 'Quarterly',
                'description' => 'Last 90 days',
                'days' => 90,
            ],
            'yearly' => [
                'name' => 'Yearly',
                'description' => 'Last 365 days',
                'days' => 365,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Output Formats
        |--------------------------------------------------------------------------
        |
        | Available output formats and their configurations
        |
        */
        'formats' => [
            'json' => [
                'name' => 'JSON',
                'description' => 'Machine-readable JSON format',
                'extension' => 'json',
                'mime_type' => 'application/json',
                'supports_email' => true,
            ],
            'csv' => [
                'name' => 'CSV',
                'description' => 'Comma-separated values for spreadsheet applications',
                'extension' => 'csv',
                'mime_type' => 'text/csv',
                'supports_email' => true,
            ],
            'html' => [
                'name' => 'HTML',
                'description' => 'Web-friendly HTML format with styling',
                'extension' => 'html',
                'mime_type' => 'text/html',
                'supports_email' => true,
            ],
            'pdf' => [
                'name' => 'PDF',
                'description' => 'Portable Document Format (requires PDF library)',
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'supports_email' => true,
                'enabled' => false, // Disabled until PDF library is installed
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for report storage and file management
    |
    */
    'storage' => [
        'disk' => env('PROFILE_ANALYTICS_DISK', 'local'),
        'path' => 'reports/profile-analytics',
        'retention_days' => env('PROFILE_ANALYTICS_RETENTION_DAYS', 90),
        'max_file_size' => env('PROFILE_ANALYTICS_MAX_FILE_SIZE', 50 * 1024 * 1024), // 50MB
        'cleanup_enabled' => env('PROFILE_ANALYTICS_CLEANUP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for email delivery of reports
    |
    */
    'email' => [
        'enabled' => env('PROFILE_ANALYTICS_EMAIL_ENABLED', true),
        'from_address' => env('PROFILE_ANALYTICS_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('PROFILE_ANALYTICS_FROM_NAME', 'Profile Analytics System'),
        'subject_prefix' => env('PROFILE_ANALYTICS_SUBJECT_PREFIX', '[Analytics Report]'),
        'max_attachment_size' => env('PROFILE_ANALYTICS_MAX_ATTACHMENT_SIZE', 25 * 1024 * 1024), // 25MB
        'allowed_domains' => env('PROFILE_ANALYTICS_ALLOWED_DOMAINS', ''), // Comma-separated list
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization
    |
    */
    'performance' => [
        'batch_size' => env('PROFILE_ANALYTICS_BATCH_SIZE', 100),
        'memory_limit' => env('PROFILE_ANALYTICS_MEMORY_LIMIT', '512M'),
        'timeout' => env('PROFILE_ANALYTICS_TIMEOUT', 300), // 5 minutes
        'enable_caching' => env('PROFILE_ANALYTICS_ENABLE_CACHING', true),
        'cache_prefix' => 'profile_analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for report generation and access
    |
    */
    'security' => [
        'require_authentication' => env('PROFILE_ANALYTICS_REQUIRE_AUTH', true),
        'allowed_roles' => ['admin', 'analytics_manager'],
        'rate_limit' => [
            'enabled' => env('PROFILE_ANALYTICS_RATE_LIMIT_ENABLED', true),
            'max_requests' => env('PROFILE_ANALYTICS_RATE_LIMIT_MAX', 10),
            'per_minutes' => env('PROFILE_ANALYTICS_RATE_LIMIT_MINUTES', 60),
        ],
        'data_anonymization' => [
            'enabled' => env('PROFILE_ANALYTICS_ANONYMIZE_DATA', false),
            'fields_to_anonymize' => ['email', 'phone', 'ip_address'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Reports Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatically scheduled reports
    |
    */
    'scheduled_reports' => [
        'enabled' => env('PROFILE_ANALYTICS_SCHEDULED_ENABLED', true),
        'default_recipients' => env('PROFILE_ANALYTICS_DEFAULT_RECIPIENTS', ''),
        'reports' => [
            'daily_summary' => [
                'enabled' => true,
                'type' => 'summary',
                'period' => 'daily',
                'format' => 'json',
                'time' => '01:00',
                'recipients' => [],
            ],
            'weekly_comprehensive' => [
                'enabled' => true,
                'type' => 'comprehensive',
                'period' => 'weekly',
                'format' => 'html',
                'day' => 'monday',
                'time' => '06:00',
                'recipients' => [],
            ],
            'monthly_behavioral' => [
                'enabled' => true,
                'type' => 'behavioral',
                'period' => 'monthly',
                'format' => 'csv',
                'day' => 1, // First day of month
                'time' => '07:00',
                'recipients' => [],
            ],
            'quarterly_scoring' => [
                'enabled' => true,
                'type' => 'scoring',
                'period' => 'quarterly',
                'format' => 'json',
                'day' => 1, // First day of quarter
                'time' => '08:00',
                'recipients' => [],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications about report generation
    |
    */
    'notifications' => [
        'enabled' => env('PROFILE_ANALYTICS_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail', 'database'],
        'notify_on' => [
            'report_generated' => true,
            'report_failed' => true,
            'large_report' => true, // Reports over certain size
            'long_generation_time' => true, // Reports taking longer than expected
        ],
        'thresholds' => [
            'large_report_size' => 10 * 1024 * 1024, // 10MB
            'long_generation_time' => 300, // 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for data export and external integrations
    |
    */
    'export' => [
        'enabled' => env('PROFILE_ANALYTICS_EXPORT_ENABLED', true),
        'formats' => ['json', 'csv', 'xml'],
        'compression' => [
            'enabled' => env('PROFILE_ANALYTICS_COMPRESSION_ENABLED', true),
            'algorithm' => 'gzip',
            'level' => 6,
        ],
        'external_apis' => [
            'enabled' => env('PROFILE_ANALYTICS_EXTERNAL_API_ENABLED', false),
            'endpoints' => [
                // Configure external API endpoints here
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for logging report generation activities
    |
    */
    'logging' => [
        'enabled' => env('PROFILE_ANALYTICS_LOGGING_ENABLED', true),
        'level' => env('PROFILE_ANALYTICS_LOG_LEVEL', 'info'),
        'channel' => env('PROFILE_ANALYTICS_LOG_CHANNEL', 'single'),
        'log_queries' => env('PROFILE_ANALYTICS_LOG_QUERIES', false),
        'log_performance' => env('PROFILE_ANALYTICS_LOG_PERFORMANCE', true),
    ],
];