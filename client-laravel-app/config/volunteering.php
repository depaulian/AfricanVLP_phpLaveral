<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Volunteering System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the volunteering system
    | including integrations, social media, and external services.
    |
    */

    'integrations' => [
        /*
        |--------------------------------------------------------------------------
        | Event Management Integration
        |--------------------------------------------------------------------------
        |
        | Configuration for integrating volunteering opportunities with
        | event management systems.
        |
        */
        'event_management' => [
            'enabled' => env('EVENT_INTEGRATION_ENABLED', false),
            'api_url' => env('EVENT_MANAGEMENT_API_URL'),
            'api_key' => env('EVENT_MANAGEMENT_API_KEY'),
            'webhook_secret' => env('EVENT_MANAGEMENT_WEBHOOK_SECRET'),
            'auto_sync' => env('EVENT_AUTO_SYNC', true),
            'sync_fields' => [
                'title',
                'description',
                'start_date',
                'end_date',
                'location',
                'max_participants',
                'registration_deadline'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Social Media Integration
        |--------------------------------------------------------------------------
        |
        | Configuration for social media content generation and sharing.
        |
        */
        'social_media' => [
            'enabled' => env('SOCIAL_MEDIA_INTEGRATION_ENABLED', true),
            'platforms' => [
                'twitter' => [
                    'enabled' => env('TWITTER_INTEGRATION_ENABLED', true),
                    'api_key' => env('TWITTER_API_KEY'),
                    'api_secret' => env('TWITTER_API_SECRET'),
                    'access_token' => env('TWITTER_ACCESS_TOKEN'),
                    'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
                    'character_limit' => 280,
                    'auto_post' => env('TWITTER_AUTO_POST', false)
                ],
                'facebook' => [
                    'enabled' => env('FACEBOOK_INTEGRATION_ENABLED', true),
                    'app_id' => env('FACEBOOK_APP_ID'),
                    'app_secret' => env('FACEBOOK_APP_SECRET'),
                    'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
                    'auto_post' => env('FACEBOOK_AUTO_POST', false)
                ],
                'linkedin' => [
                    'enabled' => env('LINKEDIN_INTEGRATION_ENABLED', true),
                    'client_id' => env('LINKEDIN_CLIENT_ID'),
                    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
                    'access_token' => env('LINKEDIN_ACCESS_TOKEN'),
                    'auto_post' => env('LINKEDIN_AUTO_POST', false)
                ],
                'instagram' => [
                    'enabled' => env('INSTAGRAM_INTEGRATION_ENABLED', true),
                    'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
                    'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
                    'auto_post' => false // Instagram requires manual posting
                ]
            ],
            'default_hashtags' => [
                '#Volunteer',
                '#MakeADifference',
                '#Community',
                '#Volunteering',
                '#SocialImpact'
            ],
            'content_templates' => [
                'opportunity_created' => 'New volunteer opportunity available: {title}',
                'application_deadline' => 'Last chance to apply for: {title}',
                'volunteer_spotlight' => 'Celebrating our amazing volunteer: {name}',
                'impact_story' => 'See the impact of our volunteers: {story}'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | External API Configuration
        |--------------------------------------------------------------------------
        |
        | Settings for external API integrations and data exports.
        |
        */
        'external_api' => [
            'enabled' => env('EXTERNAL_API_ENABLED', true),
            'rate_limits' => [
                'default' => 60, // requests per minute
                'export' => 10,
                'social_content' => 30
            ],
            'authentication' => [
                'required' => env('API_AUTH_REQUIRED', true),
                'token_expiry' => 86400, // 24 hours
                'refresh_enabled' => true
            ],
            'export_formats' => ['json', 'csv', 'xlsx'],
            'max_export_records' => env('MAX_EXPORT_RECORDS', 10000),
            'cache_duration' => 300 // 5 minutes
        ],

        /*
        |--------------------------------------------------------------------------
        | Webhook Configuration
        |--------------------------------------------------------------------------
        |
        | Settings for webhook integrations with external systems.
        |
        */
        'webhooks' => [
            'enabled' => env('WEBHOOKS_ENABLED', true),
            'max_retries' => 3,
            'retry_delay' => 60, // seconds
            'timeout' => 30, // seconds
            'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),
            'events' => [
                'opportunity.created',
                'opportunity.updated',
                'opportunity.deleted',
                'application.submitted',
                'application.reviewed',
                'application.accepted',
                'application.rejected',
                'time_log.submitted',
                'time_log.approved',
                'time_log.rejected',
                'volunteer.assigned',
                'volunteer.completed'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Analytics Integration
        |--------------------------------------------------------------------------
        |
        | Configuration for analytics and reporting integrations.
        |
        */
        'analytics' => [
            'enabled' => env('ANALYTICS_INTEGRATION_ENABLED', true),
            'google_analytics' => [
                'enabled' => env('GA_INTEGRATION_ENABLED', false),
                'tracking_id' => env('GA_TRACKING_ID'),
                'measurement_id' => env('GA_MEASUREMENT_ID')
            ],
            'custom_analytics' => [
                'enabled' => env('CUSTOM_ANALYTICS_ENABLED', true),
                'api_endpoint' => env('CUSTOM_ANALYTICS_ENDPOINT'),
                'api_key' => env('CUSTOM_ANALYTICS_API_KEY')
            ],
            'metrics_to_track' => [
                'opportunity_views',
                'application_submissions',
                'volunteer_hours',
                'user_engagement',
                'conversion_rates'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for organization dashboard widgets.
    |
    */
    'widgets' => [
        'enabled' => env('VOLUNTEERING_WIDGETS_ENABLED', true),
        'cache_duration' => 300, // 5 minutes
        'refresh_interval' => 300000, // 5 minutes in milliseconds
        'available_widgets' => [
            'summary',
            'applications',
            'opportunities',
            'analytics',
            'social'
        ],
        'default_widgets' => [
            'summary',
            'applications',
            'opportunities'
        ],
        'permissions' => [
            'view_widgets' => 'organization.view_volunteering',
            'manage_widgets' => 'organization.manage_volunteering'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Export Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for data export functionality.
    |
    */
    'export' => [
        'enabled' => env('DATA_EXPORT_ENABLED', true),
        'formats' => [
            'json' => [
                'enabled' => true,
                'mime_type' => 'application/json',
                'extension' => 'json'
            ],
            'csv' => [
                'enabled' => true,
                'mime_type' => 'text/csv',
                'extension' => 'csv',
                'delimiter' => ',',
                'enclosure' => '"'
            ],
            'xlsx' => [
                'enabled' => env('XLSX_EXPORT_ENABLED', false),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx'
            ]
        ],
        'limits' => [
            'max_records' => env('EXPORT_MAX_RECORDS', 10000),
            'max_file_size' => env('EXPORT_MAX_FILE_SIZE', 50 * 1024 * 1024), // 50MB
            'daily_limit' => env('EXPORT_DAILY_LIMIT', 100)
        ],
        'security' => [
            'require_authentication' => true,
            'log_exports' => true,
            'anonymize_personal_data' => env('EXPORT_ANONYMIZE_DATA', false)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for integration-related notifications.
    |
    */
    'notifications' => [
        'integration_failures' => [
            'enabled' => env('NOTIFY_INTEGRATION_FAILURES', true),
            'channels' => ['mail', 'slack'],
            'recipients' => [
                env('ADMIN_EMAIL', 'admin@example.com')
            ]
        ],
        'export_completion' => [
            'enabled' => env('NOTIFY_EXPORT_COMPLETION', true),
            'channels' => ['mail']
        ],
        'webhook_failures' => [
            'enabled' => env('NOTIFY_WEBHOOK_FAILURES', true),
            'channels' => ['mail', 'slack'],
            'threshold' => 3 // notify after 3 consecutive failures
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for integrations.
    |
    */
    'security' => [
        'api_key_rotation' => [
            'enabled' => env('API_KEY_ROTATION_ENABLED', false),
            'rotation_interval' => 30 * 24 * 3600, // 30 days
            'notify_before_expiry' => 7 * 24 * 3600 // 7 days
        ],
        'ip_whitelist' => [
            'enabled' => env('IP_WHITELIST_ENABLED', false),
            'allowed_ips' => explode(',', env('ALLOWED_IPS', ''))
        ],
        'request_signing' => [
            'enabled' => env('REQUEST_SIGNING_ENABLED', false),
            'algorithm' => 'sha256',
            'header_name' => 'X-Signature'
        ]
    ]
];