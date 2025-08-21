<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | application including rate limiting, input validation, and security
    | headers.
    |
    */

    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        
        'limits' => [
            'default' => [
                'max_attempts' => env('RATE_LIMIT_DEFAULT_MAX', 60),
                'decay_minutes' => env('RATE_LIMIT_DEFAULT_DECAY', 1),
            ],
            'auth' => [
                'max_attempts' => env('RATE_LIMIT_AUTH_MAX', 5),
                'decay_minutes' => env('RATE_LIMIT_AUTH_DECAY', 15),
            ],
            'api' => [
                'max_attempts' => env('RATE_LIMIT_API_MAX', 100),
                'decay_minutes' => env('RATE_LIMIT_API_DECAY', 1),
            ],
            'upload' => [
                'max_attempts' => env('RATE_LIMIT_UPLOAD_MAX', 10),
                'decay_minutes' => env('RATE_LIMIT_UPLOAD_DECAY', 1),
            ],
            'password_reset' => [
                'max_attempts' => env('RATE_LIMIT_PASSWORD_RESET_MAX', 3),
                'decay_minutes' => env('RATE_LIMIT_PASSWORD_RESET_DECAY', 60),
            ],
        ],
    ],

    'input_validation' => [
        'max_input_length' => env('MAX_INPUT_LENGTH', 10000),
        'max_file_size' => env('MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_file_types' => [
            'images' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'spreadsheets' => ['xls', 'xlsx', 'csv'],
            'presentations' => ['ppt', 'pptx'],
            'archives' => ['zip', 'rar'],
        ],
        'blocked_extensions' => [
            'php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'bash'
        ],
    ],

    'security_headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'report_only' => env('CSP_REPORT_ONLY', false),
            'directives' => [
                'default-src' => "'self'",
                'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",
                'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                'font-src' => "'self' https://fonts.gstatic.com",
                'img-src' => "'self' data: https: blob:",
                'connect-src' => "'self' https://api.cloudinary.com https://translate.googleapis.com",
                'media-src' => "'self' https:",
                'object-src' => "'none'",
                'base-uri' => "'self'",
                'form-action' => "'self'",
                'frame-ancestors' => "'none'",
                'upgrade-insecure-requests' => true,
            ],
        ],

        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('HSTS_PRELOAD', true),
        ],

        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    ],

    'csrf' => [
        'enabled' => env('CSRF_ENABLED', true),
        'token_lifetime' => env('CSRF_TOKEN_LIFETIME', 120), // minutes
        'regenerate_on_login' => env('CSRF_REGENERATE_ON_LOGIN', true),
    ],

    'session_security' => [
        'secure_cookies' => env('SESSION_SECURE_COOKIES', true),
        'http_only_cookies' => env('SESSION_HTTP_ONLY_COOKIES', true),
        'same_site_cookies' => env('SESSION_SAME_SITE_COOKIES', 'strict'),
        'regenerate_on_login' => env('SESSION_REGENERATE_ON_LOGIN', true),
        'timeout' => env('SESSION_TIMEOUT', 120), // minutes
    ],

    'password_security' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90),
        'history_count' => env('PASSWORD_HISTORY_COUNT', 5),
    ],

    'logging' => [
        'security_events' => env('LOG_SECURITY_EVENTS', true),
        'failed_logins' => env('LOG_FAILED_LOGINS', true),
        'rate_limit_violations' => env('LOG_RATE_LIMIT_VIOLATIONS', true),
        'input_validation_failures' => env('LOG_INPUT_VALIDATION_FAILURES', true),
        'file_upload_attempts' => env('LOG_FILE_UPLOAD_ATTEMPTS', true),
    ],

    'monitoring' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 10), // violations per hour
        'notification_email' => env('SECURITY_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('SECURITY_SLACK_WEBHOOK'),
    ],

    'ip_filtering' => [
        'enabled' => env('IP_FILTERING_ENABLED', false),
        'whitelist' => array_filter(explode(',', env('IP_WHITELIST', ''))),
        'blacklist' => array_filter(explode(',', env('IP_BLACKLIST', ''))),
        'allow_private_ips' => env('ALLOW_PRIVATE_IPS', true),
    ],

    'encryption' => [
        'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key_rotation_days' => env('ENCRYPTION_KEY_ROTATION_DAYS', 90),
        'backup_keys_count' => env('ENCRYPTION_BACKUP_KEYS_COUNT', 3),
    ],
];